<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionTiersTable extends Migration
{
    public function up(): void
    {
        Schema::create('commission_tiers', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_collection', 10, 2); // e.g., 2100.00
            $table->decimal('max_collection', 10, 2)->nullable(); // e.g., 2199.99
            $table->decimal('payout_amount', 10, 2); // e.g., 10.00
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_tiers');
    }
}