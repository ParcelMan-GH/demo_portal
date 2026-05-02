<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_manifest_id')->constrained('transport_manifests')->cascadeOnDelete();
            $table->string('container_code')->unique();
            $table->string('container_type')->default('loose');
            $table->unsignedInteger('sequence_number')->default(1);
            $table->string('status')->default('sealed');
            $table->unsignedInteger('expected_package_count')->default(0);
            $table->timestamp('sealed_at')->nullable();
            $table->unsignedBigInteger('sealed_by_user_id')->nullable();
            $table->timestamp('loaded_at')->nullable();
            $table->foreignId('loaded_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('received_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['transport_manifest_id', 'status']);
            $table->index(['loaded_by_driver_id', 'status']);
        });

        Schema::create('transport_container_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_container_id')->constrained('transport_containers')->cascadeOnDelete();
            $table->foreignId('transport_manifest_item_id')->constrained('transport_manifest_items')->cascadeOnDelete();
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();
            $table->string('label_barcode')->nullable();
            $table->unsignedInteger('expected_quantity')->default(0);
            $table->unsignedInteger('received_quantity')->default(0);
            $table->string('status')->default('packed');
            $table->timestamps();

            $table->unique(['transport_container_id', 'transport_manifest_item_id'], 'tc_item_unique');
            $table->index(['shipment_item_id', 'status']);
        });

        $now = now();
        DB::table('transport_manifests')
            ->orderBy('id')
            ->get(['id', 'manifest_number', 'created_by_user_id', 'created_at', 'updated_at'])
            ->each(function ($manifest) use ($now) {
                $items = DB::table('transport_manifest_items')
                    ->where('transport_manifest_id', $manifest->id)
                    ->get(['id', 'shipment_item_id', 'expected_quantity', 'line_status', 'loaded_at', 'loaded_quantity']);

                if ($items->isEmpty()) {
                    return;
                }

                $allLoaded = $items->every(fn ($item) => (int) $item->loaded_quantity >= (int) $item->expected_quantity);
                $containerId = DB::table('transport_containers')->insertGetId([
                    'transport_manifest_id' => $manifest->id,
                    'container_code' => $this->defaultContainerCode((string) $manifest->manifest_number),
                    'container_type' => 'loose',
                    'sequence_number' => 1,
                    'status' => $allLoaded ? 'loaded' : 'sealed',
                    'expected_package_count' => $items->count(),
                    'sealed_at' => $manifest->created_at ?? $now,
                    'sealed_by_user_id' => $manifest->created_by_user_id,
                    'loaded_at' => $allLoaded ? ($items->pluck('loaded_at')->filter()->max() ?: $now) : null,
                    'notes' => 'Auto-created default container for existing manifest.',
                    'created_at' => $manifest->created_at ?? $now,
                    'updated_at' => $manifest->updated_at ?? $now,
                ]);

                foreach ($items as $item) {
                    DB::table('transport_container_items')->insert([
                        'transport_container_id' => $containerId,
                        'transport_manifest_item_id' => $item->id,
                        'shipment_item_id' => $item->shipment_item_id,
                        'expected_quantity' => (int) $item->expected_quantity,
                        'received_quantity' => 0,
                        'status' => $allLoaded ? 'loaded' : 'packed',
                        'created_at' => $manifest->created_at ?? $now,
                        'updated_at' => $manifest->updated_at ?? $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_container_items');
        Schema::dropIfExists('transport_containers');
    }

    private function defaultContainerCode(string $manifestNumber): string
    {
        return preg_replace('/[^A-Z0-9-]/', '', strtoupper($manifestNumber)) . '-C01';
    }
};
