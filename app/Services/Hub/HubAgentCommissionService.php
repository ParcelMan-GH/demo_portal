<?php

namespace App\Services\Hub;

use App\Models\HubAgentCommission;
use App\Models\PlatformSetting;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * The hub agent's per-parcel commission: read the admin's switch and rates, and
 * credit the money exactly once per parcel per direction.
 *
 * ## The shape of the money
 *
 * Unlike the contact agent — whose commission is a band function of a day's
 * cumulative collection — a hub agent earns a flat amount per parcel:
 *
 *   INBOUND  — the item was checked in at the hub (an arrival scan). GHC 0.50.
 *   OUTBOUND — the item left the hub (counter handover, or doorstep dispatch).
 *              GHC 1.00.
 *
 * Two parcels are worth twice one parcel; there is no ladder. So this service
 * does not touch `CommissionTier` at all — it snapshots the configured rate onto
 * the credit row and stores the amount directly.
 *
 * ## On / off, and where the rates live
 *
 * The admin's Commission Rules screen owns a single on/off switch and the two
 * rates, persisted through the same `platform_settings` store the rest of the
 * system already uses (compare `VendorCommissionService`). The keys are below.
 * When the switch is off, `credit()` is a no-op: no row is written and no money
 * is owed. Turning it back on does not retroactively pay items that were handled
 * while it was off — that is the point of a switch an admin controls.
 *
 * ## One credit per item per direction
 *
 * The database's `unique(['shipment_item_id', 'direction'])` is the guarantee. A
 * re-scan of an already checked-in parcel, or a second release of a parcel that
 * has already gone, inserts nothing. `credit()` therefore returns the existing
 * row rather than erroring, and the caller reports it as "already credited".
 */
class HubAgentCommissionService
{
    /** Master on/off switch for hub-agent commission. Stored as '1' / '0'. */
    public const KEY_ENABLED = 'hub_agent_commission.enabled';

    /** Rate, in GHC, for an item checked in at the hub. */
    public const KEY_INBOUND_RATE = 'hub_agent_commission.inbound_rate';

    /** Rate, in GHC, for an item released from the hub. */
    public const KEY_OUTBOUND_RATE = 'hub_agent_commission.outbound_rate';

    /** Defaults the product owner specified. */
    public const DEFAULT_INBOUND_RATE = 0.50;
    public const DEFAULT_OUTBOUND_RATE = 1.00;

    /** Whether hub-agent commission is currently switched on. */
    public function isEnabled(): bool
    {
        // The setting is a string ("1"/"0"), so cast the way the other boolean
        // settings in this codebase are read — a stored "0" must not be truthy.
        return filter_var(
            PlatformSetting::getValue(self::KEY_ENABLED, false),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? false;
    }

    public function inboundRate(): float
    {
        return (float) PlatformSetting::getValue(self::KEY_INBOUND_RATE, self::DEFAULT_INBOUND_RATE);
    }

    public function outboundRate(): float
    {
        return (float) PlatformSetting::getValue(self::KEY_OUTBOUND_RATE, self::DEFAULT_OUTBOUND_RATE);
    }

    /**
     * The rate for a direction, or null for a direction this service does not pay.
     */
    public function rateFor(string $direction): ?float
    {
        return match ($direction) {
            HubAgentCommission::DIRECTION_INBOUND => $this->inboundRate(),
            HubAgentCommission::DIRECTION_OUTBOUND => $this->outboundRate(),
            default => null,
        };
    }

    /**
     * The current switch and rates, in the shape the admin screen round-trips.
     *
     * @return array{enabled: bool, inbound_rate: float, outbound_rate: float}
     */
    public function settings(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'inbound_rate' => $this->inboundRate(),
            'outbound_rate' => $this->outboundRate(),
        ];
    }

