<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_assignments', function (Blueprint $table) {
            $table->foreignId('target_warehouse_id')
                ->nullable()
                ->after('driver_id')
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->timestamp('arrived_warehouse_at')
                ->nullable()
                ->after('completed_at');

            $table->foreignId('received_warehouse_id')
                ->nullable()
                ->after('arrived_warehouse_at')
                ->constrained('warehouses')
                ->nullOnDelete();

            // Kept as plain ID for now; model relationship can be added later.
            $table->unsignedBigInteger('received_by_user_id')
                ->nullable()
                ->after('received_warehouse_id');

            $table->timestamp('received_at')
                ->nullable()
                ->after('received_by_user_id');

            $table->text('receive_notes')
                ->nullable()
                ->after('received_at');

            $table->index('received_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_assignments', function (Blueprint $table) {
            $table->dropForeign(['target_warehouse_id']);
            $table->dropForeign(['received_warehouse_id']);
            $table->dropIndex(['received_by_user_id']);
            $table->dropColumn([
                'target_warehouse_id',
                'arrived_warehouse_at',
                'received_warehouse_id',
                'received_by_user_id',
                'received_at',
                'receive_notes',
            ]);
        });
    }
};

