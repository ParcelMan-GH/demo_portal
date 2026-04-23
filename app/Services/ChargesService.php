<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\PlatformSetting;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Manages the per-shipment charges ledger: pickup fees, delivery fees (per
 * package), station fees (expenses), etc. Each charge is a ledger line with
 * its own payer, direction (revenue vs expense), due-stage, and payment
 * state.
 *
 * Permission enforcement lives in the controllers; this service trusts the
 * caller. Actors (admin or driver) are recorded on each line for audit.
 */
class ChargesService
{
    public const PICKUP_FEE_DEFAULT_KEY = 'charges.pickup_fee_default';
    public const DEFAULT_CURRENCY       = 'GHS';

    /**
     * Global default pickup fee (overridable per shipment).
     */
    public function getDefaultPickupFee(): float
    {
        return (float) PlatformSetting::getValue(self::PICKUP_FEE_DEFAULT_KEY, 0.00);
    }

    /**
     * Add a charge line. Caller provides the type/payer/stage/amount; the
     * service fills in `direction`, default currency, and the recording
     * actor.
     *
     * @param array $data {
     *     charge_type, payer_type, due_stage, amount,
     *     shipment_item_id?, currency?, status?,
     *     notes?, payment_method?, payment_reference?,
     *     delivery_run_stop_id?, pickup_assignment_id?
     * }
     */
    public function addCharge(
        Shipment $shipment,
        array $data,
        User|Driver|null $actor = null,
    ): ShipmentCharge {
        $payerType = $data['payer_type'];
        $status = $data['status'] ?? ShipmentCharge::STATUS_PENDING;

        $payload = [
            'shipment_id'           => $shipment->id,
            'shipment_item_id'      => $data['shipment_item_id'] ?? null,
            'charge_type'           => $data['charge_type'],
            'payer_type'            => $payerType,
            'direction'             => ShipmentCharge::directionFor($payerType),
            'due_stage'             => $data['due_stage'],
            'amount'                => round((float) $data['amount'], 2),
            'currency'              => $data['currency'] ?? self::DEFAULT_CURRENCY,
            'status'                => $status,
            'notes'                 => $data['notes'] ?? null,
            'pickup_assignment_id'  => $data['pickup_assignment_id'] ?? null,
            'delivery_run_stop_id'  => $data['delivery_run_stop_id'] ?? null,
        ];

        // If the caller is already marking it paid (e.g., driver records a fee
        // they collected in cash on the spot), capture the payment detail.
        if ($status === ShipmentCharge::STATUS_PAID) {
            $payload['paid_at']           = now();
            $payload['payment_method']    = $data['payment_method'] ?? null;
            $payload['payment_reference'] = $data['payment_reference'] ?? null;
        }

        $this->stampActor($payload, $actor);

        return ShipmentCharge::create($payload);
    }

    /**
     * Edit an outstanding charge. Paid/waived/cancelled charges are
     * immutable — the caller should cancel and re-create instead.
     *
     * @return array{success: bool, message: string, charge?: ShipmentCharge}
     */
    public function updateCharge(
        ShipmentCharge $charge,
        array $data,
        User|Driver|null $actor = null,
    ): array {
        if (!$charge->isOutstanding()) {
            return [
                'success' => false,
                'message' => 'Only draft or pending charges can be edited.',
            ];
        }

        $fields = [];

        if (array_key_exists('amount', $data)) {
            $fields['amount'] = round((float) $data['amount'], 2);
        }
        if (array_key_exists('notes', $data)) {
            $fields['notes'] = $data['notes'];
        }
        if (array_key_exists('due_stage', $data)) {
            $fields['due_stage'] = $data['due_stage'];
        }
        if (array_key_exists('payer_type', $data)) {
            $fields['payer_type'] = $data['payer_type'];
            $fields['direction']  = ShipmentCharge::directionFor($data['payer_type']);
        }
        if (array_key_exists('charge_type', $data)) {
            $fields['charge_type'] = $data['charge_type'];
        }

        $charge->update($fields);

        return ['success' => true, 'message' => 'Charge updated.', 'charge' => $charge->fresh()];
    }

    public function markPaid(
        ShipmentCharge $charge,
        string $paymentMethod,
        ?string $reference = null,
        User|Driver|null $actor = null,
    ): array {
        if ($charge->status === ShipmentCharge::STATUS_PAID) {
            return ['success' => false, 'message' => 'Charge is already marked paid.'];
        }

        if (in_array($charge->status, [ShipmentCharge::STATUS_CANCELLED, ShipmentCharge::STATUS_WAIVED], true)) {
            return ['success' => false, 'message' => 'Cancelled or waived charges cannot be paid.'];
        }

        $charge->update([
            'status'            => ShipmentCharge::STATUS_PAID,
            'paid_at'           => now(),
            'payment_method'    => $paymentMethod,
            'payment_reference' => $reference,
        ]);

        return ['success' => true, 'message' => 'Marked paid.', 'charge' => $charge->fresh()];
    }

    public function waive(
        ShipmentCharge $charge,
        ?string $reason = null,
        User|Driver|null $actor = null,
    ): array {
        if (!$charge->isOutstanding()) {
            return ['success' => false, 'message' => 'Only outstanding charges can be waived.'];
        }

        $charge->update([
            'status'       => ShipmentCharge::STATUS_WAIVED,
            'waive_reason' => $reason,
        ]);

        return ['success' => true, 'message' => 'Charge waived.', 'charge' => $charge->fresh()];
    }

