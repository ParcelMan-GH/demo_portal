<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Works out which `drivers` row the caller is acting as.
 *
 * A driver signs in with a `users` account, but almost every driver operation
 * needs the `drivers` row — manifests are assigned to `assigned_driver_id`,
 * notification logs are keyed by `notifiable_id`, photos live on
 * `drivers.photo_path`. The two are linked by phone or email.
 *
 * This resolution existed in two controllers and was missing from the rest,
 * which is the root of a whole family of bugs: `$request->user()` returns the
 * `users` row, and passing that into a `Driver`-typed method is a TypeError —
 * an HTTP 500 with the message "Server Error", which is what a driver saw when
 * they tried to claim a scanned batch. Where the parameter was untyped it failed
 * more quietly: notifications filtered on a user id matched nothing, and
 * `$user->notificationPreferences()` was an undefined method.
 *
 * So resolve once, here, and never reach for `$request->user()` on a driver
 * route again.
 */
trait ResolvesActingDriver
{
    private ?Driver $resolvedActingDriver = null;

    protected function actingDriver(Request $request): Driver
    {
        if ($this->resolvedActingDriver) {
            return $this->resolvedActingDriver;
        }

        $user = $request->user();

        // A token issued directly against a `drivers` row — used by tooling and
        // by drivers who authenticate that way — needs no mapping.
        if ($user instanceof Driver) {
            return $this->resolvedActingDriver = $user;
        }

        $phone = trim((string) ($user?->phone ?? ''));
        $email = trim((string) ($user?->email ?? ''));
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        // Ghana numbers are stored inconsistently (+233…, 233…, 0…), so the last
        // nine digits are used as a fallback signature for comparison.
        $tail = strlen($digits) >= 9 ? substr($digits, -9) : '';
        $normalisePhone = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '')";

        $driver = null;

        if ($phone !== '' || $email !== '') {
            $driver = Driver::query()
                ->where(function ($query) use ($phone, $email, $digits, $tail, $normalisePhone) {
                    if ($phone !== '') {
                        $query->where('phone', $phone);
                    }
                    if ($digits !== '') {
                        $query->orWhereRaw("{$normalisePhone} = ?", [$digits]);
                    }
                    if ($tail !== '') {
                        $query->orWhereRaw("RIGHT({$normalisePhone}, 9) = ?", [$tail]);
                    }
                    if ($email !== '') {
                        $query->orWhere('email', $email);
                    }
                })
                ->orderByDesc('is_active')
                ->first();
        }

        if (! $driver && $phone !== '') {
            $driver = $this->provisionDriverProfile($user, $phone);
        }

        if (! $driver) {
            abort(403, 'No rider profile is linked to this account yet. Please contact your warehouse supervisor.');
        }

        return $this->resolvedActingDriver = $driver;
    }

    /**
     * Create the `drivers` row for an account that has none, so a newly hired
     * driver is not locked out of the app until someone adds them by hand.
     */
    private function provisionDriverProfile(?object $user, string $phone): ?Driver
    {
        $fallbackEmail = 'rider-'.(preg_replace('/\D+/', '', $phone) ?: 'unknown').'@parcelmanexpress.local';

        foreach (array_values(array_unique(array_filter([$user?->email, $fallbackEmail]))) as $email) {
            try {
                return Driver::create([
                    'name' => (string) ($user?->name ?: 'Rider'),
                    'email' => $email,
                    'phone' => $phone,
                    'password' => bcrypt(bin2hex(random_bytes(16))),
                    'vehicle_type' => 'motorcycle',
                    'status' => 'available',
                    'is_active' => true,
                    'task_capabilities' => ['pickup', 'delivery'],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Could not auto-provision rider profile', ['error' => $e->getMessage()]);
            }
        }

        return null;
    }
}
