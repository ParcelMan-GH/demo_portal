<?php

use App\Enums\ShipmentDestinationMode;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Vendor;
use App\Services\Warehouse\PackageLabelHtmlRenderer;

test('package labels render the shared readable 80mm thermal layout', function () {
    $vendor = new Vendor([
        'name' => 'Ricky Stores',
        'phone' => '+233 24 602 2602',
    ]);

    $shipment = new Shipment([
        'shipment_number' => 'PCM-2026-00008',
        'destination_mode' => ShipmentDestinationMode::SINGLE,
        'pickup_contact_phone' => '+233 24 602 2602',
        'pickup_town' => 'Lapaz',
        'delivery_recipient_name' => 'Ama Mensah',
        'delivery_recipient_phone' => '+233 54 712 5464',
        'delivery_town' => 'Madina',
    ]);
    $shipment->setRelation('vendor', $vendor);
    $shipment->setRelation('pickupDistrict', null);
    $shipment->setRelation('pickupRegion', null);
    $shipment->setRelation('deliveryDistrict', null);
    $shipment->setRelation('deliveryRegion', null);

    $shipmentItem = new ShipmentItem([
        'description' => 'Medline',
        'tracking_code' => 'TRKZDGNJ1QI',
    ]);

    $labelCard = view('warehouse.receipts.partials.item-label', [
        'assignment' => null,
        'shipment' => $shipment,
        'shipmentItem' => $shipmentItem,
        'receiptItem' => null,
        'barcodeSvg' => '<svg role="img" aria-label="barcode"></svg>',
        'labelIndex' => 1,
        'labelsTotal' => 2,
        'labelBarcode' => 'TRKZDGNJ1QI-001',
    ])->render();

    $html = app(PackageLabelHtmlRenderer::class)->renderPage($labelCard, 'TRKZDGNJ1QI-001');

    foreach ([
        '@page { size: 80mm 120mm; margin: 0; }',
        'width: 80mm;',
        '.address-block-to .address-name',
        'font-size: 23px;',
        '.address-block-to .address-phone',
        'font-size: 19px;',
        '.barcode-text',
        'font-size: 18px;',
        'Ricky Stores',
        '+233 24 602 2602',
        'Ama Mensah',
        '+233 54 712 5464',
        'Madina',
        'PCM-2026-00008',
        'Medline',
        'TRKZDGNJ1QI-001',
        '1 of 2',
    ] as $needle) {
        expect($html)->toContain($needle);
    }
});
