<?php

namespace App\Listeners;

use App\Events\WalkinShipmentReceived;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendWalkinSmsNotification
{
    /**
     * How many characters of the delivery location we will put in an SMS.
     *
     * A full address ("Ablekuma North Municipal, Greater Accra Region, ...") can
     * run long and would push the message into two segments. The recipient only
     * needs enough to recognise the destination, so the label is trimmed here.
     */
    private const MAX_LOCATION_LENGTH = 60;

    /**
     * Handle the event when a walk-in shipment is received.
     */
    public function handle(WalkinShipmentReceived $event): void
    {
        $shipment = $event->shipment;
        $warehouseName = $event->warehouse->name;

        // The location label reads the region and district names, so load them
        // with the items rather than letting each item fetch its own in the loop.
        $shipment->loadMissing(['items.deliveryRegion', 'items.deliveryDistrict']);

        // Loop through each package inside the shipment
        foreach ($shipment->items as $item) {
            $phone = $item->delivery_recipient_phone;
            $name = $item->delivery_recipient_name ?: 'Customer';
            $trackingCode = $item->tracking_code;

            if (empty($phone)) {
                continue;
            }

            // Standardize Ghana phone number format to international format (233XXXXXXXXX)
            $formattedPhone = $this->formatGhanaPhone($phone);

            if (! $formattedPhone) {
                continue;
            }

            /*
             * The old message named only where the parcel was *received* (the
             * warehouse) and never where the customer will receive it. The
             * opening sentence is kept so the customer still recognises it, and
             * the delivery location is appended from the parcel's own fields.
             */
            $location = Str::limit($item->deliveryLocationLabel(), self::MAX_LOCATION_LENGTH, '...');

            $message = "Hello {$name}, your package ({$item->description}) tracking number is {$trackingCode}. Received at {$warehouseName}. Deliver to: {$location}.";

            try {
                $response = Http::withHeaders([
                    'api-key' => config('services.arkesel.api_key'),
                    'Content-Type' => 'application/json',
                ])->post('https://sms.arkesel.com/api/v2/sms/send', [
                    'sender' => config('services.arkesel.sender_id', 'Parcelman'),
                    'message' => $message,
                    'recipients' => [$formattedPhone],
                ]);

                if ($response->successful()) {
                    Log::info("SMS sent via Arkesel to {$formattedPhone} for tracking {$trackingCode}");
                } else {
                    Log::error("Arkesel SMS failed ({$response->status()}): " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Arkesel SMS Exception: " . $e->getMessage());
            }
        }
    }

    /**
     * Helper to format Ghana local numbers (024xxxxxxx) to 23324xxxxxxx.
     */
    private function formatGhanaPhone(string $phone): ?string
    {
        $cleaned = preg_replace('/\D/', '', $phone);

        if (str_starts_with($cleaned, '0') && strlen($cleaned) === 10) {
            return '233' . substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '233') && strlen($cleaned) === 12) {
            return $cleaned;
        }

        return null;
    }
}