<?php

namespace App\Observers;

use App\Models\DeliveryRunItem;
use App\Models\RiderTeamHandover;
use App\Models\RiderTeamHandoverItem;
use App\Services\RiderTeamHandoverService;
use Illuminate\Support\Facades\Schema;

class DeliveryRunItemObserver
{
    public function updated(DeliveryRunItem $item): void
    {
        if (! $item->wasChanged('status') || ! in_array($item->status, [DeliveryRunItem::STATUS_DELIVERED, DeliveryRunItem::STATUS_FAILED], true)) {
            return;
        }

        if (! Schema::hasTable('rider_team_handover_items')) {
            return;
        }

        $item->loadMissing('run');
        $driverId = $item->run?->assigned_driver_id;
        if (! $driverId || ! $item->shipment_item_id) {
            return;
        }

        $handoverItems = RiderTeamHandoverItem::query()
            ->where('allocated_to_driver_id', $driverId)
            ->whereHas('label.receiptItem', fn ($query) => $query->where('shipment_item_id', $item->shipment_item_id))
            ->whereIn('status', [
                RiderTeamHandoverItem::STATUS_MEMBER_CLAIMED,
                RiderTeamHandoverItem::STATUS_IN_DELIVERY,
            ])
            ->get();

        if ($handoverItems->isEmpty()) {
            return;
        }

        $newStatus = $item->status === DeliveryRunItem::STATUS_DELIVERED
            ? RiderTeamHandoverItem::STATUS_DELIVERED
            : RiderTeamHandoverItem::STATUS_FAILED;

        foreach ($handoverItems as $handoverItem) {
            $handoverItem->update([
                'status' => $newStatus,
                'delivered_at' => $newStatus === RiderTeamHandoverItem::STATUS_DELIVERED ? now() : $handoverItem->delivered_at,
            ]);
        }

        $handoverItems->pluck('rider_team_handover_id')
            ->unique()
            ->each(function ($handoverId) {
                $handover = RiderTeamHandover::find($handoverId);
                if ($handover) {
                    app(RiderTeamHandoverService::class)->refreshHandoverCounts($handover);
                }
            });
    }
}
