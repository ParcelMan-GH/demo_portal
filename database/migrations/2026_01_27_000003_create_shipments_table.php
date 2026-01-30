<?php

use App\Enums\ShipmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('shipment_number')->unique();
            $table->string('status')->default(ShipmentStatus::DRAFT->value);

            // Recipient Details
            $table->string('recipient_name');
            $table->string('recipient_phone');

            // Location Option 1: Dropdown + Manual
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->string('town')->nullable();

            // Location Option 2: GPS Coordinates (from OSM or manual)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Location Option 3: Ghana Post
            $table->string('gh_post_address')->nullable();

            // Additional
            $table->text('delivery_instructions')->nullable();
            $table->string('landmark')->nullable();

            // Status timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('vendor_id');
            $table->index('status');
            $table->index('shipment_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
