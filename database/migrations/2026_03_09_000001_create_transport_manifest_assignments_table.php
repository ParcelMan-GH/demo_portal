<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_manifest_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_manifest_id')->constrained('transport_manifests')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('unassigned_at')->nullable();
            $table->foreignId('unassigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unassign_reason')->nullable();
            $table->timestamps();

            $table->index(['transport_manifest_id', 'created_at'], 'tma_manifest_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_manifest_assignments');
    }
};
