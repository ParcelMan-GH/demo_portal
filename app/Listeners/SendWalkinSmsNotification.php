<?php

namespace App\Listeners;

use App\Events\WalkinShipmentReceived;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWalkinSmsNotification
{
    /**
     * Handle the event when a walk-in shipment is received.
     */
    public function handle(WalkinShipmentReceived $event): void
    {
        $shipment = $event->shipment;
        $warehouseName = $event->warehouse->name;

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

            $message = "Hello {$name}, your package ({$item->description}) tracking number is {$trackingCode}. Received at {$warehouseName}.";

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