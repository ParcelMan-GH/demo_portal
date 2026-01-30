<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify enum to add 'pin_change' purpose
        DB::statement("ALTER TABLE otp_codes MODIFY COLUMN purpose ENUM('registration', 'pin_reset', 'pin_change') NOT NULL");
    }

    public function down(): void
    {
        // Remove 'pin_change' from enum (only if no records use it)
        DB::statement("ALTER TABLE otp_codes MODIFY COLUMN purpose ENUM('registration', 'pin_reset') NOT NULL");
    }
};
