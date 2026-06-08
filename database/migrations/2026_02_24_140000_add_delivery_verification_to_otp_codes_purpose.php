<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE otp_codes MODIFY COLUMN purpose ENUM('registration', 'login', 'delivery_verification') NOT NULL");
    }

    public function down(): void
    {
        DB::table('otp_codes')->where('purpose', 'delivery_verification')->delete();

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE otp_codes MODIFY COLUMN purpose ENUM('registration', 'login') NOT NULL");
    }
};
