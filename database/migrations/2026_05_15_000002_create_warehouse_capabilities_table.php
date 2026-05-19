<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('module');
            $table->string('scope')->default('own');
            $table->json('allowed_warehouse_ids')->nullable();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['warehouse_id', 'module']);
            $table->index(['module', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_capabilities');
    }
};
