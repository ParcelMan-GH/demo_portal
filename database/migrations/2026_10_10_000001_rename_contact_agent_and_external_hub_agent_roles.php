<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename two role display names.
 *
 *   `contact_agent`       Contact Agent       -> Payment Desk
 *   `external_hub_agent`  External Hub Agent  -> ----- Agents
 *
 * ## Only `roles.name` changes
 *
 * `roles.slug` is the identity the rest of the system keys on — the
 * `role:contact_agent` middleware, the route group gating the whole agent API,
 * every role-assignment row, and the mobile apps' own role checks. Renaming a
 * slug would be a far larger and riskier change than a display rename, so it is
 * deliberately not done here.
 *
 * `roles.name` is unique, which is fine: neither new name collides with the
 * other five roles (Admin, Warehouse Supervisor, External Bus Handoff Agent,
 * Transporter, Rider).
 *
 * ## Why a migration as well as the seeder
 *
 * `RoleSeeder` carries the same names for a fresh install, but seeding does not
 * run on deploy — the live rows would otherwise keep the old wording
 * indefinitely. This updates them, and `down()` puts them back.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $renamed = [
        'contact_agent' => 'Payment Desk',
        'external_hub_agent' => '----- Agents',
    ];

    /**
     * @var array<string, string>
     */
    private array $was = [
        'contact_agent' => 'Contact Agent',
        'external_hub_agent' => 'External Hub Agent',
    ];

    public function up(): void
    {
        $this->apply($this->renamed);
    }

    public function down(): void
    {
        $this->apply($this->was);
    }

    /**
     * @param  array<string, string>  $names
     */
    private function apply(array $names): void
    {
        foreach ($names as $slug => $name) {
            // Touches only rows that exist: a fresh database that has not been
            // seeded yet simply matches nothing, and the seeder then writes the
            // new names directly.
            DB::table('roles')
                ->where('slug', $slug)
                ->update(['name' => $name]);
        }
    }
};
