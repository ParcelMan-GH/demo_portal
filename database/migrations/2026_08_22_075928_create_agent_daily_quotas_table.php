<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentDailyQuotasTable extends Migration
{
    public function up(): void
    {
        Schema::create('agent_daily_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tracking_date');
            
            // Task & Target Tracking
            $table->integer('assigned_tasks')->default(0);
            $table->integer('completed_tasks')->default(0);
            $table->decimal('collected_amount', 12, 2)->default(0.00);
            
            // Financials
            $table->decimal('earned_commission', 10, 2)->default(0.00);
            $table->boolean('is_kumasi_agent')->default(false); 
            
            // Lock & Override Safeguards
            $table->boolean('is_unlocked')->default(false);
            $table->string('payout_status')->default('locked');
            $table->foreignId('overridden_by_id')->nullable()->constrained('users');
            $table->timestamp('overridden_at')->nullable();
            $table->text('override_reason')->nullable();
            
            $table->timestamps();

            // Ensure only one ledger entry per agent, per day
            $table->unique(['user_id', 'tracking_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_daily_quotas');
    }
}