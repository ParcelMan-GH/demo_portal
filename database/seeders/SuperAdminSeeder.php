<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hqWarehouse = Warehouse::query()
            ->where('is_hq', true)
            ->first()
            ?? Warehouse::query()->where('code', 'WH-001')->first()
            ?? Warehouse::query()->orderBy('id')->first();

        $existing = User::where('email', 'admin@parcelman.com')->first();
        if ($existing) {
            $assignedWarehouse = $existing->warehouse()->first();
            $updates = [];

            if (!$existing->is_active) {
                $updates['is_active'] = true;
            }

            if (
                $hqWarehouse
                && (
                    !$existing->warehouse_id
                    || !$assignedWarehouse
                    || !$assignedWarehouse->is_active
                    || !$assignedWarehouse->is_hq
                    || !$assignedWarehouse->can_administer_system
                )
            ) {
                $updates['warehouse_id'] = $hqWarehouse->id;
            }

            if ($updates !== []) {
                $existing->update($updates);
            }

            $administratorRole = Role::where('slug', 'administrator')->first();
            if ($administratorRole && !$existing->hasRole('administrator')) {
                $existing->syncRoles([$administratorRole->id]);
            }

            $this->command->info('Administrator already exists. Skipping...');
            return;
        }

        $user = User::create([
            'name' => 'Administrator',
            'email' => 'admin@parcelman.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'warehouse_id' => $hqWarehouse?->id,
        ]);

        $administratorRole = Role::where('slug', 'administrator')->first();
        if ($administratorRole) {
            $user->roles()->attach($administratorRole->id, [
                'assigned_at' => now(),
            ]);
        }

        $this->command->info('Administrator created successfully!');
        $this->command->info('Email: admin@parcelman.com');
        $this->command->info('Password: password');
        $this->command->warn('Please change the password after first login.');
    }
}
