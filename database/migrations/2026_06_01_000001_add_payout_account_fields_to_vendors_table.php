<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('payout_momo_network', 40)->nullable()->after('commission_rate_override');
            $table->string('payout_account_name')->nullable()->after('payout_momo_network');
            $table->string('payout_account_number', 20)->nullable()->after('payout_account_name');
            $table->timestamp('payout_account_updated_at')->nullable()->after('payout_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'payout_momo_network',
                'payout_account_name',
                'payout_account_number',
                'payout_account_updated_at',
            ]);
        });
    }
};
