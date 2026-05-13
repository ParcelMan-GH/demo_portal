<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_manifests', function (Blueprint $table) {
            $table->dropForeign(['sort_batch_id']);
            $table->dropForeign(['destination_warehouse_id']);
        });

        Schema::table('transport_manifests', function (Blueprint $table) {
            $table->foreignId('sort_batch_id')->nullable()->change();
            $table->foreignId('destination_warehouse_id')->nullable()->change();
        });

        Schema::table('transport_manifests', function (Blueprint $table) {
            $table->foreign('sort_batch_id')->references('id')->on('sort_batches')->nullOnDelete();
            $table->foreign('destination_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transport_manifests', function (Blueprint $table) {
            $table->dropForeign(['sort_batch_id']);
            $table->dropForeign(['destination_warehouse_id']);
        });

        Schema::table('transport_manifests', function (Blueprint $table) {
            $table->foreignId('sort_batch_id')->nullable(false)->change();
            $table->foreignId('destination_warehouse_id')->nullable(false)->change();
        });

        Schema::table('transport_manifests', function (Blueprint $table) {
            $table->foreign('sort_batch_id')->references('id')->on('sort_batches')->cascadeOnDelete();
            $table->foreign('destination_warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
        });
    }
};
