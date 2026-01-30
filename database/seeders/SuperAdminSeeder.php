<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\Admin;
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
        if (Admin::where('email', 'admin@parcelman.com')->exists()) {
            $this->command->info('Super Admin already exists. Skipping...');
            return;
        }

        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@parcelman.com',
            'password' => Hash::make('password'),
            'role' => AdminRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: admin@parcelman.com');
        $this->command->info('Password: password');
        $this->command->warn('Please change the password after first login.');
    }
}
