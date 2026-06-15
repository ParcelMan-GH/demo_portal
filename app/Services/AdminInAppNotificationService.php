<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Collection;

class AdminInAppNotificationService
{
    public function __construct(private BackOfficeAccess $backOfficeAccess) {}

    /**
     * @return Collection<int, AdminNotification>
     */
    public function notifyShipmentSubmitted(Shipment $shipment, string $title, string $body): Collection
    {
        $shipment->loadMissing('vendor');

        return $this->shipmentViewerRecipients()
            ->map(fn (User $user) => AdminNotification::create([
                'user_id' => $user->id,
                'type' => 'shipment_submitted',
                'title' => $title,
                'body' => $body,
                'url' => route('admin.orders.show', $shipment, false),
                'data' => [
                    'shipment_id' => $shipment->id,
                    'shipment_number' => $shipment->shipment_number,
                    'status' => 'submitted',
                    'vendor_id' => $shipment->vendor_id,
                    'vendor_name' => $shipment->vendor?->name,
                ],
            ]));
    }

    /**
     * @return Collection<int, User>
     */
    private function shipmentViewerRecipients(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->with(['roles.permissions', 'warehouse'])
            ->get()
            ->filter(fn (User $user) => $this->backOfficeAccess->canUsePermission($user, 'shipments.view'))
            ->values();
    }
}
