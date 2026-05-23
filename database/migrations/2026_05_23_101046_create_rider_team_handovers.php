<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('label_custody_events', function (Blueprint $table) {
            $table->string('event_type', 40)->change();
        });

        Schema::create('rider_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('zone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['warehouse_id', 'is_active']);
            $table->index('name');
        });

        Schema::create('rider_team_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_team_id')->constrained('rider_teams')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->boolean('is_active')->default(true);
            $table->string('added_by_type', 20)->nullable();
            $table->unsignedBigInteger('added_by_id')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->index(['rider_team_id', 'role', 'is_active'], 'rtm_team_role_active_index');
            $table->index(['driver_id', 'role', 'is_active'], 'rtm_driver_role_active_index');
            $table->index(['added_by_type', 'added_by_id'], 'rtm_added_by_index');
        });

        Schema::create('rider_team_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('handover_number')->unique();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('rider_team_id')->constrained('rider_teams')->cascadeOnDelete();
            $table->foreignId('leader_driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('assigned_count')->default(0);
            $table->unsignedInteger('received_count')->default(0);
            $table->unsignedInteger('distributed_count')->default(0);
            $table->unsignedInteger('claimed_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index(['rider_team_id', 'status']);
            $table->index(['leader_driver_id', 'status']);
        });

        Schema::create('rider_team_handover_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_team_handover_id')->constrained('rider_team_handovers')->cascadeOnDelete();
            $table->unsignedBigInteger('warehouse_receipt_item_label_id');
            $table->foreignId('allocated_to_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status', 30)->default('assigned_to_leader');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('leader_received_at')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('member_claimed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_receipt_item_label_id', 'rthi_label_fk')
                ->references('id')
                ->on('warehouse_receipt_item_labels')
                ->cascadeOnDelete();
            $table->unique('warehouse_receipt_item_label_id', 'rthi_label_unique');
            $table->index(['rider_team_handover_id', 'status'], 'rthi_handover_status_index');
            $table->index(['allocated_to_driver_id', 'status'], 'rthi_allocated_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_team_handover_items');
        Schema::dropIfExists('rider_team_handovers');
        Schema::dropIfExists('rider_team_memberships');
        Schema::dropIfExists('rider_teams');

        Schema::table('label_custody_events', function (Blueprint $table) {
            $table->string('event_type', 20)->change();
        });
    }
};