    public function cancel(ShipmentCharge $charge, User|Driver|null $actor = null): array
    {
        if ($charge->status === ShipmentCharge::STATUS_CANCELLED) {
            return ['success' => false, 'message' => 'Already cancelled.'];
        }
        if ($charge->status === ShipmentCharge::STATUS_PAID) {
            return ['success' => false, 'message' => 'Paid charges cannot be cancelled. Waive instead.'];
        }

        $charge->update(['status' => ShipmentCharge::STATUS_CANCELLED]);
        return ['success' => true, 'message' => 'Charge cancelled.', 'charge' => $charge->fresh()];
    }

    /**
     * Totals + outstanding list for a shipment. Useful for the shipment page
     * summary bar and for downstream "is delivery blocked?" checks.
     */
    public function summariseShipment(Shipment $shipment): array
    {
        $charges = $shipment->charges()->get();

        $activeNotCancelled = $charges->whereNotIn('status', [
            ShipmentCharge::STATUS_CANCELLED,
            ShipmentCharge::STATUS_WAIVED,
        ]);

        $revenueTotal = (float) $activeNotCancelled
            ->where('direction', ShipmentCharge::DIRECTION_REVENUE)
            ->sum('amount');

        $expenseTotal = (float) $activeNotCancelled
            ->where('direction', ShipmentCharge::DIRECTION_EXPENSE)
            ->sum('amount');

        $paidRevenue = (float) $activeNotCancelled
            ->where('direction', ShipmentCharge::DIRECTION_REVENUE)
            ->where('status', ShipmentCharge::STATUS_PAID)
            ->sum('amount');

        $outstanding = $charges
            ->filter(fn (ShipmentCharge $c) => $c->isOutstanding())
            ->values();

        return [
            'revenue_total'    => $revenueTotal,
            'revenue_paid'     => $paidRevenue,
            'revenue_pending'  => round($revenueTotal - $paidRevenue, 2),
            'expense_total'    => $expenseTotal,
            'net'              => round($revenueTotal - $expenseTotal, 2),
            'currency'         => self::DEFAULT_CURRENCY,
            'outstanding_count' => $outstanding->count(),
        ];
    }

    /**
     * Charges that are `pending` with `due_stage = before_delivery` and tied
     * to items on this shipment. Used by the delivery-run gating logic.
     *
     * @return \Illuminate\Support\Collection<ShipmentCharge>
     */
    public function outstandingBeforeDeliveryCharges(Shipment $shipment)
    {
        return $shipment->charges()
            ->where('due_stage', ShipmentCharge::STAGE_BEFORE_DELIVERY)
            ->whereIn('status', [ShipmentCharge::STATUS_DRAFT, ShipmentCharge::STATUS_PENDING])
            ->get();
    }

    /**
     * Warnings about unpaid `before_delivery` charges across a set of
     * shipments. Used by the delivery-run dispatch flow to soft-warn admins
     * before they send a driver out with unpaid parcels.
     *
     * @param iterable<int> $shipmentIds
     * @return array<int, array{shipment_id:int, shipment_number:?string, outstanding_count:int, outstanding_total:float}>
     */
    public function dispatchWarningsForShipments(iterable $shipmentIds): array
    {
        $shipmentIds = array_values(array_filter(array_map('intval', (array) $shipmentIds)));
        if (empty($shipmentIds)) {
            return [];
        }

        $charges = ShipmentCharge::query()
            ->whereIn('shipment_id', $shipmentIds)
            ->where('due_stage', ShipmentCharge::STAGE_BEFORE_DELIVERY)
            ->whereIn('status', [ShipmentCharge::STATUS_DRAFT, ShipmentCharge::STATUS_PENDING])
            ->get(['id', 'shipment_id', 'amount']);

        if ($charges->isEmpty()) {
            return [];
        }

        $shipments = Shipment::query()
            ->whereIn('id', $charges->pluck('shipment_id')->unique())
            ->pluck('shipment_number', 'id');

        return $charges
            ->groupBy('shipment_id')
            ->map(fn ($group, $shipmentId) => [
                'shipment_id'        => (int) $shipmentId,
                'shipment_number'    => $shipments[$shipmentId] ?? null,
                'outstanding_count'  => $group->count(),
                'outstanding_total'  => round((float) $group->sum('amount'), 2),
            ])
            ->values()
            ->all();
    }

    /**
     * Seed a pickup-fee charge at shipment creation (or on demand). Idempotent
     * per shipment: existing active pickup-fee rows block re-creation.
     */
    public function seedPickupFee(Shipment $shipment, ?float $amount = null, User|Driver|null $actor = null): ?ShipmentCharge
    {
        $existing = $shipment->charges()
            ->where('charge_type', ShipmentCharge::TYPE_PICKUP_FEE)
            ->whereNotIn('status', [ShipmentCharge::STATUS_CANCELLED])
            ->first();

        if ($existing) {
            return $existing;
        }

        $amount = $amount ?? $this->getDefaultPickupFee();
        if ($amount <= 0) {
            return null; // no pickup fee configured; nothing to create
        }

        return $this->addCharge($shipment, [
            'charge_type' => ShipmentCharge::TYPE_PICKUP_FEE,
            'payer_type'  => ShipmentCharge::PAYER_VENDOR,
            'due_stage'   => ShipmentCharge::STAGE_AT_PICKUP,
            'amount'      => $amount,
            'status'      => ShipmentCharge::STATUS_PENDING,
        ], $actor);
    }

    /**
     * Stamp whichever actor created the line onto the payload.
     */
    private function stampActor(array &$payload, User|Driver|null $actor): void
    {
        if ($actor instanceof User) {
            $payload['recorded_by_admin_id'] = $actor->id;
        } elseif ($actor instanceof Driver) {
            $payload['recorded_by_driver_id'] = $actor->id;
        }
    }
}
