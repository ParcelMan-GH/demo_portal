<?php

namespace App\Services;

use App\Models\BusHandoffConfirmation;
use App\Models\DeliveryDelayEvent;
use App\Models\DeliveryDelayReason;
use App\Models\DeliveryRunItem;
use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Vendor;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeliveryDelayService
{
    public function __construct(
        private SmsService $smsService,
        private PushNotificationService $pushNotificationService,
    ) {
    }

    public function activeReasons()
    {
        return DeliveryDelayReason::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn (DeliveryDelayReason $reason) => $this->reasonPayload($reason))
            ->values();
    }

    public function reasonPayload(DeliveryDelayReason $reason): array
    {
        return [
            'id' => $reason->id,
            'label' => $reason->label,
            'slug' => $reason->slug,
            'sort_order' => $reason->sort_order,
            'is_active' => (bool) $reason->is_active,
        ];
    }

    public function setEta(
        DeliveryRunItem $item,
        ?CarbonInterface $expectedDeliveryAt,
        ?DeliveryDelayReason $reason,
        ?string $notes,
        string $source,
        ?Driver $driver = null,
        ?User $user = null,
        bool $createEvent = true,
    ): DeliveryRunItem {
        return DB::transaction(function () use ($item, $expectedDeliveryAt, $reason, $notes, $source, $driver, $user, $createEvent) {
            $item->loadMissing(['run', 'stop', 'shipmentItem']);
            $oldEta = $item->expected_delivery_at;

            $item->forceFill([
                'expected_delivery_at' => $expectedDeliveryAt,
                'expected_delivery_set_at' => now(),
                'expected_delivery_set_by_driver_id' => $driver?->id,
                'expected_delivery_set_by_user_id' => $user?->id,
            ])->save();

            if ($createEvent) {
                DeliveryDelayEvent::query()->create([
                    'delivery_run_item_id' => $item->id,
                    'delivery_run_stop_id' => $item->delivery_run_stop_id,
                    'delivery_run_id' => $item->delivery_run_id,
                    'shipment_item_id' => $item->shipment_item_id,
                    'delivery_delay_reason_id' => $reason?->id,
                    'reason_label' => $reason?->label,
                    'old_expected_delivery_at' => $oldEta,
                    'new_expected_delivery_at' => $expectedDeliveryAt,
                    'source' => $source,
                    'actor_driver_id' => $driver?->id,
                    'actor_user_id' => $user?->id,
                    'notes' => $notes,
                ]);
            }

            return $item->fresh(['run.assignedDriver', 'stop', 'shipmentItem', 'delayEvents.actorDriver', 'delayEvents.actorUser', 'delayEvents.reason']);
        });
    }

    public function sendAdminNotice(
        DeliveryRunItem $item,
        User $admin,
        DeliveryDelayReason $reason,
        ?CarbonInterface $revisedEta,
        bool $notifyRecipient,
        bool $notifyVendor,
        bool $notifyVendorSms,
        ?string $message = null,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($item, $admin, $reason, $revisedEta, $notifyRecipient, $notifyVendor, $notifyVendorSms, $message, $notes) {
            $item->loadMissing(['run.assignedDriver', 'stop', 'shipmentItem.shipment.vendor']);
            $oldEta = $item->expected_delivery_at;

            if ($revisedEta) {
                $item->forceFill([
                    'expected_delivery_at' => $revisedEta,
                    'expected_delivery_set_at' => now(),
                    'expected_delivery_set_by_driver_id' => null,
                    'expected_delivery_set_by_user_id' => $admin->id,
                ])->save();
            }

            $customMessage = trim((string) $message);
            $recipientMessage = $customMessage !== '' ? $customMessage : $this->recipientMessage($item, $reason, $revisedEta);
            $vendorMessage = $customMessage !== '' ? $customMessage : $this->vendorMessage($item, $reason, $revisedEta);
            $recipientSent = false;
            $vendorPushSent = false;
            $vendorNotified = false;
            $vendorSmsSent = false;

            if ($notifyRecipient && filled($this->recipientPhone($item))) {
                $recipientSent = $this->smsService->send((string) $this->recipientPhone($item), $recipientMessage);
            }

            $vendor = $item->shipmentItem?->shipment?->vendor;
            if ($notifyVendor && $vendor) {
                $vendorPushSent = $this->sendVendorNotification($vendor, $vendorMessage, $item);
                $vendorNotified = true;
            }

            if ($notifyVendorSms && $vendor?->phone) {
                $vendorSmsSent = $this->smsService->send($vendor->phone, $vendorMessage);
            }

            $event = DeliveryDelayEvent::query()->create([
                'delivery_run_item_id' => $item->id,
                'delivery_run_stop_id' => $item->delivery_run_stop_id,
                'delivery_run_id' => $item->delivery_run_id,
                'shipment_item_id' => $item->shipment_item_id,
                'delivery_delay_reason_id' => $reason->id,
                'reason_label' => $reason->label,
                'old_expected_delivery_at' => $oldEta,
                'new_expected_delivery_at' => $revisedEta ?: $oldEta,
                'source' => DeliveryDelayEvent::SOURCE_ADMIN_DELAY_NOTICE,
                'actor_user_id' => $admin->id,
                'recipient_sms_sent' => $recipientSent,
                'vendor_notification_sent' => $vendorPushSent || $vendorNotified,
                'vendor_sms_sent' => $vendorSmsSent,
                'message_preview' => $recipientMessage,
                'notes' => $notes,
            ]);

            return [
                'event' => $event,
                'recipient_sms_sent' => $recipientSent,
                'vendor_notification_sent' => $vendorPushSent || $vendorNotified,
                'vendor_sms_sent' => $vendorSmsSent,
            ];
        });
    }

    public function snapshot(?DeliveryRunItem $item): array
    {
        if (!$item) {
            return $this->emptySnapshot();
        }

        $relations = ['run.assignedDriver', 'stop', 'expectedDeliverySetByDriver', 'expectedDeliverySetByUser'];
        if (Schema::hasTable('bus_handoff_confirmations')) {
            $relations[] = 'busHandoffConfirmation';
        }
        if (Schema::hasTable('delivery_delay_events')) {
            $relations[] = 'delayEvents.actorDriver';
            $relations[] = 'delayEvents.actorUser';
            $relations[] = 'delayEvents.reason';
        }
        $item->loadMissing($relations);
        $lastNotice = $item->relationLoaded('delayEvents') ? $item->delayEvents
            ->where('source', DeliveryDelayEvent::SOURCE_ADMIN_DELAY_NOTICE)
            ->sortByDesc('created_at')
            ->first() : null;
        $isFinal = $this->isFinal($item);
        $eta = $item->expected_delivery_at;
        $now = now();
        $graceMinutes = max(0, (int) PlatformSetting::getValue('delivery_eta_grace_minutes', 30));
        $thresholdHours = max(1, (int) PlatformSetting::getValue('delivery_no_eta_threshold_hours', 4));
        $status = 'no_eta';
        $label = 'No ETA';
        $tone = 'slate';
        $isOverdue = false;

        if ($isFinal) {
            $status = 'completed';
            $label = 'Completed';
            $tone = 'emerald';
        } elseif ($eta) {
            $deadline = $eta->copy()->addMinutes($graceMinutes);
            $isOverdue = $now->greaterThan($deadline);
            $status = $isOverdue ? 'overdue' : 'eta_set';
            $label = $isOverdue ? 'ETA overdue' : 'ETA set';
            $tone = $isOverdue ? 'rose' : 'blue';
        } elseif ($item->run?->dispatched_at && $now->greaterThan($item->run->dispatched_at->copy()->addHours($thresholdHours))) {
            $status = 'no_eta_overdue';
            $label = 'No ETA past threshold';
            $tone = 'amber';
            $isOverdue = true;
        }

        if ($lastNotice && !$isFinal) {
            $label = $status === 'overdue' || $status === 'no_eta_overdue' ? $label . ' · notice sent' : 'Delay notice sent';
        }

        return [
            'status' => $status,
            'label' => $label,
            'tone' => $tone,
            'is_overdue' => $isOverdue,
            'expected_delivery_at' => $this->formatDateTime($eta),
            'expected_delivery_at_iso' => $eta?->toIso8601String(),
            'expected_delivery_set_at' => $this->formatDateTime($item->expected_delivery_set_at),
            'set_by' => $item->expectedDeliverySetByDriver?->name ?? $item->expectedDeliverySetByUser?->name,
            'last_notice_at' => $this->formatDateTime($lastNotice?->created_at),
            'last_notice_by' => $lastNotice?->actorUser?->name,
            'last_reason' => $lastNotice?->reason_label ?: $lastNotice?->reason?->label,
            'can_update' => !$isFinal,
            'can_notify' => !$isFinal && (bool) $item->id,
            'grace_minutes' => $graceMinutes,
            'no_eta_threshold_hours' => $thresholdHours,
        ];
    }

    public function history(DeliveryRunItem $item): array
    {
        if (!Schema::hasTable('delivery_delay_events')) {
            return [];
        }

        $item->loadMissing(['delayEvents.actorDriver', 'delayEvents.actorUser', 'delayEvents.reason']);

        return $item->delayEvents
            ->sortByDesc('created_at')
            ->map(fn (DeliveryDelayEvent $event) => [
                'id' => $event->id,
                'source' => $event->source,
                'source_label' => ucfirst(str_replace('_', ' ', $event->source)),
                'reason' => $event->reason_label ?: $event->reason?->label,
                'old_eta' => $this->formatDateTime($event->old_expected_delivery_at),
                'new_eta' => $this->formatDateTime($event->new_expected_delivery_at),
                'actor' => $event->actorDriver?->name ?? $event->actorUser?->name,
                'recipient_sms_sent' => (bool) $event->recipient_sms_sent,
                'vendor_notification_sent' => (bool) $event->vendor_notification_sent,
                'vendor_sms_sent' => (bool) $event->vendor_sms_sent,
                'notes' => $event->notes,
                'created_at' => $this->formatDateTime($event->created_at),
            ])
            ->values()
            ->all();
    }

    public function canDriverUpdate(DeliveryRunItem $item, Driver $driver): bool
    {
        $relations = ['run'];
        if (Schema::hasTable('bus_handoff_confirmations')) {
            $relations[] = 'busHandoffConfirmation';
        }
        $item->loadMissing($relations);

        return (int) $item->run?->assigned_driver_id === (int) $driver->id && !$this->isFinal($item);
    }

    private function isFinal(DeliveryRunItem $item): bool
    {
        if (in_array($item->status, [DeliveryRunItem::STATUS_DELIVERED, DeliveryRunItem::STATUS_FAILED], true)) {
            return true;
        }

        $confirmation = $item->relationLoaded('busHandoffConfirmation') ? $item->busHandoffConfirmation : null;
        return $confirmation && in_array($confirmation->status, [
            BusHandoffConfirmation::STATUS_CONFIRMED,
            BusHandoffConfirmation::STATUS_ADMIN_CONFIRMED,
            BusHandoffConfirmation::STATUS_FAILED,
        ], true);
    }

    private function recipientMessage(DeliveryRunItem $item, DeliveryDelayReason $reason, ?CarbonInterface $eta): string
    {
        $tracking = $item->shipmentItem?->tracking_code ?: $item->shipmentItem?->shipment?->shipment_number ?: 'your package';
        $etaText = $eta ? ' New expected delivery: ' . $eta->format('M j, Y g:i A') . '.' : '';

        return "Delivery for package {$tracking} is delayed. Reason: {$reason->label}.{$etaText} We will update you if anything changes.";
    }

    private function vendorMessage(DeliveryRunItem $item, DeliveryDelayReason $reason, ?CarbonInterface $eta): string
    {
        $tracking = $item->shipmentItem?->tracking_code ?: $item->shipmentItem?->shipment?->shipment_number ?: 'a package';
        $recipient = $item->shipmentItem?->delivery_recipient_name ?: $item->shipmentItem?->shipment?->delivery_recipient_name;
        $etaText = $eta ? ' New expected delivery: ' . $eta->format('M j, Y g:i A') . '.' : '';
        $recipientText = $recipient ? " to {$recipient}" : '';

        return "Delivery for package {$tracking}{$recipientText} is delayed. Reason: {$reason->label}.{$etaText}";
    }

    private function recipientPhone(DeliveryRunItem $item): ?string
    {
        return $item->shipmentItem?->delivery_recipient_phone ?: $item->shipmentItem?->shipment?->delivery_recipient_phone;
    }

    private function sendVendorNotification(Vendor $vendor, string $message, DeliveryRunItem $item): bool
    {
        $data = [
            'delivery_run_item_id' => (string) $item->id,
            'shipment_item_id' => (string) $item->shipment_item_id,
            'type' => 'delivery_delay',
        ];

        $sent = $this->pushNotificationService->sendToVendor($vendor, 'Delivery delayed', $message, $data, 'delivery_delay');

        if (!$vendor->fcm_token) {
            NotificationLog::query()->create([
                'notifiable_type' => Vendor::class,
                'notifiable_id' => $vendor->id,
                'type' => 'delivery_delay',
                'channel' => 'in_app',
                'title' => 'Delivery delayed',
                'body' => $message,
                'data' => $data,
                'status' => 'sent',
            ]);
        }

        return $sent;
    }

    private function emptySnapshot(): array
    {
        return [
            'status' => 'none',
            'label' => '-',
            'tone' => 'slate',
            'is_overdue' => false,
            'expected_delivery_at' => null,
            'expected_delivery_at_iso' => null,
            'expected_delivery_set_at' => null,
            'set_by' => null,
            'last_notice_at' => null,
            'last_notice_by' => null,
            'last_reason' => null,
            'can_update' => false,
            'can_notify' => false,
            'grace_minutes' => max(0, (int) PlatformSetting::getValue('delivery_eta_grace_minutes', 30)),
            'no_eta_threshold_hours' => max(1, (int) PlatformSetting::getValue('delivery_no_eta_threshold_hours', 4)),
        ];
    }

    private function formatDateTime(?CarbonInterface $date): ?string
    {
        return $date ? $date->format('M j, Y g:i A') : null;
    }
}
