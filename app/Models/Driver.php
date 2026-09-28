<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens;

    public const CAPABILITY_PICKUP = 'pickup';

    public const CAPABILITY_TRANSPORT = 'transport';

    public const CAPABILITY_DELIVERY = 'delivery';

    public const CAPABILITY_BUS_HANDOFF = 'bus_handoff';

    public const CAPABILITIES = [
        self::CAPABILITY_PICKUP,
        self::CAPABILITY_TRANSPORT,
        self::CAPABILITY_DELIVERY,
        self::CAPABILITY_BUS_HANDOFF,
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'photo_path',
        'password',
        'vehicle_type',
        'vehicle_number',
        'license_number',
        'make_model',
        'max_capacity',
        'base_location',
        'emergency_contact',
        'notification_settings',
        'status',
        'is_active',
        'task_capabilities',
        'last_login_at',
        'fcm_token',
    ];

    /**
     * Alert toggles, and what they are when a driver has never touched them.
     *
     * ON by default: a driver who has never opened the screen should still get
     * told about a batch they have been assigned.
     */
    public const DEFAULT_NOTIFICATION_PREFERENCES = [
        'batch_alerts' => true,
        'route_updates' => true,
        'payout_alerts' => true,
        'sound_vibration' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'notification_settings' => 'array',
        'is_active' => 'boolean',
        'task_capabilities' => 'array',
        'last_login_at' => 'datetime',
    ];

    /**
     * The driver's alert toggles, with defaults filled in.
     *
     * Reading through this rather than `$driver->notification_settings` directly
     * means a driver who has never saved anything, or one whose stored array is
     * missing a key added later, still gets a complete set of answers.
     *
     * @return array<string, bool>
     */
    public function notificationPreferences(): array
    {
        $stored = $this->notification_settings;

        if (! is_array($stored)) {
            return self::DEFAULT_NOTIFICATION_PREFERENCES;
        }

        $preferences = self::DEFAULT_NOTIFICATION_PREFERENCES;

        foreach ($preferences as $key => $default) {
            if (array_key_exists($key, $stored)) {
                $preferences[$key] = (bool) $stored[$key];
            }
        }

        return $preferences;
    }

    /**
     * Get normalized driver capabilities.
     *
     * @return array<int, string>
     */
    public function getCapabilities(): array
    {
        $raw = $this->task_capabilities;
        if ($raw === null) {
            return [self::CAPABILITY_PICKUP];
        }

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($value) => is_string($value) ? strtolower(trim($value)) : null,
            $raw
        ), static fn ($value) => in_array($value, self::CAPABILITIES, true))));
    }

    /**
     * Check whether driver can handle a given assignment capability.
     */
    public function hasCapability(string $capability): bool
    {
        return in_array(strtolower($capability), $this->getCapabilities(), true);
    }

    /**
     * Activity logs for this driver.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(DriverActivityLog::class);
    }

    /**
     * Get all pickup assignments for this driver.
     */
    public function pickupAssignments(): HasMany
    {
        return $this->hasMany(PickupAssignment::class);
    }

    /**
     * Get the active pickup assignment for this driver.
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(PickupAssignment::class)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latestOfMany();
    }

    public function transportManifests(): HasMany
    {
        return $this->hasMany(TransportManifest::class, 'assigned_driver_id');
    }

    public function deliveryRuns(): HasMany
    {
        return $this->hasMany(DeliveryRun::class, 'assigned_driver_id');
    }

    public function assignmentEvents(): HasMany
    {
        return $this->hasMany(RiderAssignmentEvent::class);
    }

    public function riderTeamMemberships(): HasMany
    {
        return $this->hasMany(RiderTeamMembership::class);
    }

    public function ledRiderTeams(): HasMany
    {
        return $this->riderTeamMemberships()
            ->where('role', RiderTeamMembership::ROLE_LEADER)
            ->where('is_active', true)
            ->whereNull('removed_at');
    }

    public function riderTeamHandovers(): HasMany
    {
        return $this->hasMany(RiderTeamHandover::class, 'receiver_driver_id');
    }

    public function packageLocationChanges(): HasMany
    {
        return $this->hasMany(RiderPackageLocationChange::class);
    }

    public function sentPackageTransfers(): HasMany
    {
        return $this->hasMany(RiderPackageTransfer::class, 'from_driver_id');
    }

    public function receivedPackageTransfers(): HasMany
    {
        return $this->hasMany(RiderPackageTransfer::class, 'to_driver_id');
    }
}
