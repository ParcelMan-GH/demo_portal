<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_runs', function (Blueprint $table) {
            $table->unsignedBigInteger('sort_batch_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_runs', function (Blueprint $table) {
            $table->unsignedBigInteger('sort_batch_id')->nullable(false)->change();
        });
    }
};
