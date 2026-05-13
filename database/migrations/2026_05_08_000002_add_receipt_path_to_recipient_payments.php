<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recipient_payment_groups') && !Schema::hasColumn('recipient_payment_groups', 'receipt_path')) {
            Schema::table('recipient_payment_groups', function (Blueprint $table) {
                $table->string('receipt_path')->nullable()->after('payment_reference');
            });
        }

        if (Schema::hasTable('recipient_payment_session_entries') && !Schema::hasColumn('recipient_payment_session_entries', 'receipt_path')) {
            Schema::table('recipient_payment_session_entries', function (Blueprint $table) {
                $table->string('receipt_path')->nullable()->after('reference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recipient_payment_session_entries') && Schema::hasColumn('recipient_payment_session_entries', 'receipt_path')) {
            Schema::table('recipient_payment_session_entries', function (Blueprint $table) {
                $table->dropColumn('receipt_path');
            });
        }

        if (Schema::hasTable('recipient_payment_groups') && Schema::hasColumn('recipient_payment_groups', 'receipt_path')) {
            Schema::table('recipient_payment_groups', function (Blueprint $table) {
                $table->dropColumn('receipt_path');
            });
        }
    }
};
