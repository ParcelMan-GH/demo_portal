<?php

namespace App\Listeners\Concerns;

use App\Enums\PickupAssignmentStatus;
use App\Helpers\PhoneHelper;
use App\Models\PickupAssignment;
use App\Models\Shipment;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Shared plumbing for the shipment-transition SMS listeners.
 *
 * This is deliberately thin: it only resolves recipients and calls the existing
 * SmsService::send(), which already writes the sms_logs row, honours the
 * sms_enabled setting and reports its own failures. It is not a second delivery
 * path.
 */
trait SendsShipmentSms
{
    /**
     * One shipment must never fan out to an unbounded list of recipients. A
     * per-item shipment can name a phone on every item, so cap the fan-out.
     */
    protected const MAX_CUSTOMER_RECIPIENTS = 5;

    /** Set by each listener's constructor. */
    protected SmsService $smsService;

    /**
     * Send one message to one phone, never letting a failure reach the caller.
     *
     * A missing or unusable number is skipped silently. A send that throws is
     * swallowed and logged: an SMS must never break a status transition or roll
     * back an assignment.
     */
    protected function sendShipmentSms(Shipment $shipment, ?string $phone, string $message): void
    {
        if (blank($phone)) {
            return;
        }

        $formatted = PhoneHelper::format($phone);

        if (! $formatted) {
            // Unusable number — skip silently, same as a missing one.
            return;
        }

        // SmsService stores recipients in the 233xxxxxxxxx shape (it strips the
        // leading +), so the idempotency lookup has to use the same string.
        $storedRecipient = ltrim($formatted, '+');

        if ($this->smsAlreadySent($storedRecipient, $message)) {
            return;
        }

        try {
            $this->smsService->send($formatted, $message);
        } catch (\Throwable $e) {
            Log::warning('Shipment SMS notification failed', [
                'shipment_id' => $shipment->id,
                'phone' => $storedRecipient,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Idempotency guard.
     *
     * sms_logs has no shipment or status column, but the rendered message is
     * unique per (shipment number, status, recipient), so recipient+message is
     * the natural key. Only 'sent'/'pending' rows count: a 'failed' row stays
     * retryable rather than locking the customer out of ever being told.
     */
    protected function smsAlreadySent(string $recipient, string $message): bool
    {
        try {
            if (! Schema::hasTable('sms_logs')) {
                return false;
            }

            return SmsLog::query()
                ->where('recipient', $recipient)
                ->where('message', $message)
                ->whereIn('status', ['sent', 'pending'])
                ->exists();
        } catch (\Throwable) {
            // If we cannot check, prefer attempting the send over silently
            // dropping it.
            return false;
        }
    }

    /** Vendor phone, or null when the shipment has no vendor or phone. */
    protected function shipmentVendorPhone(Shipment $shipment): ?string
    {
        return $shipment->vendor?->phone;
    }

    /**
     * Distinct customer numbers for a shipment.
     *
     * Shipment::recipient_phone already knows the single-destination vs
     * per-item split, but collapses several distinct numbers into the sentinel
     * "Multiple numbers". When it hands back a real number we use it; when it
     * reports several we fan out over the same per-item source the accessor
     * reads, deduplicated and capped.
     */
    protected function shipmentCustomerPhones(Shipment $shipment): array
    {
        $single = $shipment->recipient_phone;

        if (filled($single) && strtolower((string) $single) !== 'multiple numbers') {
            return [$single];
        }

        $phones = $shipment->relationLoaded('items')
            ? $shipment->items->pluck('delivery_recipient_phone')
            : $shipment->items()->whereNotNull('delivery_recipient_phone')->pluck('delivery_recipient_phone');

        return $phones
            ->filter()
            ->unique()
            ->take(self::MAX_CUSTOMER_RECIPIENTS)
            ->values()
            ->all();
    }

    /**
     * The rider to name on an out-for-delivery message: the most recently
     * created pickup assignment that has not been cancelled. After multi-slot
     * assignment a shipment may hold several, so "latest live one" is the
     * choice here.
     */
    protected function latestActivePickupAssignment(Shipment $shipment): ?PickupAssignment
    {
        return $shipment->pickupAssignments()
            ->where('status', '!=', PickupAssignmentStatus::CANCELLED)
            ->latest('id')
            ->with('driver')
            ->first();
    }
}
