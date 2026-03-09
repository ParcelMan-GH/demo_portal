<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'cancelled_by_admin_id')) {
                $table->foreignId('cancelled_by_admin_id')
                    ->nullable()
                    ->after('cancelled_at')
                    ->constrained('admins')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'cancelled_by_admin_id')) {
                $table->dropForeign(['cancelled_by_admin_id']);
                $table->dropColumn('cancelled_by_admin_id');
            }
        });
    }
};
