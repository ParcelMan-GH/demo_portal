<?php

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
        Schema::create('shipment_charges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            // Nullable = shipment-level charge (e.g. pickup fee covers the whole shipment)
            $table->foreignId('shipment_item_id')->nullable()->constrained('shipment_items')->nullOnDelete();

            // What the charge is for:
            //   pickup_fee | delivery_fee | station_fee | handling_fee | other
            $table->string('charge_type', 32);

            // Who pays:
            //   vendor | recipient | parcelman
            $table->string('payer_type', 16);

            // Derived: revenue (vendor/recipient → parcelman) | expense (parcelman → third party)
            $table->string('direction', 16);

            // Lifecycle milestone this charge is tied to:
            //   at_pickup | at_receiving | before_delivery | at_delivery | at_handoff
            $table->string('due_stage', 32);

            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('GHS');

            // Lifecycle: draft | pending | paid | waived | cancelled
            $table->string('status', 16)->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_reference', 100)->nullable();

            // Who set this up / who recorded the payment
            $table->foreignId('recorded_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            // Optional link to the event that generated this line (e.g. a delivery run stop)
            $table->foreignId('delivery_run_stop_id')->nullable()->constrained('delivery_run_stops')->nullOnDelete();
            $table->foreignId('pickup_assignment_id')->nullable()->constrained('pickup_assignments')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->text('waive_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['shipment_id', 'status']);
            $table->index(['shipment_item_id', 'status']);
            $table->index(['charge_type', 'status']);
            $table->index(['due_stage', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_charges');
    }
};
