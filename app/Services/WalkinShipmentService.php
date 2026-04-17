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
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use Illuminate\Support\Facades\DB;

class WalkinShipmentService
{
    public function lookupVendor(string $phone): ?Vendor
    {
        $formatted = PhoneHelper::format($phone);

        if (!$formatted) {
            return null;
        }

        return Vendor::where('phone', $formatted)->first();
    }

    public function createVendorInline(array $data): Vendor
    {
        return Vendor::create([
            'name'          => $data['name'],
            'business_name' => $data['business_name'] ?? null,
            'phone'         => PhoneHelper::format($data['phone']),
            'email'         => $data['email'] ?? null,
            'is_active'     => true,
        ]);
    }

    public function createWalkinShipment(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $vendor    = Vendor::findOrFail($data['vendor_id']);
            $source    = ShipmentSource::from($data['source']);
            $destMode  = $data['destination_mode'] === 'per_item'
                ? ShipmentDestinationMode::PER_ITEM
                : ShipmentDestinationMode::SINGLE;

            $fulfillmentType = FulfillmentType::tryFrom($data['fulfillment_type'] ?? 'warehouse') ?? FulfillmentType::WAREHOUSE;

            // Build shipment attributes
            $shipmentData = [
                'vendor_id'           => $vendor->id,
                'status'              => ShipmentStatus::AT_WAREHOUSE,
                'source'              => $source,
                'fulfillment_type'    => $fulfillmentType,
                'created_by_user_id'  => $data['created_by_user_id'],
                'destination_mode'    => $destMode,
                'submitted_at'        => now(),
                // Pickup location = warehouse address
                'pickup_contact_name'  => $vendor->name,
                'pickup_contact_phone' => $vendor->phone,
            ];

            // For single destination mode, set shipment-level delivery fields
            if ($destMode === ShipmentDestinationMode::SINGLE && !empty($data['delivery'])) {
                $delivery = $data['delivery'];
                $shipmentData['delivery_recipient_name']  = $delivery['recipient_name'] ?? null;
                $shipmentData['delivery_recipient_phone'] = $delivery['recipient_phone'] ?? null;
                $shipmentData['delivery_region_id']       = $delivery['region_id'] ?? null;
                $shipmentData['delivery_district_id']     = $delivery['district_id'] ?? null;
                $shipmentData['delivery_town']            = $delivery['town'] ?? null;
                $shipmentData['delivery_landmark']        = $delivery['landmark'] ?? null;
                $shipmentData['delivery_instructions']    = $delivery['instructions'] ?? null;
            }

            $shipment = Shipment::create($shipmentData);

            // Create warehouse receipt (finalized immediately)
            $receipt = WarehouseReceipt::create([
                'shipment_id'         => $shipment->id,
                'warehouse_id'        => $warehouse->id,
                'status'              => WarehouseReceipt::STATUS_FINALIZED,
                'started_by_user_id'  => $data['created_by_user_id'],
                'finalized_by_user_id'=> $data['created_by_user_id'],
                'notes'               => 'Walk-in vendor delivery — received directly at warehouse.',
                'started_at'          => now(),
                'finalized_at'        => now(),
            ]);

            // Create shipment items + receipt items + tracking
            foreach ($data['items'] as $itemData) {
                $trackingCode = ShipmentItem::generateTrackingCode();

                $itemAttrs = [
                    'shipment_id'  => $shipment->id,
                    'description'  => $itemData['description'],
                    'quantity'     => $itemData['quantity'],
                    'status'       => ItemStatus::AT_WAREHOUSE,
                    'tracking_code'=> $trackingCode,
                ];

                // Per-item delivery fields
                if ($destMode === ShipmentDestinationMode::PER_ITEM && !empty($itemData['delivery'])) {
                    $del = $itemData['delivery'];
                    $itemAttrs['delivery_recipient_name']  = $del['recipient_name'] ?? null;
                    $itemAttrs['delivery_recipient_phone'] = $del['recipient_phone'] ?? null;
                    $itemAttrs['delivery_region_id']       = $del['region_id'] ?? null;
                    $itemAttrs['delivery_district_id']     = $del['district_id'] ?? null;
                    $itemAttrs['delivery_town']            = $del['town'] ?? null;
                    $itemAttrs['delivery_landmark']        = $del['landmark'] ?? null;
                    $itemAttrs['delivery_instructions']    = $del['instructions'] ?? null;
                }

                $shipmentItem = ShipmentItem::create($itemAttrs);

                // Warehouse receipt item
                WarehouseReceiptItem::create([
                    'warehouse_receipt_id' => $receipt->id,
                    'shipment_item_id'     => $shipmentItem->id,
                    'expected_quantity'     => $shipmentItem->quantity,
                    'received_quantity'     => $shipmentItem->quantity,
                    'damaged_quantity'      => 0,
                    'discrepancy_type'      => 'none',
                    'condition_status'      => 'ok',
                    'barcode_value'         => $trackingCode,
                    'barcode_format'        => 'code128',
                    'received_by_user_id'   => $data['created_by_user_id'],
                    'received_at'           => now(),
                ]);

                // Tracking entry
                ShipmentItemTracking::create([
                    'shipment_item_id' => $shipmentItem->id,
                    'status'           => 'at_warehouse',
                    'location'         => $warehouse->name,
                    'notes'            => 'Walk-in: received directly at ' . $warehouse->name,
                    'created_by'       => $data['created_by_user_id'],
                    'created_at'       => now(),
                ]);
            }

            // Fire event
            $user = \App\Models\User::find($data['created_by_user_id']);
            event(new WalkinShipmentReceived($shipment, $warehouse, $user));

            // For self-pickup walk-ins: auto-mark ready for collection
            if ($fulfillmentType->isSelfPickup()) {
                app(ShipmentCollectionService::class)->markReadyForCollection($shipment, $warehouse);
            }


            return [
                'shipment' => $shipment->fresh(['vendor', 'items']),
                'receipt'  => $receipt,
            ];
        });
    }
}
