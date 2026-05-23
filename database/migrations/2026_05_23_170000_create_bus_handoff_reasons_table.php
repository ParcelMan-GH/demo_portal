<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_handoff_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->string('slug', 140)->unique();
            $table->string('type', 40)->default('issue');
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active', 'sort_order']);
        });

        $defaults = [
            ['label' => 'Recipient unreachable', 'type' => 'not_received'],
            ['label' => 'Recipient says not received', 'type' => 'not_received'],
            ['label' => 'Wrong contact', 'type' => 'failed'],
            ['label' => 'Courier delay', 'type' => 'issue'],
            ['label' => 'Package damaged', 'type' => 'issue'],
            ['label' => 'Other', 'type' => 'other'],
        ];

        foreach ($defaults as $index => $reason) {
            DB::table('bus_handoff_reasons')->insert([
                'label' => $reason['label'],
                'slug' => Str::slug($reason['label']),
                'type' => $reason['type'],
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_handoff_reasons');
    }
};
