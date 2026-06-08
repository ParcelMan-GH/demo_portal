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

    /**
     * Global admin search.
     *
     * GET /admin/search?q=
     */
    public function search(Request $request): JsonResponse
    {
        $q = substr(trim((string) $request->get('q', '')), 0, self::MAX_QUERY_LENGTH);

        if (strlen($q) < self::MIN_QUERY_LENGTH) {
            return response()->json(['data' => []]);
        }

        /** @var User|null $admin */
        $admin = $request->user('admin');
        $data = [];

        if ($this->can($admin, 'shipments.view')) {
            $shipments = $this->shipments($q);
            $packages = $this->packages($q);

            if ($shipments->isNotEmpty()) {
                $data['shipments'] = $shipments->values()->all();
            }

            if ($packages->isNotEmpty()) {
                $data['packages'] = $packages->values()->all();
            }
        }

        $transactions = $this->transactions($q, $admin);
        if ($transactions->isNotEmpty()) {
            $data['transactions'] = $transactions->values()->all();
        }

        if ($this->can($admin, 'vendors.view')) {
            $vendors = $this->vendors($q);

            if ($vendors->isNotEmpty()) {
                $data['vendors'] = $vendors->values()->all();
            }
        }

        if ($this->can($admin, 'drivers.view')) {
            $drivers = $this->drivers($q);

            if ($drivers->isNotEmpty()) {
                $data['drivers'] = $drivers->values()->all();
            }
        }

        return response()->json(['data' => $data]);
    }

    private function shipments(string $q): Collection
    {
        $like = $this->like($q);

        return Shipment::query()
            ->with('vendor:id,name,business_name')
            ->where(function ($query) use ($like) {
                $query->where('shipment_number', 'like', $like)
                    ->orWhere('recipient_name', 'like', $like)
                    ->orWhere('recipient_phone', 'like', $like)
                    ->orWhere('delivery_recipient_name', 'like', $like)
                    ->orWhere('delivery_recipient_phone', 'like', $like)
                    ->orWhereHas('vendor', fn ($vendor) => $vendor
                        ->where('name', 'like', $like)
                        ->orWhere('business_name', 'like', $like));
            })
            ->latest()
            ->limit(self::LIMIT)
            ->get(['id', 'shipment_number', 'status', 'vendor_id'])
            ->map(fn (Shipment $shipment) => $this->result(
                $shipment->id,
                $shipment->shipment_number,
                $shipment->vendor?->business_name ?? $shipment->vendor?->name ?? '-',
                $this->statusLabel($shipment->status),
                route('admin.shipments.show', $shipment->id),
            ));
    }

    private function packages(string $q): Collection
    {
        $like = $this->like($q);

        $labels = WarehouseReceiptItemLabel::query()
            ->with([
                'receiptItem:id,warehouse_receipt_id,shipment_item_id,barcode_value',
                'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
                'receiptItem.shipmentItem.shipment:id,shipment_number,vendor_id,status',
                'receiptItem.shipmentItem.shipment.vendor:id,name,business_name',
            ])
            ->where(function ($query) use ($like) {
                $query->where('barcode_value', 'like', $like)
                    ->orWhereHas('receiptItem', fn ($receiptItem) => $receiptItem->where('barcode_value', 'like', $like))
                    ->orWhereHas('receiptItem.shipmentItem', fn ($item) => $item
                        ->where('tracking_code', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('delivery_recipient_name', 'like', $like)
                        ->orWhere('delivery_recipient_phone', 'like', $like)
                        ->orWhere('delivery_town', 'like', $like))
                    ->orWhereHas('receiptItem.shipmentItem.shipment', fn ($shipment) => $shipment->where('shipment_number', 'like', $like));
            })
            ->orderByRaw(
                'CASE WHEN barcode_value = ? THEN 0 WHEN barcode_value LIKE ? THEN 1 ELSE 2 END',
                [$q, $q . '%'],
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
                    'label-' . $label->id,
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
            ->where(function ($query) use ($like) {
                $query->where('tracking_code', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('delivery_recipient_name', 'like', $like)
                    ->orWhere('delivery_recipient_phone', 'like', $like)
                    ->orWhere('delivery_town', 'like', $like)
                    ->orWhereHas('shipment', fn ($shipment) => $shipment->where('shipment_number', 'like', $like));
            })
            ->whereDoesntHave('warehouseReceiptItems.labels')
            ->orderByRaw(
                'CASE WHEN tracking_code = ? THEN 0 WHEN tracking_code LIKE ? THEN 1 ELSE 2 END',
                [$q, $q . '%'],
            )
            ->latest()
            ->limit(self::LIMIT - $labels->count())
            ->get()
            ->map(function (ShipmentItem $item) {
                $shipment = $item->shipment;
                $label = $item->tracking_code ?: ('Shipment item #' . $item->id);
                $sub = collect([$item->description, $shipment?->shipment_number])
                    ->filter()
                    ->implode(' | ');

                return $this->result(
                    'item-' . $item->id,
                    $label,
                    $sub !== '' ? $sub : 'Package',
                    $item->delivery_recipient_phone ?: $this->statusLabel($shipment?->status),
                    $this->shipmentOrPackageTrackingUrl($shipment?->id, $label),
                );
            });

        return $labels->merge($items)->take(self::LIMIT)->values();
    }

    private function transactions(string $q, ?User $admin): Collection
    {
        return collect()
            ->merge($this->can($admin, 'charges.view') ? $this->shipmentPayments($q) : collect())
            ->merge($this->can($admin, 'charges.view') ? $this->shipmentCharges($q) : collect())
            ->merge($this->can($admin, 'recipient_payments.view') ? $this->recipientPayments($q) : collect())
            ->merge($this->can($admin, 'vendors.manage') ? $this->vendorPayouts($q) : collect())
            ->sortByDesc('created_at')
            ->take(self::LIMIT)
            ->map(fn (array $result) => collect($result)->except('created_at')->all())
            ->values();
    }

    private function shipmentPayments(string $q): Collection
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
                [$q, $q . '%'],
            )
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (ShipmentPayment $payment) => $this->transactionResult(
                'payment-' . $payment->id,
                $payment->reference_number ?: ('Payment #' . $payment->id),
                collect([$this->money($payment->amount), $payment->payment_method, $payment->shipment?->shipment_number])->filter()->implode(' | '),
                'Shipment payment',
                $this->shipmentUrl($payment->shipment_id, 'payments'),
                $payment->created_at,
            ));
    }

    private function shipmentCharges(string $q): Collection
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
                [$q, $q . '%'],
            )
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (ShipmentCharge $charge) => $this->transactionResult(
                'charge-' . $charge->id,
                $charge->payment_reference ?: ('Charge #' . $charge->id),
                collect([$this->money($charge->amount, $charge->currency), $charge->shipment?->shipment_number, $charge->shipmentItem?->tracking_code])->filter()->implode(' | '),
                $this->statusLabel($charge->charge_type) . ' | ' . $this->statusLabel($charge->status),
                $this->shipmentUrl($charge->shipment_id, 'charges'),
                $charge->created_at,
            ));
    }

    private function recipientPayments(string $q): Collection
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
                [$q, $q . '%'],
            )
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (RecipientPaymentTask $task) => $this->transactionResult(
                'recipient-payment-' . $task->id,
                $task->payment_reference ?: ('Recipient payment #' . $task->id),
                collect([$task->recipient_name, $task->recipient_phone, $task->shipmentItem?->tracking_code, $task->shipment?->shipment_number])->filter()->implode(' | '),
                $this->statusLabel($task->status),
                route('admin.recipient-payments.index', ['search' => $task->payment_reference ?: $task->recipient_phone]),
                $task->created_at,
            ));
    }

    private function vendorPayouts(string $q): Collection
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
                [$q, $q . '%'],
            )
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (VendorPayout $payout) => $this->transactionResult(
                'vendor-payout-' . $payout->id,
                $payout->payment_reference ?: ('Vendor payout #' . $payout->id),
                collect([$this->money($payout->amount), $payout->vendor?->business_name ?? $payout->vendor?->name, $payout->payment_phone])->filter()->implode(' | '),
                'Vendor payout | ' . $this->statusLabel($payout->status),
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

    private function vendors(string $q): Collection
    {
        $like = $this->like($q);

        return Vendor::query()
            ->where('name', 'like', $like)
            ->orWhere('business_name', 'like', $like)
            ->orWhere('phone', 'like', $like)
            ->latest()
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'business_name', 'phone'])
            ->map(fn (Vendor $vendor) => $this->result(
                $vendor->id,
                $vendor->business_name ?? $vendor->name,
                $vendor->phone ?? '-',
                null,
                route('admin.vendors.show', $vendor->id),
            ));
    }

    private function drivers(string $q): Collection
    {
        $like = $this->like($q);

        return Driver::query()
            ->where('name', 'like', $like)
            ->orWhere('phone', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->latest()
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'phone', 'is_active'])
            ->map(fn (Driver $driver) => $this->result(
                $driver->id,
                $driver->name,
                $driver->phone ?? '-',
                $driver->is_active ? 'Active' : 'Inactive',
                route('admin.drivers.show', $driver->id),
            ));
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
        if (!$shipmentId) {
            return route('admin.package-tracking.index');
        }

        $url = route('admin.shipments.show', $shipmentId);

        return $fragment ? $url . '#' . $fragment : $url;
    }

    private function like(string $q): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
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
        return trim($currency . ' ' . number_format((float) $amount, 2));
    }

    private function can(?User $user, string $permission): bool
    {
        return $user ? app(BackOfficeAccess::class)->canUsePermission($user, $permission) : false;
    }
}
