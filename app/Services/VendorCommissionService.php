<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\PlatformSetting;
use App\Models\VendorEarning;
use App\Models\VendorPayout;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class VendorCommissionService
{
    public function isEnabled(): bool
    {
        return (bool) PlatformSetting::getValue('vendor_commission.enabled', false);
    }

    public function getRatePerPackage(): float
    {
        return (float) PlatformSetting::getValue('vendor_commission.rate_per_package', 2.00);
    }

    /**
     * Effective commission rate for a specific vendor: the per-vendor override
     * if set (including 0, which means "no commission for this vendor"),
     * otherwise the global default.
     */
    public function getRatePerPackageFor(Vendor $vendor): float
    {
        if ($vendor->commission_rate_override !== null) {
            return (float) $vendor->commission_rate_override;
        }

        return $this->getRatePerPackage();
    }

    public function getMinPayout(): float
    {
        return (float) PlatformSetting::getValue('vendor_commission.min_payout', 20.00);
    }

    public function createEarningsForStop(DeliveryRunStop $stop): int
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        $globalRate = $this->getRatePerPackage();
        $status = VendorEarning::STATUS_APPROVED;

        $items = DeliveryRunItem::where('delivery_run_stop_id', $stop->id)
            ->with('shipmentItem.shipment.vendor')
            ->get();

        $created = 0;
        $rateCache = [];

        foreach ($items as $runItem) {
            $shipmentItem = $runItem->shipmentItem;
            if (!$shipmentItem || !$shipmentItem->shipment) {
                continue;
            }

            $vendor = $shipmentItem->shipment->vendor;
            $vendorId = $shipmentItem->shipment->vendor_id;
            if (!$vendorId || !$vendor) {
                continue;
            }

            if (!array_key_exists($vendorId, $rateCache)) {
                $rateCache[$vendorId] = $this->getRatePerPackageFor($vendor);
            }
            $rate = $rateCache[$vendorId];

            // Rate of 0 = "no commission for this vendor" → skip earning creation.
            if ($rate <= 0) {
                continue;
            }

            $exists = VendorEarning::where('shipment_item_id', $shipmentItem->id)->exists();
            if ($exists) {
                continue;
            }

            VendorEarning::create([
                'vendor_id' => $vendorId,
                'shipment_id' => $shipmentItem->shipment_id,
                'shipment_item_id' => $shipmentItem->id,
                'delivery_run_stop_id' => $stop->id,
                'amount' => $rate,
                'status' => $status,
            ]);

            $created++;
        }

        return $created;
    }

    public function getVendorSummary(Vendor $vendor): array
    {
        $totalEarned = VendorEarning::where('vendor_id', $vendor->id)->sum('amount');
        $approvedBalance = VendorEarning::where('vendor_id', $vendor->id)
            ->where('status', VendorEarning::STATUS_APPROVED)
            ->whereNull('payout_id')
            ->sum('amount');
        $totalPaid = VendorPayout::where('vendor_id', $vendor->id)
            ->whereIn('status', [VendorPayout::STATUS_SENT, VendorPayout::STATUS_CONFIRMED])
            ->sum('amount');
        $pendingPayout = VendorPayout::where('vendor_id', $vendor->id)
            ->where('status', VendorPayout::STATUS_PENDING)
            ->sum('amount');
        $lastPayout = VendorPayout::where('vendor_id', $vendor->id)
            ->whereIn('status', [VendorPayout::STATUS_SENT, VendorPayout::STATUS_CONFIRMED])
            ->latest('confirmed_at')
            ->latest('sent_at')
            ->latest()
            ->first();

        return [
            'total_earned' => (float) $totalEarned,
            'available_balance' => (float) $approvedBalance,
            'total_paid' => (float) $totalPaid,
            'pending_payout' => (float) $pendingPayout,
            'last_payout_amount' => $lastPayout ? (float) $lastPayout->amount : 0,
            'last_payout_at' => $lastPayout?->confirmed_at?->toIso8601String() ?? $lastPayout?->sent_at?->toIso8601String() ?? $lastPayout?->created_at?->toIso8601String(),
            'min_payout' => $this->getMinPayout(),
            'can_request_payout' => $approvedBalance >= $this->getMinPayout(),
            'currency' => 'GHS',
            'payout_account' => [
                'is_set' => filled($vendor->payout_momo_network) && filled($vendor->payout_account_name) && filled($vendor->payout_account_number),
                'method' => 'momo',
                'network' => $vendor->payout_momo_network,
                'account_name' => $vendor->payout_account_name,
                'account_number' => $vendor->payout_account_number,
                'updated_at' => $vendor->payout_account_updated_at?->toIso8601String(),
            ],
        ];
    }

    public function createPayout(Vendor $vendor, float $amount, int $adminId, array $data = []): array
    {
        /*
         * No balance check here on purpose.
         *
         * It used to run at this point — outside any transaction, on a value read
         * without a lock — and that was the whole defect: two submissions of the
         * same amount both read the same approved balance, both passed, and both
         * created a payout against earnings that only covered one of them. The
         * vendor was paid twice. The authoritative check now lives inside the
         * transaction below, under a row lock.
         *
         * `getVendorSummary` is still read for the messages it produces, but only
         * after the lock is held.
         */
        if ($amount < $this->getMinPayout()) {
            return ['success' => false, 'message' => 'Minimum payout amount is GHS ' . number_format($this->getMinPayout(), 2)];
        }

        $paymentMethod = $data['payment_method'] ?? 'momo';
        $paymentPhone = $data['payment_phone'] ?? $vendor->payout_account_number;

        if ($paymentMethod === 'momo') {
            if (!filled($vendor->payout_momo_network) || !filled($vendor->payout_account_name) || !filled($vendor->payout_account_number)) {
                return ['success' => false, 'message' => 'This vendor has no MoMo payout account set.'];
            }

            $paymentPhone = $vendor->payout_account_number;
            $localPhone = PhoneHelper::toLocal((string) $paymentPhone);

            if (!$localPhone || !preg_match('/^0(?:2\d|5\d)\d{7}$/', $localPhone)) {
                return ['success' => false, 'message' => 'Enter a valid 10-digit Ghana phone number'];
            }

            $paymentPhone = PhoneHelper::format($paymentPhone);
        }

        return DB::transaction(function () use ($vendor, $amount, $adminId, $data, $paymentMethod, $paymentPhone) {
            /*
             * Lock the vendor first.
             *
             * Locking the eligible earnings would be the obvious move, but it locks
             * *nothing* when the vendor has none — and two simultaneous first-ever
             * payouts are exactly the case that must not both succeed. Locking the
             * vendor row serialises every payout attempt for this vendor, whatever
             * their earnings look like.
             *
             * `withTrashed` because Vendor soft-deletes: without it a trashed
             * vendor would lock no row and fall through to the unlocked behaviour
             * this is here to prevent.
             */
            $lockedVendor = Vendor::withTrashed()->whereKey($vendor->getKey())->lockForUpdate()->first();

            /*
             * The records this payout may settle — locked as well as read, so they
             * cannot move between the sum below and the allocation after it.
             */
            $earnings = VendorEarning::where('vendor_id', $vendor->id)
                ->where('status', VendorEarning::STATUS_APPROVED)
                ->whereNull('payout_id')
                ->oldest()
                ->lockForUpdate()
                ->get();

            /*
             * Re-read the balance under that lock, in pesewas. Anything read before
             * this point is a stale snapshot and must not be trusted for the
             * decision, and money is compared as integers so no float rounding can
             * let through a payout the records do not cover.
             */
            $targetPesewas = (int) round($amount * 100);
            $availablePesewas = (int) round(((float) $earnings->sum('amount')) * 100);

            if ($targetPesewas > $availablePesewas) {
                return ['success' => false, 'message' => 'Payout amount exceeds available balance of GHS ' . number_format($availablePesewas / 100, 2)];
            }

            /*
             * Choose the records this payout settles — and refuse the amount when no
             * set of whole records adds up to it.
             *
             * The allocation used to take records until the running total passed the
             * payout and then stop, so a GHS 50 payout against records of 30 and 40
             * settled BOTH: GHS 70 of commission consumed for GHS 50 of money, and
             * the vendor quietly short by 20. Taking only what fits instead would
             * leave the vendor apparently owed money they had already been paid.
             *
             * Commission is recorded per parcel and there is nowhere to record a
             * half-settled one — `vendor_earnings.shipment_item_id` is unique, so the
             * remainder cannot be split into a row of its own — which leaves refusing
             * the amount as the only option that neither short-changes the vendor nor
             * overstates what is still owed to them.
             *
             * Computed before the payout row is written, so a refused amount leaves
             * no payout behind.
             */
            $settled = collect();
            $settledPesewas = 0;
            $firstUnsettledPesewas = null;

            foreach ($earnings as $earning) {
                $earningPesewas = (int) round(((float) $earning->amount) * 100);

                if ($settledPesewas + $earningPesewas > $targetPesewas) {
                    $firstUnsettledPesewas = $earningPesewas;
                    break;
                }

                $settled->push($earning);
                $settledPesewas += $earningPesewas;
            }

            if ($settledPesewas !== $targetPesewas) {
                return [
                    'success' => false,
                    'message' => $this->settlementMismatchMessage($targetPesewas, $settledPesewas, $firstUnsettledPesewas, $availablePesewas),
                ];
            }

            $confirmImmediately = (bool) ($data['confirm_immediately'] ?? false);
            $now = now();

            $payout = VendorPayout::create([
                'vendor_id' => $vendor->id,
                'amount' => $amount,
                'status' => $confirmImmediately ? VendorPayout::STATUS_CONFIRMED : VendorPayout::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_phone' => $paymentPhone,
                'notes' => $data['notes'] ?? null,
                'processed_by_admin_id' => $adminId,
                'sent_at' => $confirmImmediately ? $now : null,
                'confirmed_at' => $confirmImmediately ? $now : null,
            ]);

            // Already chosen above, and already proven to sum to exactly this
            // payout, so there is no running total left to overshoot with.
            foreach ($settled as $earning) {
                $earning->update(['payout_id' => $payout->id, 'status' => VendorEarning::STATUS_PAID]);
            }

            $message = $confirmImmediately ? 'Vendor paid successfully.' : 'Payout of GHS ' . number_format($amount, 2) . ' created.';

            return ['success' => true, 'message' => $message, 'data' => ['payout' => $payout]];
        });
    }

    /**
     * Why an amount could not be settled, and what would settle instead.
     *
     * Names the two nearest amounts that do add up, rather than only refusing, so
     * whoever is paying is not left working out the arithmetic from the ledger.
     * All values arrive in pesewas.
     */
    private function settlementMismatchMessage(
        int $targetPesewas,
        int $settledPesewas,
        ?int $firstUnsettledPesewas,
        int $availablePesewas
    ): string {
        $money = fn (int $pesewas) => 'GHS ' . number_format($pesewas / 100, 2);

        $options = [];

        if ($settledPesewas > 0) {
            $options[] = $money($settledPesewas);
        }

        if ($firstUnsettledPesewas !== null && $settledPesewas + $firstUnsettledPesewas <= $availablePesewas) {
            $options[] = $money($settledPesewas + $firstUnsettledPesewas);
        }

        return $money($targetPesewas) . ' cannot be settled exactly: commission is recorded per parcel and paid in '
            . 'whole records, and no set of them adds up to that amount.'
            . ($options ? ' ' . (count($options) > 1 ? 'Amounts that would settle: ' : 'The nearest amount that would settle is ') . implode(' or ', $options) . '.' : '')
            . ' Outstanding balance is ' . $money($availablePesewas) . '.';
    }

    public function markPayoutSent(VendorPayout $payout, string $reference, int $adminId): array
    {
        if ($payout->status !== VendorPayout::STATUS_PENDING) {
            return ['success' => false, 'message' => 'Payout is not in pending status.'];
        }

        $payout->update([
            'status' => VendorPayout::STATUS_SENT,
            'payment_reference' => $reference,
            'processed_by_admin_id' => $adminId,
            'sent_at' => now(),
        ]);

        return ['success' => true, 'message' => 'Payout marked as sent.'];
    }

    public function confirmPayout(VendorPayout $payout): array
    {
        if ($payout->status !== VendorPayout::STATUS_SENT) {
            return ['success' => false, 'message' => 'Payout must be sent before confirming.'];
        }

        $payout->update([
            'status' => VendorPayout::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return ['success' => true, 'message' => 'Payout confirmed.'];
    }
}
