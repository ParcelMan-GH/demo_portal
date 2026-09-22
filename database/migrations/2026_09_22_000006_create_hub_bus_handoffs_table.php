<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bus handoff agent's record of handing one parcel to an external bus driver.
 *
 * A parcel leaves a hub on an intercity bus. The driver is not a ParcelMan user
 * (they do not run the app), so their details are captured as free text rather
 * than a foreign key, and the handover itself is evidenced by a photo. The
 * customer gets an SMS pointing at a public token URL where they can see that
 * photo, which is why the token is stored hashed with its own expiry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_bus_handoffs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('shipment_item_id');
            $table->foreign('shipment_item_id')->references('id')->on('shipment_items')->cascadeOnDelete();

            $table->unsignedBigInteger('hub_id')->nullable();
            $table->foreign('hub_id')->references('id')->on('warehouses')->nullOnDelete();

            $table->unsignedBigInteger('outgoing_batch_id')->nullable();

            // The bus handoff agent who submitted the handover.
            $table->unsignedBigInteger('handed_off_by')->nullable();
            $table->foreign('handed_off_by')->references('id')->on('users')->nullOnDelete();

            // The external bus driver, captured by hand because they have no account.
            $table->string('driver_name', 120);
            $table->string('driver_phone', 30)->nullable();
            $table->string('driver_id_number', 60)->nullable();

            // The vehicle the parcel went onto.
            $table->string('vehicle_plate', 30)->nullable();
            $table->string('vehicle_description', 120)->nullable();
            $table->string('bus_company', 120)->nullable();

            // Snapshot of where it was going, frozen at handover time.
            $table->string('destination', 160)->nullable();
            $table->timestamp('departure_at')->nullable();

            // The proof: a photo of the parcel being handed to the driver.
            $table->string('proof_photo_path');
            $table->unsignedBigInteger('proof_photo_size')->nullable();
            $table->timestamp('proof_photo_taken_at')->nullable();

            // The public link the customer is texted, stored hashed like a password.
            $table->string('public_token_hash', 64)->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable();

            $table->timestamp('sms_sent_at')->nullable();
            $table->timestamp('sms_failed_at')->nullable();
            $table->string('sms_error', 255)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['hub_id', 'created_at']);
            $table->index('shipment_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_bus_handoffs');
    }
};
