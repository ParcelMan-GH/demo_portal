<?php

use App\Models\TransportContainer;
use App\Models\TransportContainerItem;
use App\Models\TransportManifest;
use App\Models\Warehouse;
use App\Services\Warehouse\BarcodeService;

test('transport container label uses the approved 80mm portrait layout', function () {
    $origin = new Warehouse(['name' => 'Accra Main']);
    $destination = new Warehouse(['name' => 'Kumasi Center']);

    $manifest = new TransportManifest([
        'manifest_number' => 'TM-2026-WH001-WH002-0001',
    ]);
    $manifest->setRelation('originWarehouse', $origin);
    $manifest->setRelation('destinationWarehouse', $destination);

    $container = new TransportContainer([
        'container_code' => 'TM-2026-WH001-WH002-0001-C01',
        'container_type' => 'mixed',
        'sequence_number' => 1,
    ]);
    $container->setRelation('items', collect([
        new TransportContainerItem(['expected_quantity' => 1]),
        new TransportContainerItem(['expected_quantity' => 3]),
    ]));

    $barcodeSvg = app(BarcodeService::class)->renderCode128Svg(
        $container->container_code,
        210,
        2,
        10,
        false
    );

    $html = view('shared.transport-container-label', compact(
        'manifest',
        'container',
        'barcodeSvg'
    ))->render();

    expect($html)
        ->toContain('@page { size: 80mm 120mm; margin: 0; }')
        ->toContain('grid-template-rows: 16mm 43mm 28.5mm 20mm 12.5mm;')
        ->toContain('TM-2026-WH001-WH002-0001-C01')
        ->toContain('Accra Main -&gt; Kumasi Center')
        ->toContain('TM-2026-WH001-WH002-0001')
        ->toContain('Scan at each warehouse handoff')
        ->toContain('<div class="stat-value">2</div>')
        ->toContain('<div class="stat-value">4</div>')
        ->toContain('<div class="footer-sequence-value">C01</div>')
        ->not->toContain('size: 4in 3in')
        ->not->toContain('<text ');
});

test('transport container label compacts long warehouse routes', function () {
    $manifest = new TransportManifest([
        'manifest_number' => 'TM-2026-WH001-WH002-0001',
    ]);
    $manifest->setRelation('originWarehouse', new Warehouse([
        'name' => 'Greater Accra Regional Fulfilment Warehouse',
    ]));
    $manifest->setRelation('destinationWarehouse', new Warehouse([
        'name' => 'Ashanti Regional Distribution Centre',
    ]));

    $container = new TransportContainer([
        'container_code' => 'TM-2026-WH001-WH002-0001-C01',
        'container_type' => 'oversized_freight',
        'sequence_number' => 1,
    ]);
    $container->setRelation('items', collect());

    $barcodeSvg = app(BarcodeService::class)->renderCode128Svg(
        $container->container_code,
        210,
        2,
        10,
        false
    );

    $html = view('shared.transport-container-label', compact(
        'manifest',
        'container',
        'barcodeSvg'
    ))->render();

    expect($html)
        ->toContain('route-value route-value--compact')
        ->toContain('Greater Accra Regional Fulfilment Warehouse')
        ->toContain('Ashanti Regional Distribution Centre')
        ->toContain('Manifest&nbsp;&nbsp;TM-2026-WH001-WH002-0001');
});
