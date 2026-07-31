<?php

namespace App\Services;

use App\Enums\FulfillmentType;
use App\Enums\ItemStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Enums\ShipmentStatus;
use App\Events\WalkinShipmentReceived;
use App\Helpers\PhoneHelper;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemPhoto;
use App\Services\Warehouse\BarcodeService;
use App\Services\Warehouse\PackageLabelHtmlRenderer;
use App\Services\Warehouse\WarehouseSortingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class WalkinShipmentService
{
    public function __construct(
        private StorageService $storageService,
        private ChargesService $chargesService,
        private PackageLabelHtmlRenderer $labelHtmlRenderer,
    ) {}

    public function lookupVendor(string $phone): ?Vendor
    {
        $formatted = PhoneHelper::format($phone);

        if (! $formatted) {
            return null;
        }

        return Vendor::where('phone', $formatted)->first();
    }

    public function createVendorInline(array $data): Vendor
    {
        return Vendor::create([
            'name' => $data['name'],
            'business_name' => $data['business_name'] ?? null,
            'phone' => PhoneHelper::format($data['phone']),
            'email' => $data['email'] ?? null,
            'is_active' => true,
        ]);
    }

    public function createWalkinShipment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $vendor = Vendor::findOrFail($data['vendor_id']);
            $source = ShipmentSource::from($data['source']);
            $destMode = $data['destination_mode'] === 'per_item'
                ? ShipmentDestinationMode::PER_ITEM
                : ShipmentDestinationMode::SINGLE;

            $fulfillmentType = FulfillmentType::tryFrom($data['fulfillment_type'] ?? 'warehouse') ?? FulfillmentType::WAREHOUSE;
            $deliveryPreference = $data['delivery_preference'] ?? 'deliver';

            // Build shipment attributes
            $shipmentData = [
                'vendor_id' => $vendor->id,
                'status' => ShipmentStatus::AT_WAREHOUSE,
                'source' => $source,
                'fulfillment_type' => $fulfillmentType,
                'delivery_preference' => $deliveryPreference,
                'created_by_user_id' => $data['created_by_user_id'],
                'destination_mode' => $destMode,
                'submitted_at' => now(),
                // Pickup location = warehouse address
                'pickup_contact_name' => $vendor->name,
                'pickup_contact_phone' => $vendor->phone,
            ];

            // For single destination mode, set shipment-level delivery fields
            if ($destMode === ShipmentDestinationMode::SINGLE && ! empty($data['delivery'])) {
                $delivery = $data['delivery'];
                $shipmentData['delivery_recipient_name'] = $delivery['recipient_name'] ?? null;
                $shipmentData['delivery_recipient_phone'] = $delivery['recipient_phone'] ?? null;
                $shipmentData['delivery_region_id'] = $delivery['region_id'] ?? null;
                $shipmentData['delivery_district_id'] = $delivery['district_id'] ?? null;
                $shipmentData['delivery_town'] = $delivery['town'] ?? null;
                $shipmentData['delivery_landmark'] = $delivery['landmark'] ?? null;
                $shipmentData['delivery_instructions'] = $delivery['instructions'] ?? null;
            }

            $user = User::findOrFail($data['created_by_user_id']);
            $shipment = Shipment::create($shipmentData);
            $pickupFeeCharge = null;
            $pickupFeeAmount = filled($data['pickup_fee_amount'] ?? null)
                ? round((float) $data['pickup_fee_amount'], 2)
                : 0.0;

            if ($pickupFeeAmount > 0) {
                $pickupFeeCharge = $this->chargesService->addCharge($shipment, [
                    'charge_type' => ShipmentCharge::TYPE_PICKUP_FEE,
                    'payer_type' => ShipmentCharge::PAYER_VENDOR,
                    'due_stage' => ShipmentCharge::STAGE_AT_PICKUP,
                    'amount' => $pickupFeeAmount,
                    'status' => ShipmentCharge::STATUS_PENDING,
                    'notes' => 'Walk-in counter charge recorded at shipment creation.',
                ], $user);
            }

            // Create warehouse receipt (finalized immediately)
            $receipt = WarehouseReceipt::create([
                'shipment_id' => $shipment->id,
                'warehouse_id' => $warehouse->id,
                'status' => WarehouseReceipt::STATUS_FINALIZED,
                'started_by_user_id' => $data['created_by_user_id'],
                'finalized_by_user_id' => $data['created_by_user_id'],
                'notes' => 'Walk-in vendor delivery — received directly at warehouse.',
                'started_at' => now(),
                'finalized_at' => now(),
            ]);

            $transferRoutes = collect();

            // Create shipment items + receipt items + tracking
            foreach ($data['items'] as $itemIndex => $itemData) {
                $trackingCode = ShipmentItem::generateTrackingCode();
                $deliveryMethod = $itemData['delivery_method'] ?? ShipmentItem::DELIVERY_METHOD_DIRECT;
                if (! in_array($deliveryMethod, ShipmentItem::DELIVERY_METHODS, true)) {
                    $deliveryMethod = ShipmentItem::DELIVERY_METHOD_DIRECT;
                }

                // Explicitly pull fee regardless of key format passed
                $rawFee = $itemData['delivery_fee'] 
                    ?? $itemData['price'] 
                    ?? $itemData['fee'] 
                    ?? $itemData['amount'] 
                    ?? null;

                $itemAttrs = [
                    'shipment_id' => $shipment->id,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'delivery_fee' => filled($rawFee) ? round((float) $rawFee, 2) : 0.00,
                    'fulfillment_type' => $fulfillmentType,
                    'delivery_preference' => $itemData['delivery_preference'] ?? $deliveryPreference,
                    'delivery_method' => $deliveryMethod,
                    'status' => ItemStatus::AT_WAREHOUSE,
                    'tracking_code' => $trackingCode,
                ];

                // Keep item-level delivery details populated so warehouse screens, sorting,
                // and delivery runs can work from the package record directly.
                $del = null;
                if ($destMode === ShipmentDestinationMode::PER_ITEM && ! empty($itemData['delivery'])) {
                    $del = $itemData['delivery'];
                } elseif ($destMode === ShipmentDestinationMode::SINGLE && ! empty($data['delivery'])) {
                    $del = $data['delivery'];
                }

                if ($del) {
                    $itemAttrs['delivery_recipient_name'] = $del['recipient_name'] ?? null;
                    $itemAttrs['delivery_recipient_phone'] = $del['recipient_phone'] ?? null;
                    $itemAttrs['delivery_region_id'] = $del['region_id'] ?? null;
                    $itemAttrs['delivery_district_id'] = $del['district_id'] ?? null;
                    $itemAttrs['delivery_town'] = $del['town'] ?? null;
                    $itemAttrs['delivery_landmark'] = $del['landmark'] ?? null;
                    $itemAttrs['delivery_instructions'] = $del['instructions'] ?? null;
                }

                $shipmentItem = ShipmentItem::create($itemAttrs);

                // Warehouse receipt item
                $receiptItem = WarehouseReceiptItem::create([
                    'warehouse_receipt_id' => $receipt->id,
                    'shipment_item_id' => $shipmentItem->id,
                    'expected_quantity' => $shipmentItem->quantity,
                    'received_quantity' => $shipmentItem->quantity,
                    'damaged_quantity' => 0,
                    'discrepancy_type' => 'none',
                    'condition_status' => 'ok',
                    'barcode_value' => $trackingCode,
                    'barcode_format' => 'code128',
                    'received_by_user_id' => $data['created_by_user_id'],
                    'received_at' => now(),
                ]);

                if (! empty($itemData['forward_to_warehouse_id']) && (int) $itemData['forward_to_warehouse_id'] !== (int) $warehouse->id) {
                    $transferRoutes->push([
                        'warehouse_receipt_item_id' => $receiptItem->id,
                        'destination_warehouse_id' => (int) $itemData['forward_to_warehouse_id'],
                    ]);
                }

                foreach (($data['item_photos'][$itemIndex] ?? []) as $photo) {
                    if (! $photo) {
                        continue;
                    }

                    $storedPhoto = $this->storageService->upload($photo, 'warehouse-receipts/'.$receipt->id);
                    WarehouseReceiptItemPhoto::create([
                        'warehouse_receipt_item_id' => $receiptItem->id,
                        'path' => $storedPhoto['path'],
                        'original_name' => $storedPhoto['original_name'],
                        'size' => $storedPhoto['size'],
                        'photo_type' => 'proof',
                        'created_by_user_id' => $data['created_by_user_id'],
                    ]);
                }

                // Tracking entry
                ShipmentItemTracking::create([
                    'shipment_item_id' => $shipmentItem->id,
                    'status' => 'at_warehouse',
                    'location' => $warehouse->name,
                    'notes' => 'Walk-in: received directly at '.$warehouse->name,
                    'created_by' => $data['created_by_user_id'],
                    'created_at' => now(),
                ]);
            }

            $autoRouting = app(WarehouseSortingService::class)->autoRouteReceiptItemsToDestinationBatches(
                $transferRoutes,
                $warehouse,
                $user
            );

            // Fire event
            event(new WalkinShipmentReceived($shipment, $warehouse, $user));

            // For self-pickup walk-ins: auto-mark ready for collection
            if ($fulfillmentType->isSelfPickup()) {
                app(ShipmentCollectionService::class)->markReadyForCollection($shipment, $warehouse);
            }

            return [
                'shipment' => $shipment->fresh(['vendor', 'items']),
                'receipt' => $receipt,
                'auto_routing' => $autoRouting,
                'pickup_fee_charge' => $pickupFeeCharge,
            ];
        });
    }

    public function printWalkinItemLabel(ShipmentItem $shipmentItem, Warehouse $warehouse, User $user, int $labelCount = 1): array
    {
        $result = $this->printWalkinItemLabels([
            [
                'shipment_item_id' => $shipmentItem->id,
                'label_count' => $labelCount,
            ],
        ], $warehouse, $user);

        if (($result['success'] ?? false) && ! empty($result['data']['packages'][0])) {
            $package = $result['data']['packages'][0];
            $result['data']['barcode_value'] = $package['barcode_value'];
            $result['data']['barcode_format'] = 'code128';
            $result['data']['label_count'] = $package['label_count'];
            $result['data']['print_count'] = $package['print_count'];
        }

        return $result;
    }

    public function printWalkinItemLabels(array $packages, Warehouse $warehouse, User $user): array
    {
        $requests = collect($packages)
            ->map(fn (array $package) => [
                'shipment_item_id' => (int) ($package['shipment_item_id'] ?? 0),
                'label_count' => max(1, min(500, (int) ($package['label_count'] ?? 1))),
            ])
            ->filter(fn (array $package) => $package['shipment_item_id'] > 0)
            ->unique('shipment_item_id')
            ->values();

        if ($requests->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Select at least one walk-in package to print.',
            ];
        }

        $totalLabels = $requests->sum('label_count');
        if ($totalLabels > 500) {
            return [
                'success' => false,
                'message' => 'Print 500 labels or fewer at a time.',
            ];
        }

        return DB::transaction(function () use ($requests, $warehouse, $totalLabels) {
            $receiptItems = WarehouseReceiptItem::query()
                ->whereIn('shipment_item_id', $requests->pluck('shipment_item_id')->all())
                ->whereHas('receipt', fn ($query) => $query->where('warehouse_id', $warehouse->id))
                ->orderByDesc('id')
                ->get()
                ->unique('shipment_item_id')
                ->keyBy('shipment_item_id');

            $labelCards = '';
            $printedPackages = [];

            foreach ($requests as $request) {
                $receiptItem = $receiptItems->get($request['shipment_item_id']);

                if (! $receiptItem) {
                    return [
                        'success' => false,
                        'message' => 'Save each walk-in package before printing labels.',
                    ];
                }

                $lockedItem = ShipmentItem::query()->lockForUpdate()->findOrFail($request['shipment_item_id']);
                if (empty($lockedItem->tracking_code)) {
                    $lockedItem->update(['tracking_code' => ShipmentItem::generateTrackingCode()]);
                    $lockedItem->refresh();
                }

                $lockedReceiptItem = WarehouseReceiptItem::query()->lockForUpdate()->findOrFail($receiptItem->id);
                $parentBarcode = $lockedItem->tracking_code;
                $labelCount = $request['label_count'];
                $labelType = $labelCount === 1 ? 'sealed' : 'unit';

                $lockedReceiptItem->update([
                    'barcode_value' => $parentBarcode,
                    'barcode_format' => 'code128',
                    'barcode_printed_at' => now(),
                    'barcode_print_count' => (int) $lockedReceiptItem->barcode_print_count + 1,
                ]);

                $lockedReceiptItem->labels()->delete();

                $labels = [];
                for ($i = 1; $i <= $labelCount; $i++) {
                    $labels[] = $lockedReceiptItem->labels()->create([
                        'barcode_value' => $parentBarcode.'-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                        'label_index' => $i,
                        'labels_total' => $labelCount,
                        'label_type' => $labelType,
                        'printed_at' => now(),
                        'print_count' => 1,
                    ]);
                }

                $shipment = $lockedItem->shipment()->with([
                    'vendor:id,name,business_name',
                    'pickupRegion:id,name',
                    'pickupDistrict:id,name',
                    'deliveryRegion:id,name',
                    'deliveryDistrict:id,name',
                ])->first();

                $lockedItem->load(['deliveryRegion:id,name', 'deliveryDistrict:id,name']);
                $freshReceiptItem = $lockedReceiptItem->fresh();

                foreach ($labels as $label) {
                    $barcodeSvg = app(BarcodeService::class)->renderCode128Svg($label->barcode_value);
                    $labelCards .= View::make('warehouse.receipts.partials.item-label', [
                        'assignment' => null,
                        'shipment' => $shipment,
                        'shipmentItem' => $lockedItem,
                        'receiptItem' => $freshReceiptItem,
                        'barcodeSvg' => $barcodeSvg,
                        'labelIndex' => $label->label_index,
                        'labelsTotal' => $label->labels_total,
                        'labelBarcode' => $label->barcode_value,
                    ])->render();
                }

                $printedPackages[] = [
                    'shipment_item_id' => $lockedItem->id,
                    'barcode_value' => $parentBarcode,
                    'label_count' => $labelCount,
                    'print_count' => (int) $freshReceiptItem->barcode_print_count,
                    'labels' => collect($labels)->map(fn ($label) => [
                        'id' => $label->id,
                        'barcode_value' => $label->barcode_value,
                        'label_index' => $label->label_index,
                        'labels_total' => $label->labels_total,
                    ])->values(),
                ];
            }

            return [
                'success' => true,
                'message' => $requests->count() === 1
                    ? $totalLabels.' label(s) generated.'
                    : $totalLabels.' label(s) generated for '.$requests->count().' package(s).',
                'data' => [
                    'label_count' => $totalLabels,
                    'packages' => $printedPackages,
                    'label_html' => $this->labelHtmlRenderer->renderPage($labelCards, 'Walk-in Labels'),
                ],
            ];
        });
    }
}