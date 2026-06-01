<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_contact_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('package_contact_tasks', 'resolved_by_user_id')) {
                $table->unsignedBigInteger('resolved_by_user_id')->nullable()->after('resolved_at');
                $table->foreign('resolved_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->index('resolved_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('package_contact_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('package_contact_tasks', 'resolved_by_user_id')) {
                $table->dropForeign(['resolved_by_user_id']);
                $table->dropIndex(['resolved_by_user_id']);
                $table->dropColumn('resolved_by_user_id');
            }
        });
    }
};
