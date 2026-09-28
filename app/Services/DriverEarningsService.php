<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverPayoutRequest;
use App\Models\TransportManifest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * What a driver has earned, and what they can still withdraw.
 *
 * There was no driver earnings table, so this derives the figures from records
 * that do exist: the manifests assigned to the driver, and the delivery fee on
 * each shipment item those manifests carried.
 *
 * The rate assumption is worth stating plainly. A driver is credited the
 * `delivery_fee` of every item they moved, in full. There is no driver
 * commission table in the schema (`commission_tiers` is for vendors and has no
 * rows), so this is the only defensible figure available; when a real rate card
 * exists, `manifestAmount()` is the single place that has to change.
 *
 * Only finished journeys count. `cancelled` is deliberately excluded — it is in
 * STATUSES_HISTORY because the driver should still see it in their history, but
 * a cancelled trip did not earn anything.
 */
class DriverEarningsService
{
    public const CURRENCY = 'GHS';

    /** How many recent trips the screen shows. */
    private const RECENT_LIMIT = 20;

    /**
     * Manifests this driver has finished.
     *
     * @return \Illuminate\Database\Eloquent\Builder<TransportManifest>
     */
    private function completedQuery(Driver $driver)
    {
        return TransportManifest::query()
            ->where('assigned_driver_id', $driver->id)
            ->whereIn('status', [
                TransportManifest::STATUS_ARRIVED,
                TransportManifest::STATUS_RECEIVED,
            ])
            ->with([
                'originWarehouse:id,name',
                'destinationWarehouse:id,name',
                'items.shipmentItem:id,tracking_code,delivery_fee',
            ]);
    }

    /** What one manifest earned: the delivery fees of everything on it. */
    private function manifestAmount(TransportManifest $manifest): float
    {
        return (float) $manifest->items->sum(
            fn ($line) => (float) ($line->shipmentItem?->delivery_fee ?? 0)
        );
    }

    /**
     * Already-requested money. A rejected or cancelled request is not counted,
     * so a mistake does not permanently reduce what the driver can withdraw.
     */
    private function consumedByPayouts(Driver $driver): float
    {
        return (float) DriverPayoutRequest::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', DriverPayoutRequest::STATUSES_CONSUMING_BALANCE)
            ->sum('amount');
    }

    /**
     * The payload behind GET /driver/earnings.
     *
     * @return array<string, mixed>
     */
    public function summary(Driver $driver): array
    {
        $manifests = $this->completedQuery($driver)->orderByDesc('id')->get();

        $earned = 0.0;
        $thisMonth = 0.0;

        foreach ($manifests as $manifest) {
            $amount = $this->manifestAmount($manifest);
            $earned += $amount;

            $finishedAt = $manifest->received_at ?? $manifest->arrived_at;

            if ($finishedAt instanceof Carbon && $finishedAt->isSameMonth(now())) {
                $thisMonth += $amount;
            }
        }

        $consumed = $this->consumedByPayouts($driver);
        $balance = round($earned - $consumed, 2);

        $recentTrips = $manifests
            ->take(self::RECENT_LIMIT)
            ->map(fn (TransportManifest $manifest) => $this->formatTrip($manifest))
            ->values()
            ->all();

        return [
            'currency' => self::CURRENCY,
            'balance' => max($balance, 0.0),
            'completed_trips' => $manifests->count(),
            'this_month' => round($thisMonth, 2),
            'total_earned' => round($earned, 2),
            'total_withdrawn' => round($consumed, 2),
            'recent_trips' => $recentTrips,
            'balance_updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * One trip, in the shape the earnings screen lists.
     *
     * @return array<string, mixed>
     */
    private function formatTrip(TransportManifest $manifest): array
    {
        $origin = $manifest->originWarehouse?->name;
        $destination = $manifest->destinationWarehouse?->name;

        return [
            'id' => (string) $manifest->id,
            'manifest_code' => $manifest->manifest_number,
            'route' => $origin && $destination
                ? "{$origin} -> {$destination}"
                : ($origin ?? $destination ?? 'Route not recorded'),
            'package_count' => (int) $manifest->items->count(),
            'amount' => round($this->manifestAmount($manifest), 2),
            'currency' => self::CURRENCY,
            'status' => $manifest->status,
            'completed_at' => ($manifest->received_at ?? $manifest->arrived_at)?->toIso8601String(),
        ];
    }

    /**
     * Request a withdrawal.
     *
     * The amount is re-checked against a freshly computed balance inside a
     * transaction. The balance shown on screen could be minutes old, and two
     * taps on a slow connection must not be able to withdraw the same money
     * twice — which is why the rows are locked before the check.
     *
     * @return array{success: bool, message: string, data?: array<string, mixed>, status?: int}
     */
    public function requestPayout(Driver $driver, float $amount, ?string $phone = null, ?string $method = null): array
    {
        if ($amount <= 0) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Enter an amount greater than zero.',
            ];
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($driver, $amount, $phone, $method) {
            // Lock what the balance is computed from, so a concurrent request
            // cannot slip between the calculation and the insert.
            DriverPayoutRequest::query()
                ->where('driver_id', $driver->id)
                ->lockForUpdate()
                ->get();

            $balance = (float) $this->summary($driver)['balance'];

            if ($amount > $balance + 0.001) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => sprintf(
                        'That is more than your available balance of %s %s.',
                        self::CURRENCY,
                        number_format($balance, 2)
                    ),
                    'data' => ['balance' => $balance, 'currency' => self::CURRENCY],
                ];
            }

            $payout = DriverPayoutRequest::query()->create([
                'driver_id' => $driver->id,
                'reference' => $this->generateReference(),
                'amount' => round($amount, 2),
                'status' => DriverPayoutRequest::STATUS_PENDING,
                'phone' => $phone ?: $driver->phone,
                'method' => $method ?: 'mobile_money',
                'balance_at_request' => $balance,
                'requested_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Payout requested successfully.',
                'data' => [
                    'payout' => [
                        'id' => (string) $payout->id,
                        'reference' => $payout->reference,
                        'amount' => (float) $payout->amount,
                        'currency' => self::CURRENCY,
                        'status' => $payout->status,
                        'phone' => $payout->phone,
                        'method' => $payout->method,
                        'requested_at' => $payout->requested_at?->toIso8601String(),
                    ],
                    'balance_after' => round($balance - $amount, 2),
                    'currency' => self::CURRENCY,
                ],
            ];
        });
    }

    /** Short, unambiguous, and readable over the phone. */
    private function generateReference(): string
    {
        do {
            $reference = 'PO-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (DriverPayoutRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
