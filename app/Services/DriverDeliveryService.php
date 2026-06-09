<?php

namespace App\Services;

use App\Models\DeliveryRun;
use App\Models\Driver;
use App\Models\PlatformSetting;
use App\Models\ShipmentCharge;
use App\Services\DeliveryDelayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class DriverDeliveryService
{
    public function __construct(
        private StorageService $storageService,
        private ?DeliveryDelayService $deliveryDelayService = null,
    ) {
    }

    public function list(Driver $driver, Request $request): array
    {
        $statuses = [
            DeliveryRun::STATUS_DRAFT,
            DeliveryRun::STATUS_ASSIGNED,
            DeliveryRun::STATUS_OUT_FOR_DELIVERY,
            DeliveryRun::STATUS_PARTIALLY_DELIVERED,
            DeliveryRun::STATUS_COMPLETED,
            DeliveryRun::STATUS_CANCELLED,
        ];
        $sortable = ['id', 'run_number', 'status', 'assigned_at', 'dispatched_at', 'completed_at', 'created_at', 'updated_at'];

        $validated = $request->validate([
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in($statuses)],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', Rule::in($sortable)],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = DeliveryRun::query()
            ->where('assigned_driver_id', $driver->id)
            ->with([
                'warehouse:id,name,code,address,latitude,longitude,contact_phone',
                'stops:id,delivery_run_id,recipient_name,recipient_phone,status,delivery_method,total_packages,town,landmark,gh_post_address,verification_code_sent_at,verification_code_expires_at,verification_attempts,max_attempts,verification_skipped,verification_skip_reason,verification_skipped_at,arrived_at,delivered_at,proof_photo_path,failure_reason,failure_notes,delivery_notes,handoff_courier_name,handoff_courier_phone,handoff_vehicle_number,bus_station_name,handoff_at',
            'items:id,delivery_run_id,delivery_run_stop_id,shipment_item_id,expected_quantity,delivered_quantity,status,notes,delivered_at,expected_delivery_at,expected_delivery_set_at,expected_delivery_set_by_driver_id,expected_delivery_set_by_user_id',
            'items.busHandoffConfirmation:id,delivery_run_item_id,status,source,target_type,target_phone,confirmation_code_sent_at,confirmed_at,reason_id',
            'items.busHandoffConfirmation.reason:id,label,type',
            'items.expectedDeliverySetByRider:id,name,phone',
            'items.expectedDeliverySetByUser:id,name',
            'items.delayEvents:id,delivery_run_item_id,delivery_delay_reason_id,reason_label,source,actor_driver_id,actor_user_id,old_expected_delivery_at,new_expected_delivery_at,created_at',
            'items.delayEvents.reason:id,label',
            'items.delayEvents.actorRider:id,name,phone',
            'items.delayEvents.actorUser:id,name',
            'items.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
                'items.shipmentItem.shipment:id,shipment_number,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            ]);

        if (!empty($validated['status'])) {
            $query->whereIn('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('run_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('stops', fn ($sq) => $sq
                        ->where('recipient_name', 'like', "%{$search}%")
                        ->orWhere('recipient_phone', 'like', "%{$search}%")
                    );
            });
        }

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $limit = (int) ($validated['limit'] ?? 15);
        $offset = (int) ($validated['offset'] ?? 0);

        $total = (clone $query)->count();
        $rows = $query
            ->orderBy($sortBy, $sortOrder)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (DeliveryRun $run) => $this->transformRun($run))
            ->values()
            ->all();

        $count = count($rows);
        $hasMore = ($offset + $count) < $total;

        return [
            'success' => true,
            'message' => 'Deliveries retrieved successfully.',
            'data' => [
                'deliveries' => $rows,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $total,
                    'has_more' => $hasMore,
                    'next_offset' => $hasMore ? $offset + $limit : null,
                    'current_page' => (int) floor($offset / $limit) + 1,
                    'last_page' => max((int) ceil($total / $limit), 1),
                    'per_page' => $limit,
                ],
            ],
        ];
    }

    public function show(Driver $driver, DeliveryRun $run): array
    {
        if ((int) $run->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Delivery run not found.', 'status' => 404];
        }

        $run->load([
            'warehouse:id,name,code,address,latitude,longitude,contact_phone',
            'stops:id,delivery_run_id,recipient_name,recipient_phone,status,delivery_method,total_packages,region_id,district_id,town,latitude,longitude,gh_post_address,landmark,verification_code_sent_at,verification_code_expires_at,verification_attempts,max_attempts,verification_skipped,verification_skip_reason,verification_skipped_at,arrived_at,delivered_at,delivery_latitude,delivery_longitude,proof_photo_path,failure_reason,failure_notes,delivery_notes,handoff_courier_name,handoff_courier_phone,handoff_vehicle_number,bus_station_name,handoff_at',
            'stops.region:id,name',
            'stops.district:id,name',
            'items:id,delivery_run_id,delivery_run_stop_id,shipment_item_id,expected_quantity,delivered_quantity,status,notes,delivered_at,expected_delivery_at,expected_delivery_set_at,expected_delivery_set_by_driver_id,expected_delivery_set_by_user_id',
            'items.busHandoffConfirmation:id,delivery_run_item_id,status,source,target_type,target_phone,confirmation_code_sent_at,confirmed_at,reason_id',
            'items.busHandoffConfirmation.reason:id,label,type',
            'items.expectedDeliverySetByRider:id,name,phone',
            'items.expectedDeliverySetByUser:id,name',
            'items.delayEvents:id,delivery_run_item_id,delivery_delay_reason_id,reason_label,source,actor_driver_id,actor_user_id,old_expected_delivery_at,new_expected_delivery_at,created_at',
            'items.delayEvents.reason:id,label',
            'items.delayEvents.actorRider:id,name,phone',
            'items.delayEvents.actorUser:id,name',
            'items.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            'items.shipmentItem.shipment:id,shipment_number,vendor_id,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            'items.shipmentItem.shipment.vendor:id,name,phone,business_name',
        ]);

        return [
            'success' => true,
            'message' => 'Delivery run retrieved successfully.',
            'data' => ['delivery' => $this->transformRun($run)],
            'status' => 200,
        ];
    }

    private function transformRun(DeliveryRun $run): array
    {
        // Check if this run's shipment is a direct delivery
        $isDirectDelivery = false;
        $firstItem = $run->items->first();
        if ($firstItem?->shipmentItem?->shipment) {
            $isDirectDelivery = $firstItem->shipmentItem->shipment->fulfillment_type?->isDirect() ?? false;
        }

        return [
            'id' => $run->id,
            'run_number' => $run->run_number,
            'status' => $run->status,
            'is_direct_delivery' => $isDirectDelivery,
            'allow_skip_verification' => (bool) PlatformSetting::getValue('delivery.allow_skip_verification', false),
            'warehouse' => $run->warehouse ? [
                'id' => $run->warehouse->id,
                'name' => $run->warehouse->name,
                'code' => $run->warehouse->code,
                'address' => $run->warehouse->address,
                'latitude' => $run->warehouse->latitude,
                'longitude' => $run->warehouse->longitude,
                'contact_phone' => $run->warehouse->contact_phone,
            ] : null,
            'timeline' => [
                'assigned' => ['at' => $run->assigned_at],
                'out_for_delivery' => ['at' => $run->dispatched_at],
                'completed' => ['at' => $run->completed_at],
            ],
            'stops' => $run->stops->map(function ($stop) use ($run) {
                $items = $run->items->where('delivery_run_stop_id', $stop->id)->values();
                $stationFeeMeta = $this->getStationFeeMetaForStop($stop->id);
                $deliveryFeeMeta = $stop->delivery_method === 'bus_handoff'
                    ? null
                    : $this->getDeliveryFeeMetaForStop($stop->id, $items);
                return [
                    'id' => $stop->id,
                    'recipient_name' => $stop->recipient_name,
                    'recipient_phone' => $stop->recipient_phone,
                    'status' => $stop->status,
                    'delivery_method' => $stop->delivery_method ?? 'direct',
                    'total_packages' => (int) $stop->total_packages,
                    'location' => [
                        'region' => $stop->region?->name,
                        'district' => $stop->district?->name,
                        'town' => $stop->town,
                        'latitude' => $stop->latitude,
                        'longitude' => $stop->longitude,
                        'gh_post_address' => $stop->gh_post_address,
                        'landmark' => $stop->landmark,
                    ],
                    'verification' => [
                        'code_sent_at' => $stop->verification_code_sent_at,
                        'code_expires_at' => $stop->verification_code_expires_at,
                        'attempts' => (int) $stop->verification_attempts,
                        'max_attempts' => (int) $stop->max_attempts,
                        'skipped' => (bool) $stop->verification_skipped,
                        'skip_reason' => $stop->verification_skip_reason,
                        'skipped_at' => $stop->verification_skipped_at,
                    ],
                    'timeline' => [
                        'arrived' => ['at' => $stop->arrived_at],
                        'delivered' => ['at' => $stop->delivered_at],
                    ],
                    'failure_reason' => $stop->failure_reason,
                    'failure_notes' => $stop->failure_notes,
                    'delivery_notes' => $stop->delivery_notes,
                    'delivery_fee' => $deliveryFeeMeta,
                    'handoff' => $stop->delivery_method === 'bus_handoff' ? [
                        'bus_station' => $stop->bus_station_name,
                        'courier_name' => $stop->handoff_courier_name,
                        'courier_phone' => $stop->handoff_courier_phone,
                        'vehicle_number' => $stop->handoff_vehicle_number,
                        'handed_off_at' => $stop->handoff_at,
                        'proof_photo_url' => $this->getStopProofPhotoUrl($stop),
                        'amount_paid' => $stationFeeMeta['amount_paid'] ?? null,
                        'currency' => $stationFeeMeta['currency'] ?? null,
                    ] : null,
                    'items' => $items->map(function ($item) {
                        $vendor = $item->shipmentItem?->shipment?->vendor;
                        $si = $item->shipmentItem;
                        $shipment = $si?->shipment;
                        $confirmation = Schema::hasTable('bus_handoff_confirmations') ? $item->busHandoffConfirmation : null;
                        return [
                            'id' => $item->id,
                            'delivery_run_item_id' => $item->id,
                            'shipment_item_id' => $item->shipment_item_id,
                            'shipment_number' => $shipment?->shipment_number,
                            'description' => $si?->description,
                            'tracking_code' => $si?->tracking_code,
                            'expected_quantity' => (int) $item->expected_quantity,
                            'delivered_quantity' => (int) $item->delivered_quantity,
                            'status' => $item->status,
                            'notes' => $item->notes,
                            'delivered_at' => $item->delivered_at,
                            'eta' => $this->delayService()->snapshot($item),
                            'recipient_name' => $si?->delivery_recipient_name ?? $shipment?->delivery_recipient_name,
                            'recipient_phone' => $si?->delivery_recipient_phone ?? $shipment?->delivery_recipient_phone,
                            'delivery_town' => $si?->delivery_town ?? $shipment?->delivery_town,
                            'vendor' => $vendor ? [
                                'name' => $vendor->name,
                                'phone' => $vendor->phone,
                                'business_name' => $vendor->business_name,
                            ] : null,
                            'bus_handoff_confirmation' => $confirmation ? [
                                'id' => $confirmation->id,
                                'status' => $confirmation->status,
                                'source' => $confirmation->source,
                                'target_type' => $confirmation->target_type,
                                'target_phone' => $confirmation->target_phone,
                                'code_sent_at' => $confirmation->confirmation_code_sent_at?->toIso8601String(),
                                'confirmed_at' => $confirmation->confirmed_at?->toIso8601String(),
                                'reason' => $confirmation->reason ? [
                                    'id' => $confirmation->reason->id,
                                    'label' => $confirmation->reason->label,
                                    'type' => $confirmation->reason->type,
                                ] : null,
                            ] : null,
                        ];
                    })->values(),
                ];
            })->values(),
            'notes' => $run->notes,
            'created_at' => $run->created_at,
            'updated_at' => $run->updated_at,
        ];
    }

    private function getStationFeeMetaForStop(?int $stopId): ?array
    {
        if (!$stopId) {
            return null;
        }

        $charges = ShipmentCharge::query()
            ->where('delivery_run_stop_id', $stopId)
            ->where('charge_type', ShipmentCharge::TYPE_STATION_FEE)
            ->where('direction', ShipmentCharge::DIRECTION_EXPENSE)
            ->whereNotIn('status', [
                ShipmentCharge::STATUS_CANCELLED,
                ShipmentCharge::STATUS_WAIVED,
            ])
            ->get(['amount', 'currency']);

        if ($charges->isEmpty()) {
            return null;
        }

        return [
            'amount_paid' => (float) $charges->sum(fn (ShipmentCharge $charge) => (float) $charge->amount),
            'currency' => $charges->first()?->currency ?? 'GHS',
        ];
    }

    /**
     * Summary of recipient-paid delivery fees tied to this direct-delivery stop.
     *
     * We look for item-level charges, shipment-level charges for shipments on
     * this stop, and charges already linked back to the stop itself.
     */
    private function getDeliveryFeeMetaForStop(?int $stopId, Collection $runItems): array
    {
        $shipmentIds = $runItems
            ->map(fn ($runItem) => (int) ($runItem->shipmentItem?->shipment_id ?? 0))
            ->filter()
            ->unique()
            ->values();

        $shipmentItemIds = $runItems
            ->map(fn ($runItem) => (int) ($runItem->shipment_item_id ?? 0))
            ->filter()
            ->unique()
            ->values();

        if (!$stopId && $shipmentIds->isEmpty() && $shipmentItemIds->isEmpty()) {
            return $this->emptyDeliveryFeeMeta();
        }

        $charges = ShipmentCharge::query()
            ->where('charge_type', ShipmentCharge::TYPE_DELIVERY_FEE)
            ->where('payer_type', ShipmentCharge::PAYER_RECIPIENT)
            ->whereIn('due_stage', [ShipmentCharge::STAGE_AT_DELIVERY, ShipmentCharge::STAGE_BEFORE_DELIVERY])
            ->whereNotIn('status', [ShipmentCharge::STATUS_CANCELLED])
            ->where(function ($query) use ($stopId, $shipmentIds, $shipmentItemIds) {
                $hasCondition = false;

                if ($stopId) {
                    $query->where('delivery_run_stop_id', $stopId);
                    $hasCondition = true;
                }

                if ($shipmentItemIds->isNotEmpty()) {
                    $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('shipment_item_id', $shipmentItemIds->all());
                    $hasCondition = true;
                }

                if ($shipmentIds->isNotEmpty()) {
                    $method = $hasCondition ? 'orWhere' : 'where';
                    $query->{$method}(function ($shipmentQuery) use ($shipmentIds) {
                        $shipmentQuery
                            ->whereNull('shipment_item_id')
                            ->whereIn('shipment_id', $shipmentIds->all());
                    });
                }
            })
            ->get([
                'id',
                'amount',
                'currency',
                'status',
                'paid_at',
                'payment_method',
                'notes',
            ]);

        if ($charges->isEmpty()) {
            return $this->emptyDeliveryFeeMeta();
        }

        $currency = $charges->first()?->currency ?: 'GHS';
        $paidAmount = (float) $charges
            ->where('status', ShipmentCharge::STATUS_PAID)
            ->sum('amount');
        $outstandingAmount = (float) $charges
            ->whereIn('status', [ShipmentCharge::STATUS_DRAFT, ShipmentCharge::STATUS_PENDING])
            ->sum('amount');
        $waivedAmount = (float) $charges
            ->where('status', ShipmentCharge::STATUS_WAIVED)
            ->sum('amount');
        $status = match (true) {
            $outstandingAmount > 0 && $paidAmount > 0 => 'partially_paid',
            $outstandingAmount > 0 => 'collect',
            $paidAmount > 0 => 'paid',
            $waivedAmount > 0 => 'waived',
            default => 'none',
        };
        $latestPaidCharge = $charges
            ->where('status', ShipmentCharge::STATUS_PAID)
            ->sortByDesc(fn (ShipmentCharge $charge) => $charge->paid_at?->getTimestamp() ?? 0)
            ->first();

        return [
            'status' => $status,
            'currency' => $currency,
            'total_amount' => round((float) $charges->sum('amount'), 2),
            'paid_amount' => round($paidAmount, 2),
            'outstanding_amount' => round($outstandingAmount, 2),
            'is_paid' => $outstandingAmount <= 0 && $paidAmount > 0,
            'can_capture_amount' => in_array($status, ['none', 'collect', 'partially_paid'], true),
            'paid_at' => $latestPaidCharge?->paid_at?->toIso8601String(),
            'payment_method' => $latestPaidCharge?->payment_method,
            'notes' => $charges->pluck('notes')->filter()->first(),
        ];
    }

    private function emptyDeliveryFeeMeta(): array
    {
        return [
            'status' => 'none',
            'currency' => 'GHS',
            'total_amount' => 0.0,
            'paid_amount' => 0.0,
            'outstanding_amount' => 0.0,
            'is_paid' => false,
            'can_capture_amount' => true,
            'paid_at' => null,
            'payment_method' => null,
            'notes' => null,
        ];
    }

    private function getStopProofPhotoUrl(object $stop): ?string
    {
        if (blank($stop->proof_photo_path)) {
            return null;
        }

        return $this->storageService->getUrl($stop->proof_photo_path);
    }

    private function delayService(): DeliveryDelayService
    {
        return $this->deliveryDelayService ??= app(DeliveryDelayService::class);
    }
}
