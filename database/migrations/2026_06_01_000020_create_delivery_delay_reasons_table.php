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
        Schema::create('delivery_delay_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            'Traffic delay',
            'Recipient requested later delivery',
            'Weather delay',
            'Vehicle issue',
            'Route delay',
            'Other',
        ];

        foreach ($defaults as $index => $label) {
            DB::table('delivery_delay_reasons')->insert([
                'label' => $label,
                'slug' => Str::slug($label),
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_delay_reasons');
    }
};
