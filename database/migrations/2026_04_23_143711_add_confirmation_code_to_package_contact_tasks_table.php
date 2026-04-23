<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('package_contact_tasks', function (Blueprint $table) {
            $table->string('confirmation_code', 10)->nullable()->after('notes');
            $table->timestamp('confirmation_code_sent_at')->nullable()->after('confirmation_code');
            $table->timestamp('confirmation_code_expires_at')->nullable()->after('confirmation_code_sent_at');
            $table->timestamp('confirmation_code_verified_at')->nullable()->after('confirmation_code_expires_at');
            $table->unsignedInteger('confirmation_attempts')->default(0)->after('confirmation_code_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_contact_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'confirmation_code',
                'confirmation_code_sent_at',
                'confirmation_code_expires_at',
                'confirmation_code_verified_at',
                'confirmation_attempts',
            ]);
        });
    }
};
