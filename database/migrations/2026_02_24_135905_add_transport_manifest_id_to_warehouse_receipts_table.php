<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_receipts', function (Blueprint $table) {
            // Make pickup_assignment_id nullable so transport manifest receipts can exist without one
            $table->unsignedBigInteger('pickup_assignment_id')->nullable()->change();

            // Add transport manifest reference
            $table->foreignId('transport_manifest_id')
                ->nullable()
                ->after('pickup_assignment_id')
                ->constrained('transport_manifests')
                ->nullOnDelete();

            $table->index('transport_manifest_id');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transport_manifest_id');
            $table->unsignedBigInteger('pickup_assignment_id')->nullable(false)->change();
        });
    }
};
