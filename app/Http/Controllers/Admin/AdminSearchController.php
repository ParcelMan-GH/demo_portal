<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\RecipientPaymentTask;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\ShipmentPayment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\WarehouseReceiptItemLabel;
use App\Services\BackOfficeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminSearchController extends Controller
{
    private const LIMIT = 5;

    private const MIN_QUERY_LENGTH = 2;

    private const MAX_QUERY_LENGTH = 100;

    private const RESULTS_PER_PAGE = 15;

    private const GROUPS = ['shipments', 'packages', 'transactions', 'vendors', 'drivers'];

    /**
     * Typed prefixes that scope the search to one category,
     * e.g. "vendor: kofi" or "trk: TRK12345".
     */
    private const PREFIXES = [
        'vendor' => 'vendors',
        'vendors' => 'vendors',
        'shipment' => 'shipments',
        'shipments' => 'shipments',
        'order' => 'shipments',
        'parcel' => 'packages',
        'parcels' => 'packages',
        'package' => 'packages',
        'packages' => 'packages',
        'trk' => 'packages',
        'tracking' => 'packages',
        'barcode' => 'packages',
        'rider' => 'drivers',
        'riders' => 'drivers',
        'driver' => 'drivers',
        'drivers' => 'drivers',
        'txn' => 'transactions',
        'transaction' => 'transactions',
        'transactions' => 'transactions',
        'payment' => 'transactions',
    ];

    /**
     * Global admin search.
     *
     * GET /admin/search?q=&type=
     */
    public function search(Request $request): JsonResponse
    {
        $raw = substr(trim((string) $request->get('q', '')), 0, self::MAX_QUERY_LENGTH);
        [$q, $type] = $this->parseQuery($raw, $request->get('type'));

        if (strlen($q) < self::MIN_QUERY_LENGTH) {
            return response()->json(['data' => [], 'meta' => ['type' => $type, 'order' => []]]);
        }

        /** @var User|null $admin */
        $admin = $request->user('admin');
        $phones = $this->phoneVariants($q);
        $data = [];

        $include = fn (string $group): bool => $type === null || $type === $group;

        if ($include('shipments') && $this->can($admin, 'shipments.view')) {
            $shipments = $this->shipments($q, $phones);

            if ($shipments->isNotEmpty()) {
                $data['shipments'] = $shipments->values()->all();
            }
        }

        if ($include('packages') && $this->can($admin, 'shipments.view')) {
            $packages = $this->packages($q, $phones);

            if ($packages->isNotEmpty()) {
                $data['packages'] = $packages->values()->all();
            }
        }

        if ($include('transactions')) {
            $transactions = $this->transactions($q, $admin);

            if ($transactions->isNotEmpty()) {
                $data['transactions'] = $transactions->values()->all();
            }
        }

        if ($include('vendors') && $this->can($admin, 'vendors.view')) {
            $vendors = $this->vendors($q, $phones);

            if ($vendors->isNotEmpty()) {
                $data['vendors'] = $vendors->values()->all();
            }
        }

        if ($include('drivers') && $this->can($admin, 'drivers.view')) {
            $drivers = $this->drivers($q, $phones);

            if ($drivers->isNotEmpty()) {
                $data['drivers'] = $drivers->values()->all();
            }
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'type' => $type,
                'order' => $this->groupOrder($q, $phones !== []),
            ],
        ]);
    }

    /**
     * Full advanced-search results page.
     *
     * GET /admin/search/results?q=&type=&status=&date_from=&date_to=
     */
    public function results(Request $request)
    {
        $raw = substr(trim((string) $request->get('q', '')), 0, self::MAX_QUERY_LENGTH);
        [$q, $detected] = $this->parseQuery($raw, $request->get('type'));

        /** @var User|null $admin */
        $admin = $request->user('admin');

        $tabs = array_values(array_unique(array_filter([
            $this->can($admin, 'shipments.view') ? 'shipments' : null,
            $this->can($admin, 'shipments.view') ? 'packages' : null,
            $this->can($admin, 'vendors.view') ? 'vendors' : null,
            $this->can($admin, 'drivers.view') ? 'drivers' : null,
            'transactions',
        ])));

        $type = in_array($detected, $tabs, true)
            ? $detected
            : ($this->detectPriorityTab($q, $tabs) ?? ($tabs[0] ?? 'transactions'));

        $filters = [
            'status' => (string) $request->get('status', ''),
            'date_from' => (string) $request->get('date_from', ''),
            'date_to' => (string) $request->get('date_to', ''),
        ];

        $phones = $this->phoneVariants($q);
        $hasQuery = strlen($q) >= self::MIN_QUERY_LENGTH;
        $counts = [];
        $results = null;
        $transactionGroups = [];

        if ($hasQuery) {
            foreach ($tabs as $tab) {
                $counts[$tab] = match ($tab) {
                    'shipments' => $this->applyDateFilters($this->shipmentsQuery($q, $phones, $filters['status']), $filters)->count(),
                    'packages' => $this->applyDateFilters($this->packageItemsQuery($q, $phones), $filters)->count(),
                    'vendors' => $this->applyDateFilters($this->vendorsQuery($q, $phones), $filters)->count(),
                    'drivers' => $this->applyDateFilters($this->driversQuery($q, $phones), $filters)->count(),
                    'transactions' => $this->transactions($q, $admin, 10)->count(),
                };
            }

            if ($type === 'transactions') {
                $transactionGroups = array_filter([
                    'Shipment payments' => $this->can($admin, 'charges.view') ? $this->shipmentPayments($q, 10) : collect(),
                    'Shipment charges' => $this->can($admin, 'charges.view') ? $this->shipmentCharges($q, 10) : collect(),
                    'Recipient payments' => $this->can($admin, 'recipient_payments.view') ? $this->recipientPayments($q, 10) : collect(),
                    'Vendor payouts' => $this->can($admin, 'vendors.manage') ? $this->vendorPayouts($q, 10) : collect(),
                ], fn (Collection $group) => $group->isNotEmpty());
            } else {
                $results = match ($type) {
                    'shipments' => $this->applyDateFilters($this->shipmentsQuery($q, $phones, $filters['status']), $filters)
                        ->with('vendor:id,name,business_name')
                        ->latest()
                        ->paginate(self::RESULTS_PER_PAGE)
                        ->withQueryString()
                        ->through(fn (Shipment $shipment) => $this->mapShipment($shipment)),
                    'packages' => $this->applyDateFilters($this->packageItemsQuery($q, $phones), $filters)
                        ->with(['shipment:id,shipment_number,status,vendor_id', 'shipment.vendor:id,name,business_name'])
                        ->latest()
                        ->paginate(self::RESULTS_PER_PAGE)
                        ->withQueryString()
                        ->through(fn (ShipmentItem $item) => $this->mapPackageItem($item)),
                    'vendors' => $this->applyDateFilters($this->vendorsQuery($q, $phones), $filters)
                        ->latest()
                        ->paginate(self::RESULTS_PER_PAGE)
                        ->withQueryString()
                        ->through(fn (Vendor $vendor) => $this->mapVendor($vendor)),
                    'drivers' => $this->applyDateFilters($this->driversQuery($q, $phones), $filters)
                        ->latest()
                        ->paginate(self::RESULTS_PER_PAGE)
                        ->withQueryString()
                        ->through(fn (Driver $driver) => $this->mapDriver($driver)),
                    default => null,
                };
            }
        }

        return view('admin.search.results', [
            'q' => $q,
            'type' => $type,
            'tabs' => $tabs,
            'counts' => $counts,
            'results' => $results,
            'transactionGroups' => $transactionGroups,
            'filters' => $filters,
            'hasQuery' => $hasQuery,
            'shipmentStatuses' => \App\Enums\ShipmentStatus::cases(),
        ]);
    }

    private function shipments(string $q, array $phones = []): Collection
    {
        return $this->shipmentsQuery($q, $phones)
            ->with('vendor:id,name,business_name')
            ->latest()
            ->limit(self::LIMIT)
            ->get(['id', 'shipment_number', 'status', 'vendor_id'])
            ->map(fn (Shipment $shipment) => $this->mapShipment($shipment));
    }

    private function shipmentsQuery(string $q, array $phones = [], string $status = '')
    {
        $like = $this->like($q);

        return Shipment::query()
            ->where(function ($query) use ($like, $phones) {
                $query->where('shipment_number', 'like', $like)
                    ->orWhere('recipient_name', 'like', $like)
                    ->orWhere('recipient_phone', 'like', $like)
                    ->orWhere('delivery_recipient_name', 'like', $like)
                    ->orWhere('delivery_recipient_phone', 'like', $like)
                    ->orWhereHas('vendor', fn ($vendor) => $vendor
                        ->where('name', 'like', $like)
                        ->orWhere('business_name', 'like', $like));

                foreach ($phones as $phone) {
                    $query->orWhere('recipient_phone', 'like', $phone.'%')
                        ->orWhere('delivery_recipient_phone', 'like', $phone.'%')
                        ->orWhereHas('vendor', fn ($vendor) => $vendor->where('phone', 'like', $phone.'%'));
                }
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status));
    }

    private function mapShipment(Shipment $shipment): array
    {
        return $this->result(
            $shipment->id,
            $shipment->shipment_number,
            $shipment->vendor?->business_name ?? $shipment->vendor?->name ?? '-',
            $this->statusLabel($shipment->status),
            route('admin.shipments.show', $shipment->id),
        );
    }

    private function packages(string $q, array $phones = []): Collection
    {
        $like = $this->like($q);

        $labels = WarehouseReceiptItemLabel::query()
            ->with([
                'receiptItem:id,warehouse_receipt_id,shipment_item_id,barcode_value',
                'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
                'receiptItem.shipmentItem.shipment:id,shipment_number,vendor_id,status',
                'receiptItem.shipmentItem.shipment.vendor:id,name,business_name',
            ])
            ->where(function ($query) use ($like, $phones) {
                $query->where('barcode_value', 'like', $like)
                    ->orWhereHas('receiptItem', fn ($receiptItem) => $receiptItem->where('barcode_value', 'like', $like))
                    ->orWhereHas('receiptItem.shipmentItem', fn ($item) => $item
                        ->where('tracking_code', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('delivery_recipient_name', 'like', $like)
                        ->orWhere('delivery_recipient_phone', 'like', $like)
                        ->orWhere('delivery_town', 'like', $like))
                    ->orWhereHas('receiptItem.shipmentItem.shipment', fn ($shipment) => $shipment->where('shipment_number', 'like', $like));

                foreach ($phones as $phone) {
                    $query->orWhereHas('receiptItem.shipmentItem', fn ($item) => $item->where('delivery_recipient_phone', 'like', $phone.'%'));
                }
            })
            ->orderByRaw(
                'CASE WHEN barcode_value = ? THEN 0 WHEN barcode_value LIKE ? THEN 1 ELSE 2 END',
                [$q, $q.'%'],
            )
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(function (WarehouseReceiptItemLabel $label) {
                $item = $label->receiptItem?->shipmentItem;
                $shipment = $item?->shipment;
                $tracking = $item?->tracking_code ?: $label->receiptItem?->barcode_value;
                $description = $item?->description;
                $sub = collect([$tracking, $description, $shipment?->shipment_number])
                    ->filter()
                    ->implode(' | ');

                return $this->result(
                    'label-'.$label->id,
                    $label->barcode_value,
                    $sub !== '' ? $sub : 'Package label',
                    $item?->delivery_recipient_phone ?: $this->statusLabel($shipment?->status),
                    $this->shipmentOrPackageTrackingUrl($shipment?->id, $label->barcode_value),
                );
            });

        if ($labels->count() >= self::LIMIT) {
            return $labels->take(self::LIMIT);
        }

        $items = ShipmentItem::query()
            ->with(['shipment:id,shipment_number,status,vendor_id', 'shipment.vendor:id,name,business_name'])
            ->where(function ($query) use ($like, $phones) {
                $query->where('tracking_code', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('delivery_recipient_name', 'like', $like)
                    ->orWhere('delivery_recipient_phone', 'like', $like)
                    ->orWhere('delivery_town', 'like', $like)
                    ->orWhereHas('shipment', fn ($shipment) => $shipment->where('shipment_number', 'like', $like));

                foreach ($phones as $phone) {
                    $query->orWhere('delivery_recipient_phone', 'like', $phone.'%');
                }
            })
            ->whereDoesntHave('warehouseReceiptItems.labels')
            ->orderByRaw(
                'CASE WHEN tracking_code = ? THEN 0 WHEN tracking_code LIKE ? THEN 1 ELSE 2 END',
                [$q, $q.'%'],
            )
            ->latest()
            ->limit(self::LIMIT - $labels->count())
            ->get()
            ->map(function (ShipmentItem $item) {
                $shipment = $item->shipment;
                $label = $item->tracking_code ?: ('Shipment item #'.$item->id);
                $sub = collect([$item->description, $shipment?->shipment_number])
                    ->filter()
                    ->implode(' | ');

                return $this->result(
                    'item-'.$item->id,
                    $label,
                    $sub !== '' ? $sub : 'Package',
                    $item->delivery_recipient_phone ?: $this->statusLabel($shipment?->status),
                    $this->shipmentOrPackageTrackingUrl($shipment?->id, $label),
                );
            });

        return $labels->merge($items)->take(self::LIMIT)->values();
    }

    private function transactions(string $q, ?User $admin, int $limit = self::LIMIT): Collection
    {
        return collect()
            ->merge($this->can($admin, 'charges.view') ? $this->shipmentPayments($q, $limit) : collect())
            ->merge($this->can($admin, 'charges.view') ? $this->shipmentCharges($q, $limit) : collect())
            ->merge($this->can($admin, 'recipient_payments.view') ? $this->recipientPayments($q, $limit) : collect())
            ->merge($this->can($admin, 'vendors.manage') ? $this->vendorPayouts($q, $limit) : collect())
            ->sortByDesc('created_at')
            ->take($limit)
            ->map(fn (array $result) => collect($result)->except('created_at')->all())
            ->values();
    }

    private function shipmentPayments(string $q, int $limit = self::LIMIT): Collection
    {
        $like = $this->like($q);

        return ShipmentPayment::query()
            ->with(['shipment:id,shipment_number,vendor_id', 'shipment.vendor:id,name,business_name'])
            ->where(function ($query) use ($like) {
                $query->where('reference_number', 'like', $like)
                    ->orWhere('payment_method', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('shipment', fn ($shipment) => $shipment->where('shipment_number', 'like', $like))
                    ->orWhereHas('shipment.vendor', fn ($vendor) => $vendor
                        ->where('name', 'like', $like)
                        ->orWhere('business_name', 'like', $like));
            })
            ->orderByRaw(
                'CASE WHEN reference_number = ? THEN 0 WHEN reference_number LIKE ? THEN 1 ELSE 2 END',
                [$q, $q.'%'],
            )
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ShipmentPayment $payment) => $this->transactionResult(
                'payment-'.$payment->id,
                $payment->reference_number ?: ('Payment #'.$payment->id),
                collect([$this->money($payment->amount), $payment->payment_method, $payment->shipment?->shipment_number])->filter()->implode(' | '),
                'Shipment payment',
                $this->shipmentUrl($payment->shipment_id, 'payments'),
                $payment->created_at,
            ));
    }

    private function shipmentCharges(string $q, int $limit = self::LIMIT): Collection
    {
        $like = $this->like($q);

        return ShipmentCharge::query()
            ->with(['shipment:id,shipment_number,vendor_id', 'shipmentItem:id,tracking_code,description'])
            ->where(function ($query) use ($like) {
                $query->where('payment_reference', 'like', $like)
                    ->orWhere('charge_type', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('shipment', fn ($shipment) => $shipment->where('shipment_number', 'like', $like))
                    ->orWhereHas('shipmentItem', fn ($item) => $item
                        ->where('tracking_code', 'like', $like)
                        ->orWhere('description', 'like', $like));
            })
            ->orderByRaw(
                'CASE WHEN payment_reference = ? THEN 0 WHEN payment_reference LIKE ? THEN 1 ELSE 2 END',
                [$q, $q.'%'],
            )
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ShipmentCharge $charge) => $this->transactionResult(
                'charge-'.$charge->id,
                $charge->payment_reference ?: ('Charge #'.$charge->id),
                collect([$this->money($charge->amount, $charge->currency), $charge->shipment?->shipment_number, $charge->shipmentItem?->tracking_code])->filter()->implode(' | '),
                $this->statusLabel($charge->charge_type).' | '.$this->statusLabel($charge->status),
                $this->shipmentUrl($charge->shipment_id, 'charges'),
                $charge->created_at,
            ));
    }

    private function recipientPayments(string $q, int $limit = self::LIMIT): Collection
    {
        $like = $this->like($q);

        return RecipientPaymentTask::query()
            ->with(['shipment:id,shipment_number', 'shipmentItem:id,tracking_code,description'])
            ->where(function ($query) use ($like) {
                $query->where('payment_reference', 'like', $like)
                    ->orWhere('recipient_name', 'like', $like)
                    ->orWhere('recipient_phone', 'like', $like)
                    ->orWhere('delivery_town', 'like', $like)
                    ->orWhereHas('shipment', fn ($shipment) => $shipment->where('shipment_number', 'like', $like))
                    ->orWhereHas('shipmentItem', fn ($item) => $item
                        ->where('tracking_code', 'like', $like)
                        ->orWhere('description', 'like', $like));
            })
            ->orderByRaw(
                'CASE WHEN payment_reference = ? THEN 0 WHEN payment_reference LIKE ? THEN 1 ELSE 2 END',
                [$q, $q.'%'],
            )
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (RecipientPaymentTask $task) => $this->transactionResult(
                'recipient-payment-'.$task->id,
                $task->payment_reference ?: ('Recipient payment #'.$task->id),
                collect([$task->recipient_name, $task->recipient_phone, $task->shipmentItem?->tracking_code, $task->shipment?->shipment_number])->filter()->implode(' | '),
                $this->statusLabel($task->status),
                route('admin.recipient-payments.index', ['search' => $task->payment_reference ?: $task->recipient_phone]),
                $task->created_at,
            ));
    }

    private function vendorPayouts(string $q, int $limit = self::LIMIT): Collection
    {
        $like = $this->like($q);

        return VendorPayout::query()
            ->with('vendor:id,name,business_name')
            ->where(function ($query) use ($like) {
                $query->where('payment_reference', 'like', $like)
                    ->orWhere('payment_phone', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereHas('vendor', fn ($vendor) => $vendor
                        ->where('name', 'like', $like)
                        ->orWhere('business_name', 'like', $like));
            })
            ->orderByRaw(
                'CASE WHEN payment_reference = ? THEN 0 WHEN payment_reference LIKE ? THEN 1 ELSE 2 END',
                [$q, $q.'%'],
            )
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (VendorPayout $payout) => $this->transactionResult(
                'vendor-payout-'.$payout->id,
                $payout->payment_reference ?: ('Vendor payout #'.$payout->id),
                collect([$this->money($payout->amount), $payout->vendor?->business_name ?? $payout->vendor?->name, $payout->payment_phone])->filter()->implode(' | '),
                'Vendor payout | '.$this->statusLabel($payout->status),
                $payout->vendor_id
                    ? route('admin.vendors.show', [
                        'vendor' => $payout->vendor_id,
                        'tab' => 'payouts',
                        'search' => $payout->payment_reference ?: $payout->payment_phone,
                    ])
                    : route('admin.vendor-payouts.index', ['search' => $payout->payment_reference ?: $payout->payment_phone]),
                $payout->created_at,
            ));
    }

    private function vendors(string $q, array $phones = []): Collection
    {
        return $this->vendorsQuery($q, $phones)
            ->latest()
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'business_name', 'phone'])
            ->map(fn (Vendor $vendor) => $this->mapVendor($vendor));
    }

    private function vendorsQuery(string $q, array $phones = [])
    {
        $like = $this->like($q);

        return Vendor::query()->where(function ($query) use ($like, $phones) {
            $query->where('name', 'like', $like)
                ->orWhere('business_name', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like);

            foreach ($phones as $phone) {
                $query->orWhere('phone', 'like', $phone.'%');
            }
        });
    }

    private function mapVendor(Vendor $vendor): array
    {
        return $this->result(
            $vendor->id,
            $vendor->business_name ?? $vendor->name,
            $vendor->phone ?? '-',
            null,
            route('admin.vendors.show', $vendor->id),
        );
    }

    private function drivers(string $q, array $phones = []): Collection
    {
        return $this->driversQuery($q, $phones)
            ->latest()
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'phone', 'is_active'])
            ->map(fn (Driver $driver) => $this->mapDriver($driver));
    }

    private function driversQuery(string $q, array $phones = [])
    {
        $like = $this->like($q);

        return Driver::query()->where(function ($query) use ($like, $phones) {
            $query->where('name', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like);

            foreach ($phones as $phone) {
                $query->orWhere('phone', 'like', $phone.'%');
            }
        });
    }

    private function mapDriver(Driver $driver): array
    {
        return $this->result(
            $driver->id,
            $driver->name,
            $driver->phone ?? '-',
            $driver->is_active ? 'Active' : 'Inactive',
            route('admin.drivers.show', $driver->id),
        );
    }

    private function packageItemsQuery(string $q, array $phones = [])
    {
        $like = $this->like($q);

        return ShipmentItem::query()->where(function ($query) use ($like, $phones) {
            $query->where('tracking_code', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('delivery_recipient_name', 'like', $like)
                ->orWhere('delivery_recipient_phone', 'like', $like)
                ->orWhere('delivery_town', 'like', $like)
                ->orWhereHas('shipment', fn ($shipment) => $shipment->where('shipment_number', 'like', $like))
                ->orWhereHas('warehouseReceiptItems.labels', fn ($label) => $label->where('barcode_value', 'like', $like));

            foreach ($phones as $phone) {
                $query->orWhere('delivery_recipient_phone', 'like', $phone.'%');
            }
        });
    }

    private function mapPackageItem(ShipmentItem $item): array
    {
        $shipment = $item->shipment;
        $label = $item->tracking_code ?: ('Shipment item #'.$item->id);
        $sub = collect([$item->description, $shipment?->shipment_number])
            ->filter()
            ->implode(' | ');

        return $this->result(
            'item-'.$item->id,
            $label,
            $sub !== '' ? $sub : 'Package',
            $item->delivery_recipient_phone ?: $this->statusLabel($shipment?->status),
            $this->shipmentOrPackageTrackingUrl($shipment?->id, $label),
        );
    }

    /**
     * Split an optional "category:" prefix off the query and resolve the
     * requested result type (typed prefix wins over the ?type= parameter).
     *
     * @return array{0: string, 1: ?string}
     */
    private function parseQuery(string $raw, mixed $requestedType): array
    {
        $type = null;

        if (preg_match('/^([a-z]+):\s*(.*)$/i', $raw, $matches) && isset(self::PREFIXES[strtolower($matches[1])])) {
            $type = self::PREFIXES[strtolower($matches[1])];
            $raw = trim($matches[2]);
        } elseif (is_string($requestedType) && in_array($requestedType, self::GROUPS, true)) {
            $type = $requestedType;
        }

        return [$raw, $type];
    }

    /**
     * Ghana-aware phone variants so "0241234567", "+233241234567",
     * "233241234567" and "024 123 4567" all find the same records.
     */
    private function phoneVariants(string $q): array
    {
        $normalized = preg_replace('/[\s\-().]/', '', $q);

        if (! preg_match('/^\+?\d{9,15}$/', (string) $normalized)) {
            return [];
        }

        $digits = ltrim((string) $normalized, '+');

        if (str_starts_with($digits, '233')) {
            $local = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $local = substr($digits, 1);
        } else {
            $local = $digits;
        }

        $local = substr($local, 0, 9);

        if (strlen($local) < 7) {
            return [];
        }

        return array_values(array_unique([
            (string) $normalized,
            '+233'.$local,
            '233'.$local,
            '0'.$local,
        ]));
    }

    /**
     * Render order for result groups: phone-looking queries surface people
     * first, code-looking queries surface packages first.
     */
    private function groupOrder(string $q, bool $isPhone): array
    {
        if ($isPhone) {
            return ['vendors', 'drivers', 'shipments', 'packages', 'transactions'];
        }

        if ($this->looksLikeCode($q)) {
            return ['packages', 'shipments', 'transactions', 'vendors', 'drivers'];
        }

        return self::GROUPS;
    }

    private function looksLikeCode(string $q): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9\-]{6,}$/', $q) && (bool) preg_match('/\d/', $q);
    }

    private function detectPriorityTab(string $q, array $tabs): ?string
    {
        if ($this->phoneVariants($q) !== [] && in_array('vendors', $tabs, true)) {
            return 'vendors';
        }

        if ($this->looksLikeCode($q) && in_array('packages', $tabs, true)) {
            return 'packages';
        }

        return null;
    }

    private function applyDateFilters($query, array $filters)
    {
        return $query
            ->when($filters['date_from'] !== '', fn ($builder) => $builder->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($builder) => $builder->whereDate('created_at', '<=', $filters['date_to']));
    }

    private function result(mixed $id, string $label, ?string $sub, ?string $status, string $url): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'sub' => $sub ?: '-',
            'status' => $status,
            'url' => $url,
        ];
    }

    private function transactionResult(
        mixed $id,
        string $label,
        string $sub,
        ?string $status,
        string $url,
        mixed $createdAt,
    ): array {
        return $this->result($id, $label, $sub, $status, $url) + [
            'created_at' => optional($createdAt)->timestamp ?? 0,
        ];
    }

    private function shipmentOrPackageTrackingUrl(?int $shipmentId, string $search): string
    {
        if ($shipmentId) {
            return $this->shipmentUrl($shipmentId, 'packages');
        }

        return route('admin.package-tracking.index', ['search' => $search]);
    }

    private function shipmentUrl(?int $shipmentId, ?string $fragment = null): string
    {
        if (! $shipmentId) {
            return route('admin.package-tracking.index');
        }

        $url = route('admin.shipments.show', $shipmentId);

        return $fragment ? $url.'#'.$fragment : $url;
    }

    private function like(string $q): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';
    }

    private function statusLabel(mixed $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        if (is_object($status) && property_exists($status, 'value')) {
            $status = $status->value;
        }

        return ucwords(str_replace('_', ' ', (string) $status));
    }

    private function money(mixed $amount, string $currency = 'GHS'): string
    {
        return trim($currency.' '.number_format((float) $amount, 2));
    }

    private function can(?User $user, string $permission): bool
    {
        return $user ? app(BackOfficeAccess::class)->canUsePermission($user, $permission) : false;
    }
}
