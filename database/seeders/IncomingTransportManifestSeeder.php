<?php

namespace Database\Seeders;

use App\Enums\FulfillmentType;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Models\Driver;
use App\Models\Role;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItemImage;
use App\Models\SortBatch;
use App\Models\TransportManifest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemPhoto;
use App\Services\ChargesService;
use App\Services\StorageService;
use App\Services\WalkinShipmentService;
use App\Services\Warehouse\WarehouseSortingService;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class IncomingTransportManifestSeeder extends Seeder
{
    private const FIXTURE_PREFIX = 'incoming-accra-fixture';

    public function run(): void
    {
        $accra = $this->warehouse('WH-001');
        $kumasi = $this->warehouse('WH-002');
        $tema = $this->warehouse('WH-003');
        $user = $this->seedUser($accra);
        $driver = $this->seedDriver();

        $fixtures = [
            [
                'key' => 'kumasi-in-transit-01',
                'origin' => $kumasi,
                'status' => 'in_transit',
                'container_type' => 'box',
                'items' => [
                    ['description' => 'Cosmetics cartons', 'quantity' => 2, 'recipient' => 'Ama Mensah', 'phone' => '0241007001', 'town' => 'Osu'],
                    ['description' => 'Mobile accessories', 'quantity' => 1, 'recipient' => 'Kojo Asare', 'phone' => '0241007002', 'town' => 'Madina'],
                    ['description' => 'Shoes package', 'quantity' => 1, 'recipient' => 'Nana Boateng', 'phone' => '0241007003', 'town' => 'Achimota'],
                ],
            ],
            [
                'key' => 'tema-in-transit-01',
                'origin' => $tema,
                'status' => 'in_transit',
                'container_type' => 'loose',
                'items' => [
                    ['description' => 'Pharmacy supplies', 'quantity' => 1, 'recipient' => 'Esi Owusu', 'phone' => '0241007011', 'town' => 'Dansoman'],
                    ['description' => 'Printed documents', 'quantity' => 1, 'recipient' => 'Kwame Dela', 'phone' => '0241007012', 'town' => 'Ridge'],
                ],
            ],
            [
                'key' => 'kumasi-arrived-01',
                'origin' => $kumasi,
                'status' => 'arrived',
                'container_type' => 'box',
                'items' => [
                    ['description' => 'Fashion bags', 'quantity' => 2, 'recipient' => 'Akua Sarpong', 'phone' => '0241007021', 'town' => 'East Legon'],
                    ['description' => 'Laptop stand', 'quantity' => 1, 'recipient' => 'Yaw Frimpong', 'phone' => '0241007022', 'town' => 'Spintex'],
                    ['description' => 'Kitchen set', 'quantity' => 1, 'recipient' => 'Abena Darko', 'phone' => '0241007023', 'town' => 'Lapaz'],
                ],
            ],
            [
                'key' => 'tema-arrived-01',
                'origin' => $tema,
                'status' => 'arrived',
                'container_type' => 'crate',
                'items' => [
                    ['description' => 'Office stationery', 'quantity' => 1, 'recipient' => 'Kofi Annan', 'phone' => '0241007031', 'town' => 'Airport'],
                    ['description' => 'Beauty products', 'quantity' => 2, 'recipient' => 'Mavis Tetteh', 'phone' => '0241007032', 'town' => 'Kaneshie'],
                ],
            ],
        ];

        foreach ($fixtures as $fixture) {
            $tag = self::FIXTURE_PREFIX . ':' . $fixture['key'];

            $existing = TransportManifest::query()->where('notes', 'like', "%{$tag}%")->first();
            if ($existing) {
                $this->attachFixturePhotosToManifest($existing, $tag, $user);
                $this->command?->warn("Skipped {$fixture['key']} because it already exists; photos backfilled.");
                continue;
            }

            $manifest = $this->createTransferFixture(
                tag: $tag,
                origin: $fixture['origin'],
                destination: $accra,
                user: $user,
                driver: $driver,
                items: $fixture['items'],
                targetStatus: $fixture['status'],
                containerType: $fixture['container_type'],
            );

            $this->command?->info("Seeded {$manifest->manifest_number} ({$fixture['status']}) from {$fixture['origin']->name} to {$accra->name}.");
        }
    }

    /**
     * @param array<int, array{description:string, quantity:int, recipient:string, phone:string, town:string}> $items
     */
    private function createTransferFixture(
        string $tag,
        Warehouse $origin,
        Warehouse $destination,
        User $user,
        Driver $driver,
        array $items,
        string $targetStatus,
        string $containerType,
    ): TransportManifest {
        $vendor = $this->seedVendor($origin);
        $walkin = app(WalkinShipmentService::class);
        $sorting = app(WarehouseSortingService::class);
        $transport = app(WarehouseTransportService::class);
        $charges = app(ChargesService::class);

        $shipmentResult = $walkin->createWalkinShipment([
            'warehouse_id' => $origin->id,
            'vendor_id' => $vendor->id,
            'created_by_user_id' => $user->id,
            'source' => ShipmentSource::WAREHOUSE_WALKIN->value,
            'fulfillment_type' => FulfillmentType::WAREHOUSE->value,
            'delivery_preference' => 'deliver',
            'destination_mode' => ShipmentDestinationMode::PER_ITEM->value,
            'items' => collect($items)->map(fn (array $item) => [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'delivery_method' => 'direct',
                'delivery_preference' => 'deliver',
                'delivery' => [
                    'recipient_name' => $item['recipient'],
                    'recipient_phone' => $item['phone'],
                    'town' => $item['town'],
                    'landmark' => 'Accra incoming fixture',
                ],
            ])->all(),
        ]);

        $shipment = $shipmentResult['shipment'];
        $receipt = $shipmentResult['receipt'];

        $receiptItems = WarehouseReceiptItem::query()
            ->where('warehouse_receipt_id', $receipt->id)
            ->with('shipmentItem.shipment')
            ->get();

        foreach ($receiptItems as $receiptItem) {
            $walkin->printWalkinItemLabel($receiptItem->shipmentItem, $origin, $user);

            $charges->addCharge($receiptItem->shipmentItem->shipment, [
                'shipment_item_id' => $receiptItem->shipment_item_id,
                'charge_type' => ShipmentCharge::TYPE_DELIVERY_FEE,
                'payer_type' => ShipmentCharge::PAYER_RECIPIENT,
                'due_stage' => ShipmentCharge::STAGE_BEFORE_DELIVERY,
                'amount' => 25.00,
                'status' => ShipmentCharge::STATUS_PAID,
                'payment_method' => 'seed',
                'payment_reference' => strtoupper(str_replace(':', '-', $tag)),
                'notes' => 'Paid by incoming manifest fixture seeder.',
            ], $user);
        }

        $this->attachFixturePhotosToReceiptItems($receiptItems, $tag, $user);

        $batchResult = $sorting->createBatch(
            originWarehouse: $origin,
            destinationWarehouse: $destination,
            user: $user,
            dispatchMode: SortBatch::DISPATCH_TRANSFER,
            notes: "Seed fixture {$tag}"
        );
        $this->ensureSuccess($batchResult, 'create sort batch');

        /** @var SortBatch $batch */
        $batch = $batchResult['data']['batch'];
        $this->ensureSuccess(
            $sorting->addItems($batch, $origin, $user, $receiptItems->pluck('id')->all()),
            'add items to sort batch'
        );
        $this->ensureSuccess($sorting->sealBatch($batch->fresh(), $origin, $user), 'seal sort batch');

        $manifestResult = $transport->createDraftManifest($origin, $user);
        $this->ensureSuccess($manifestResult, 'create draft transfer');

        /** @var TransportManifest $manifest */
        $manifest = $manifestResult['data']['manifest'];
        $containerResult = $transport->createContainer(
            manifest: $manifest,
            warehouse: $origin,
            user: $user,
            containerType: $containerType,
            notes: "Fixture container for {$tag}",
            sortBatch: $batch->fresh(['activeItems.shipmentItem', 'destinationWarehouse'])
        );
        $this->ensureSuccess($containerResult, 'attach batch to container');

        $manifest = $manifest->fresh();
        $manifest->update(['notes' => "Seeded {$tag}."]);

        $this->ensureSuccess($transport->assignDriver($manifest->fresh(), $driver, $origin, $user), 'assign rider');
        $this->ensureSuccess($transport->adminMarkAllItemsLoaded($manifest->fresh(), $origin, $user), 'mark all loaded');
        $this->ensureSuccess($transport->dispatch($manifest->fresh(), $origin, $user), 'dispatch transfer');

        if ($targetStatus === 'arrived') {
            $this->ensureSuccess($transport->adminMarkArrived($manifest->fresh(), $origin, $user), 'mark arrived');
        }

        return $manifest->fresh(['originWarehouse', 'destinationWarehouse', 'items']);
    }

    private function attachFixturePhotosToManifest(TransportManifest $manifest, string $tag, User $user): void
    {
        $manifest->loadMissing('items.shipmentItem.warehouseReceiptItems');

        $receiptItems = $manifest->items
            ->pluck('shipmentItem')
            ->filter()
            ->flatMap(fn ($shipmentItem) => $shipmentItem->warehouseReceiptItems)
            ->filter()
            ->unique('id')
            ->values();

        $this->attachFixturePhotosToReceiptItems($receiptItems, $tag, $user);
    }

    private function attachFixturePhotosToReceiptItems(iterable $receiptItems, string $tag, User $user): void
    {
        collect($receiptItems)
            ->values()
            ->each(function (WarehouseReceiptItem $receiptItem, int $index) use ($tag, $user) {
                $receiptItem->loadMissing('shipmentItem.images', 'photos');
                $shipmentItem = $receiptItem->shipmentItem;

                if (!$shipmentItem) {
                    return;
                }

                $base = $this->photoSlug($tag . '-' . $shipmentItem->tracking_code);

                if ($index % 3 !== 1 && !$shipmentItem->images()->exists()) {
                    $photo = $this->writeFixturePhoto(
                        path: "incoming-fixtures/vendor-{$base}.svg",
                        title: $shipmentItem->description ?: 'Package',
                        subtitle: $shipmentItem->tracking_code ?: 'Vendor photo',
                        color: '#fb923c'
                    );

                    ShipmentItemImage::query()->create([
                        'shipment_item_id' => $shipmentItem->id,
                        'path' => $photo['path'],
                        'original_name' => 'vendor-package-photo.svg',
                        'size' => $photo['size'],
                        'sort_order' => 1,
                    ]);
                }

                if (!$receiptItem->photos()->exists()) {
                    $photo = $this->writeFixturePhoto(
                        path: "incoming-fixtures/receipt-{$base}.svg",
                        title: 'Receipt Check',
                        subtitle: $shipmentItem->tracking_code ?: 'Receipt photo',
                        color: '#64748b'
                    );

                    WarehouseReceiptItemPhoto::query()->create([
                        'warehouse_receipt_item_id' => $receiptItem->id,
                        'path' => $photo['path'],
                        'original_name' => 'receipt-package-photo.svg',
                        'size' => $photo['size'],
                        'photo_type' => 'proof',
                        'created_by_user_id' => $user->id,
                    ]);
                }
            });
    }

    /**
     * @return array{path: string, size: int}
     */
    private function writeFixturePhoto(string $path, string $title, string $subtitle, string $color): array
    {
        $storage = app(StorageService::class);

        if ($storage->exists($path)) {
            return [
                'path' => $path,
                'size' => $storage->size($path),
            ];
        }

        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeSubtitle = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="650" viewBox="0 0 900 650">
  <rect width="900" height="650" fill="#f8fafc"/>
  <rect x="70" y="70" width="760" height="510" rx="42" fill="#fff7ed" stroke="#fed7aa" stroke-width="8"/>
  <rect x="130" y="130" width="260" height="260" rx="30" fill="{$color}"/>
  <path d="M260 185 175 228v96l85 48 85-48v-96l-85-43Z" fill="none" stroke="#fff" stroke-width="18" stroke-linejoin="round"/>
  <path d="M175 228 260 276l85-48M260 276v96" fill="none" stroke="#fff" stroke-width="14" stroke-linecap="round" stroke-linejoin="round"/>
  <text x="440" y="235" fill="#0f172a" font-family="Arial, sans-serif" font-size="46" font-weight="800">{$safeTitle}</text>
  <text x="440" y="305" fill="#64748b" font-family="Arial, sans-serif" font-size="30" font-weight="700">{$safeSubtitle}</text>
  <text x="440" y="375" fill="#9ca3af" font-family="Arial, sans-serif" font-size="24" font-weight="700">Incoming manifest fixture photo</text>
</svg>
SVG;

        return $storage->putContent($path, $svg);
    }

    private function photoSlug(string $value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($value)), '-');
    }

    private function warehouse(string $code): Warehouse
    {
        $warehouse = Warehouse::query()->where('code', $code)->first();

        if (!$warehouse) {
            throw new RuntimeException("Warehouse {$code} does not exist. Run WarehouseSeeder first.");
        }

        return $warehouse;
    }

    private function seedUser(Warehouse $warehouse): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'incoming-fixtures@parcelman.test'],
            [
                'name' => 'Incoming Fixture Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'warehouse_id' => $warehouse->id,
            ]
        );

        if ((int) ($user->warehouse_id ?? 0) !== (int) $warehouse->id) {
            $user->update(['warehouse_id' => $warehouse->id]);
        }

        $role = Role::query()->where('slug', 'warehouse_manager')->first();
        if ($role && !$user->roles()->whereKey($role->id)->exists()) {
            $user->roles()->attach($role->id, ['assigned_at' => now()]);
        }

        return $user;
    }

    private function seedDriver(): Driver
    {
        return Driver::query()->updateOrCreate(
            ['phone' => '+233244222222'],
            [
                'name' => 'Fixture Transport Rider',
                'email' => 'fixture.transport.rider@parcelman.test',
                'password' => Hash::make('password123'),
                'vehicle_type' => 'truck',
                'vehicle_number' => 'GT-4242-26',
                'license_number' => 'FIXTURE-TX-001',
                'base_location' => 'Accra Main Hub',
                'status' => 'available',
                'is_active' => true,
                'task_capabilities' => [Driver::CAPABILITY_TRANSPORT],
            ]
        );
    }

    private function seedVendor(Warehouse $origin): Vendor
    {
        return Vendor::query()->updateOrCreate(
            ['phone' => '+233244333333'],
            [
                'name' => 'Incoming Fixture Vendor',
                'business_name' => $origin->name . ' Fixture Desk',
                'email' => 'incoming-fixtures@parcelman.test',
                'is_active' => true,
            ]
        );
    }

    private function ensureSuccess(array $result, string $action): void
    {
        if (!($result['success'] ?? false)) {
            throw new RuntimeException("Could not {$action}: " . ($result['message'] ?? 'Unknown error'));
        }
    }
}
