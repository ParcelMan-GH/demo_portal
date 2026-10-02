<?php

namespace App\Support;

/**
 * Which accounts are allowed to be a driver.
 *
 * The driver app signs in with these two role slugs (see
 * `DriverAuthController::login`, which requires the requested portal to match
 * the account's role). That check ran at sign-in and nowhere else.
 *
 * `ResolvesActingDriver` then created a `drivers` row for any account it could
 * not match by phone — so any authenticated account with a phone number, doing
 * anything on `/v1/driver/*`, silently acquired an active rider profile with
 * pickup and delivery rights. The profile is real: it appears in driver
 * listings and can be assigned work.
 *
 * One rule, consulted from the three places that provision a driver profile.
 * `ResolvesActingDriver` is the trait; the rider-team controllers carry their
 * own private copies of the same method.
 */
class DriverRoles
{
    /**
     * The roles the driver app authenticates with.
     *
     * @var array<int, string>
     */
    public const DRIVER = ['transporter', 'rider'];

    /**
     * Whether this account may hold a `drivers` row.
     *
     * Fails closed: an account with no `roles` relation, or with roles, none of
     * which is a driver role, is refused. The relation is checked because not
     * every authenticatable model carries one, and treating a missing relation
     * as "allowed" would recreate the hole this closes.
     */
    public static function accountMayDrive(?object $user): bool
    {
        if (! $user || ! method_exists($user, 'roles')) {
            return false;
        }

        $held = $user->roles
            ->pluck('slug')
            ->map(fn ($slug) => strtolower((string) $slug));

        return $held->intersect(self::DRIVER)->isNotEmpty();
    }
}
