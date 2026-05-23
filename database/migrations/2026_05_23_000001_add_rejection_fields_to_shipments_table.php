<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('cancellation_reason');
            $table->foreignId('rejected_by_admin_id')
                ->nullable()
                ->after('rejected_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('rejected_by_admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['rejected_by_admin_id']);
            $table->dropColumn(['rejected_at', 'rejected_by_admin_id', 'rejection_reason']);
        });
    }
};
