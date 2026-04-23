<?php

namespace App\Console\Commands;

use App\Events\ShipmentStatusChanged;
use App\Helpers\PhoneHelper;
use App\Models\District;
use App\Models\Region;
use App\Models\Vendor;
use App\Services\ShipmentItemService;
use App\Services\ShipmentService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

/**
 * Seeds sample shipments shaped like mobile-app submissions, straight through
 * the service layer. Images are synthesized JPEGs fed as UploadedFile
 * instances so the rest of the pipeline (storage, ShipmentItemImage rows,
 * phone tagging) runs exactly as it would for a real vendor upload.
 */
class SeedMobileSampleShipments extends Command
{
    protected $signature = 'shipments:seed-mobile-sample
                            {--vendor-phone=0542796510}
                            {--count=20}
                            {--min-photos=2}
                            {--max-photos=5}';

    protected $description = 'Generate mobile-app-shaped sample shipments for a vendor (bypasses notifications).';

    public function handle(ShipmentService $shipmentService, ShipmentItemService $itemService): int
    {
        $vendorPhone = (string) $this->option('vendor-phone');
        $count = (int) $this->option('count');
        $minPhotos = max(1, (int) $this->option('min-photos'));
        $maxPhotos = max($minPhotos, (int) $this->option('max-photos'));

        $normalized = PhoneHelper::format($vendorPhone) ?? $vendorPhone;
        $vendor = Vendor::where('phone', $normalized)->orWhere('phone', $vendorPhone)->first();

        if (!$vendor) {
            $this->error("No vendor found with phone {$vendorPhone} (normalized: {$normalized}).");
            return self::FAILURE;
        }

        $this->info("Seeding {$count} shipments for vendor #{$vendor->id} ({$vendor->name}, {$vendor->phone}).");

        $regions = Region::with(['districts' => fn($q) => $q->limit(8)])->get();
        if ($regions->isEmpty()) {
            $this->error('No regions seeded. Run `php artisan db:seed` first.');
            return self::FAILURE;
        }

        // Silence notification listeners but keep other side effects intact.
        Event::fake([ShipmentStatusChanged::class]);

        $tmpDir = storage_path('app/tmp-seed-shipments-' . uniqid());
        File::ensureDirectoryExists($tmpDir);

        $fakeRequest = Request::create('/api/v1/shipments', 'POST');
        $fakeRequest->headers->set('User-Agent', 'parcelman-seeder/1.0');
        $fakeRequest->setUserResolver(fn() => $vendor);

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $createdIds = [];

        for ($i = 1; $i <= $count; $i++) {
            $photoCount = random_int($minPhotos, $maxPhotos);
            $deliveryPreference = random_int(0, 4) === 0 ? 'self_pickup' : 'deliver';
            $region = $regions->random();
            $district = $region->districts->isNotEmpty() ? $region->districts->random() : null;

            $shipmentData = [
                'destination_mode' => 'single',
                'delivery_preference' => $deliveryPreference,
                'fulfillment_type' => 'warehouse',

                'pickup_contact_name' => $vendor->name,
                'pickup_contact_phone' => $vendor->phone,
                'pickup_town' => $this->randomTown('origin'),
                'pickup_landmark' => $this->pickRandom([
                    'Opposite Shell Fuel Station', 'Near Melcom Superstore', 'Behind the roundabout',
                    'Next to Ecobank', null, null,
                ]),
                'pickup_instructions' => $this->pickRandom([
                    'Call on arrival', 'Ask for shop owner', null, null,
                ]),

                'delivery_recipient_name' => $this->randomName(),
                'delivery_recipient_phone' => $this->randomGhanaPhone(),
                'delivery_region_id' => $region->id,
                'delivery_district_id' => $district?->id,
                'delivery_town' => $this->randomTown('destination'),
                'delivery_landmark' => $this->pickRandom([
                    'Blue gate with satellite dish', 'Second house from the junction',
                    'Above pharmacy', null,
                ]),
                'delivery_instructions' => $this->pickRandom([
                    'Deliver between 9am and 5pm.', 'Call 30 minutes before arrival.',
                    null, null,
                ]),

                'sender_notes' => $this->pickRandom([
                    'Handle with care — fragile.', 'Keep dry.', 'Urgent — customer waiting.',
                    null, null, null,
                ]),
            ];

            $createResult = $shipmentService->create($vendor, $shipmentData, $fakeRequest);
            if (empty($createResult['success'])) {
                $this->newLine();
                $this->error("[{$i}/{$count}] Create failed: " . ($createResult['message'] ?? 'unknown error'));
                continue;
            }

            $shipment = \App\Models\Shipment::find($createResult['data']['shipment']['id']);

            $images = [];
            $phones = [];
            for ($p = 0; $p < $photoCount; $p++) {
                $path = $this->makeJpeg($tmpDir, $shipment->shipment_number, $p + 1);
                $images[] = new UploadedFile(
                    path: $path,
                    originalName: basename($path),
                    mimeType: 'image/jpeg',
                    error: null,
                    test: true,
                );
                // Every photo carries its own recipient phone — mirrors the
                // mobile app where each photo = one package + one recipient.
                $phones[] = $this->randomGhanaPhone();
            }

            $itemData = [
                'description' => $this->pickRandom([
                    'Clothing bundle', 'Electronics parcel', 'Kitchenware', 'Books',
                    'Cosmetics package', 'Phone accessories', 'Shoes',
                ]),
                'quantity' => $photoCount,
                'delivery_preference' => $deliveryPreference,
            ];

            $itemResult = $itemService->addItem(
                shipment: $shipment,
                data: $itemData,
                request: $fakeRequest,
                images: $images,
                phones: $phones,
            );

            if (empty($itemResult['success'] ?? false)) {
                $this->newLine();
                $this->warn("[{$i}/{$count}] Item add returned warning: " . ($itemResult['message'] ?? ''));
            }

            $submitResult = $shipmentService->submit($shipment, $fakeRequest);
            if (empty($submitResult['success'])) {
                $this->newLine();
                $this->warn("[{$i}/{$count}] Submit failed: " . ($submitResult['message'] ?? ''));
            }

            $createdIds[] = $shipment->id;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        File::deleteDirectory($tmpDir);

        $this->info('Done. Created shipment IDs: ' . implode(', ', $createdIds));
        return self::SUCCESS;
    }

    /** Create a small varied JPEG on disk and return its path. */
    private function makeJpeg(string $dir, string $shipmentNumber, int $index): string
    {
        $w = 800;
        $h = 600;
        $im = imagecreatetruecolor($w, $h);

        // Randomish background — visually distinguishable across seeds.
        $bg = imagecolorallocate($im, random_int(60, 200), random_int(60, 200), random_int(60, 200));
        imagefilledrectangle($im, 0, 0, $w, $h, $bg);

        $white = imagecolorallocate($im, 255, 255, 255);
        $label = "{$shipmentNumber} #{$index}";
        imagestring($im, 5, 20, 20, $label, $white);
        imagestring($im, 3, 20, 50, 'SEEDED SAMPLE (not a real package)', $white);

        $path = $dir . DIRECTORY_SEPARATOR . 'seed-' . $shipmentNumber . '-' . $index . '-' . uniqid() . '.jpg';
        imagejpeg($im, $path, 80);
        imagedestroy($im);

        return $path;
    }

    private function randomName(): string
    {
        $first = ['Akosua', 'Kwame', 'Abena', 'Kojo', 'Ama', 'Yaw', 'Esi', 'Kweku', 'Adjoa', 'Fiifi', 'Nana', 'Efua'];
        $last = ['Mensah', 'Boateng', 'Asante', 'Owusu', 'Appiah', 'Darko', 'Sarpong', 'Agyeman', 'Osei', 'Nyarko'];
        return $this->pickRandom($first) . ' ' . $this->pickRandom($last);
    }

    private function randomGhanaPhone(): string
    {
        $prefixes = ['024', '054', '055', '059', '020', '050', '027', '057', '026', '056'];
        return $this->pickRandom($prefixes) . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
    }

    private function randomTown(string $kind): string
    {
        $towns = $kind === 'origin'
            ? ['East Legon', 'Osu', 'Spintex', 'Tema Community 1', 'Madina', 'Dansoman', 'Adabraka']
            : ['Kumasi Central', 'Takoradi Market Circle', 'Cape Coast', 'Ho', 'Sunyani', 'Tamale', 'Koforidua', 'Tarkwa', 'Obuasi'];
        return $this->pickRandom($towns);
    }

    /** @template T @param array<T> $items @return T */
    private function pickRandom(array $items)
    {
        return $items[array_rand($items)];
    }
}
