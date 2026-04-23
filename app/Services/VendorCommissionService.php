<?php

namespace App\Services;

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

        return [
            'total_earned' => (float) $totalEarned,
            'available_balance' => (float) $approvedBalance,
            'total_paid' => (float) $totalPaid,
            'pending_payout' => (float) $pendingPayout,
            'min_payout' => $this->getMinPayout(),
            'can_request_payout' => $approvedBalance >= $this->getMinPayout(),
            'currency' => 'GHS',
        ];
    }

    public function createPayout(Vendor $vendor, float $amount, int $adminId, array $data = []): array
    {
        $summary = $this->getVendorSummary($vendor);

        if ($amount > $summary['available_balance']) {
            return ['success' => false, 'message' => 'Payout amount exceeds available balance of GHS ' . number_format($summary['available_balance'], 2)];
        }

        if ($amount < $this->getMinPayout()) {
            return ['success' => false, 'message' => 'Minimum payout amount is GHS ' . number_format($this->getMinPayout(), 2)];
        }

        return DB::transaction(function () use ($vendor, $amount, $adminId, $data) {
            $payout = VendorPayout::create([
                'vendor_id' => $vendor->id,
                'amount' => $amount,
                'status' => VendorPayout::STATUS_PENDING,
                'payment_method' => $data['payment_method'] ?? 'momo',
                'payment_phone' => $data['payment_phone'] ?? $vendor->phone,
                'notes' => $data['notes'] ?? null,
                'processed_by_admin_id' => $adminId,
            ]);

            $remaining = $amount;
            $earnings = VendorEarning::where('vendor_id', $vendor->id)
                ->where('status', VendorEarning::STATUS_APPROVED)
                ->whereNull('payout_id')
                ->oldest()
                ->get();

            foreach ($earnings as $earning) {
                if ($remaining <= 0) break;
                $earning->update(['payout_id' => $payout->id, 'status' => VendorEarning::STATUS_PAID]);
                $remaining -= $earning->amount;
            }

            return ['success' => true, 'message' => 'Payout of GHS ' . number_format($amount, 2) . ' created.', 'data' => ['payout' => $payout]];
        });
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
