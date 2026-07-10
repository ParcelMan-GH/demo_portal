<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        // Earlier enum-alter migrations intentionally skip SQLite, leaving
        // fresh test databases unable to store the production login purpose.
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('purpose')->change();
        });
    }

    public function down(): void
    {
        // The production schema remains an enum. SQLite test databases are
        // ephemeral and do not need the obsolete constraint restored.
    }
};