    /**
     * Persist the admin's switch and rates.
     *
     * Stored as plain strings, matching how the settings screen writes numbers
     * (`PlatformSetting::setValue` stringifies). Not encrypted: there is nothing
     * secret about a commission rate.
     */
    public function saveSettings(bool $enabled, float $inboundRate, float $outboundRate): void
    {
        PlatformSetting::setValue(
            self::KEY_ENABLED,
            $enabled ? '1' : '0',
            false,
            'Pay hub agents a per-parcel commission (inbound/outbound).'
        );

        PlatformSetting::setValue(
            self::KEY_INBOUND_RATE,
            number_format($inboundRate, 2, '.', ''),
            false,
            'Hub agent commission per parcel checked in at the hub (GHC).'
        );

        PlatformSetting::setValue(
            self::KEY_OUTBOUND_RATE,
            number_format($outboundRate, 2, '.', ''),
            false,
            'Hub agent commission per parcel released from the hub (GHC).'
        );
    }

    /**
     * Credit one parcel's commission for one direction.
     *
     * Returns the credited row, or null when the switch is off or the direction
     * is unknown. A re-scan/re-release returns the row that already exists rather
     * than writing a second one — callers can tell the two apart by comparing the
     * row's `wasRecentlyCreated` flag if they need to.
     */
    public function credit(
        ShipmentItem $item,
        ?Warehouse $hub,
        ?User $agent,
        string $direction,
    ): ?HubAgentCommission {
        if (! $agent) {
            return null;
        }

        if (! $this->isEnabled()) {
            return null;
        }

        $rate = $this->rateFor($direction);

        if ($rate === null) {
            return null;
        }

        // A rate of zero means "pay nothing" for this direction. Writing a 0.00
        // row would clutter the ledger with credits that are not credits, so it
        // is treated as off — the same convention VendorCommissionService uses.
        if ($rate <= 0) {
            return null;
        }

        $attributes = [
            'shipment_item_id' => $item->getKey(),
            'direction' => $direction,
        ];

        // The unique index is the real guard. `firstOrCreate` handles the common
        // re-scan; the catch handles the genuine race, where two concurrent scans
        // both miss the SELECT and one INSERT loses to the index.
        try {
            return HubAgentCommission::query()->firstOrCreate($attributes, [
                'user_id' => $agent->getKey(),
                'hub_id' => $hub?->getKey(),
                'amount' => $rate,
                'rate' => $rate,
                'status' => HubAgentCommission::STATUS_APPROVED,
                'credited_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Lost the race; the other request's row is the one that counts.
            Log::info('Hub agent commission insert lost a race; using the existing credit', [
                'shipment_item_id' => $item->getKey(),
                'direction' => $direction,
                'error' => $e->getMessage(),
            ]);

            return HubAgentCommission::query()->where($attributes)->first();
        }
    }

    /**
     * Whether this parcel already has a credit in this direction.
     */
    public function alreadyCredited(ShipmentItem $item, string $direction): bool
    {
        return HubAgentCommission::query()
            ->where('shipment_item_id', $item->getKey())
            ->where('direction', $direction)
            ->exists();
    }

    /**
     * A hub agent's running total, for a future earnings screen.
     *
     * @return array{total: float, inbound: float, outbound: float, pending: float}
     */
    public function summaryFor(User $agent): array
    {
        $rows = HubAgentCommission::query()->where('user_id', $agent->getKey())->get();

        $inbound = (float) $rows->where('direction', HubAgentCommission::DIRECTION_INBOUND)->sum('amount');
        $outbound = (float) $rows->where('direction', HubAgentCommission::DIRECTION_OUTBOUND)->sum('amount');
        $pending = (float) $rows->where('status', HubAgentCommission::STATUS_APPROVED)->sum('amount');

        return [
            'total' => round($inbound + $outbound, 2),
            'inbound' => round($inbound, 2),
            'outbound' => round($outbound, 2),
            'pending' => round($pending, 2),
        ];
    }
}
