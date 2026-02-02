<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if super admin already exists
        if (User::where('email', 'admin@parcelman.com')->exists()) {
            $this->command->info('Super Admin already exists. Skipping...');
            return;
        }

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@parcelman.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Assign Super Admin role
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        if ($superAdminRole) {
            $user->roles()->attach($superAdminRole->id, [
                'assigned_at' => now(),
            ]);
        }

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: admin@parcelman.com');
        $this->command->info('Password: password');
        $this->command->warn('Please change the password after first login.');
    }
}
