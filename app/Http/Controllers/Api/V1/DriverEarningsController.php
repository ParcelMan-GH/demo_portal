<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ResolvesActingDriver;
use App\Models\Driver;
use App\Services\DriverEarningsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The driver's earnings and their withdrawal requests.
 *
 * Neither route existed: the app called `/driver/earnings` and
 * `/driver/payouts/request`, both 404, and the screen filled the gap with
 * hardcoded demo money. It now has real figures, and a failed request says so
 * instead of showing a fabricated balance.
 */
class DriverEarningsController extends Controller
{
    use ResolvesActingDriver;

    public function __construct(private DriverEarningsService $earnings) {}

    /**
     * GET /api/v1/driver/earnings
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $this->actingDriver($request);

        return response()->json([
            'success' => true,
            'message' => 'Earnings retrieved successfully.',
            'data' => $this->earnings->summary($driver),
        ]);
    }

    /**
     * POST /api/v1/driver/payouts/request
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $driver = $this->actingDriver($request);

        $request->validate([
            'amount' => ['required'],
            'phone' => ['nullable', 'string', 'max:30'],
            'method' => ['nullable', 'string', 'max:30'],
        ]);

        $amount = $this->normaliseAmount($request->input('amount'));

        if ($amount === null) {
            return response()->json([
                'success' => false,
                'message' => 'That amount could not be read. Please enter a number.',
                'errors' => ['amount' => ['That amount could not be read. Please enter a number.']],
            ], 422);
        }

        $result = $this->earnings->requestPayout(
            $driver,
            $amount,
            $request->input('phone'),
            $request->input('method'),
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => ['amount' => [$result['message']]],
                'data' => $result['data'] ?? null,
            ], $result['status'] ?? 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    }

    /**
     * Read an amount out of whatever the client sent.
     *
     * The earnings screen displays a balance as a formatted string ("GHS 450.00")
     * and the first version of the payout button posted that string straight
     * back. Accepting the money words-and-all is more useful than a 422 that the
     * driver cannot act on, so currency symbols and separators are stripped
     * before the value is checked.
     */
    private function normaliseAmount(mixed $raw): ?float
    {
        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        if (! is_string($raw)) {
            return null;
        }

        // Keep digits, the decimal point and a leading minus; drop "GHS", commas
        // and spaces.
        $cleaned = preg_replace('/[^0-9.\-]/', '', $raw) ?? '';

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
