<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Withdrawal requests from drivers.
 *
 * This exists so the balance is a real number rather than a display value. A
 * driver's balance is what they have earned minus what they have already asked
 * for; without a record of the asking, the same balance could be withdrawn over
 * and over and every request would look valid.
 *
 * `reference` is what the driver is shown and what support would search for.
 * Amounts are decimal(12,2) to match the fee columns they are summed from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();

            $table->string('reference', 40)->unique();
            $table->decimal('amount', 12, 2);

            // pending -> approved -> paid, or rejected / cancelled.
            $table->string('status', 20)->default('pending');

            // Where the money is going, captured at request time because the
            // profile phone number can change afterwards.
            $table->string('phone', 30)->nullable();
            $table->string('method', 30)->default('mobile_money');

            // The balance at the moment of the request, so a later dispute can
            // be settled against what the driver was actually shown.
            $table->decimal('balance_at_request', 12, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_payout_requests');
    }
};
