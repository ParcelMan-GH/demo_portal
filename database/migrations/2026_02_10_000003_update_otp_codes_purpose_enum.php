<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Delete old OTP records with removed purposes
        DB::table('otp_codes')->whereIn('purpose', ['pin_reset', 'pin_change'])->delete();

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Update enum to only include registration and login
        DB::statement("ALTER TABLE otp_codes MODIFY COLUMN purpose ENUM('registration', 'login') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE otp_codes MODIFY COLUMN purpose ENUM('registration', 'pin_reset', 'pin_change', 'login') NOT NULL");
    }
};
