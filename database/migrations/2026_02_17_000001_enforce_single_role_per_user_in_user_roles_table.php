<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Keep only the latest role assignment per user before adding the unique key.
        $duplicateUserIds = DB::table('user_roles')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $keepId = DB::table('user_roles')
                ->where('user_id', $userId)
                ->orderByDesc('assigned_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('user_roles')
                ->where('user_id', $userId)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('user_roles', function (Blueprint $table) {
            if ($this->indexExists('user_roles_user_id_role_id_unique')) {
                $table->dropUnique('user_roles_user_id_role_id_unique');
            }

            if (!$this->indexExists('user_roles_user_id_unique')) {
                $table->unique('user_id', 'user_roles_user_id_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            if ($this->indexExists('user_roles_user_id_unique')) {
                $table->dropUnique('user_roles_user_id_unique');
            }

            if (!$this->indexExists('user_roles_user_id_role_id_unique')) {
                $table->unique(['user_id', 'role_id'], 'user_roles_user_id_role_id_unique');
            }
        });
    }

    private function indexExists(string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(1) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, 'user_roles', $indexName]
        );

        return ((int) ($result->aggregate ?? 0)) > 0;
    }
};
