<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesActingDriver;
use App\Http\Controllers\Controller;
use App\Models\BusHandoffConfirmation;
use App\Models\DeliveryRunItem;
use App\Services\BusHandoffConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverBusHandoffController extends Controller
{
    use ResolvesActingDriver;

    public function __construct(private BusHandoffConfirmationService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->listForDriver($this->actingDriver($request), $request));
    }

    public function show(Request $request, DeliveryRunItem $deliveryRunItem): JsonResponse
    {
        $result = $this->service->showForDriver($this->actingDriver($request), $deliveryRunItem);

        return response()->json($result, $result['status'] ?? 200);
    }

    public function reasons(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['reasons' => $this->service->activeReasons()],
        ]);
    }

    public function sendConfirmation(Request $request, DeliveryRunItem $deliveryRunItem): JsonResponse
    {
        $validated = $request->validate([
            'target_type' => ['required', 'in:' . implode(',', [
                BusHandoffConfirmation::TARGET_RECIPIENT,
                BusHandoffConfirmation::TARGET_VENDOR,
            ])],
        ]);

        $result = $this->service->sendConfirmation(
            $this->actingDriver($request),
            $deliveryRunItem,
            $validated['target_type'],
        );

        return response()->json($result, $result['status'] ?? 200);
    }

    public function confirmCode(Request $request, DeliveryRunItem $deliveryRunItem): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_code' => ['required', 'string', 'max:12'],
        ]);

        $result = $this->service->confirmWithCode(
            $this->actingDriver($request),
            $deliveryRunItem,
            $validated['confirmation_code'],
        );

        return response()->json($result, $result['status'] ?? 200);
    }

    public function reportIssue(Request $request, DeliveryRunItem $deliveryRunItem): JsonResponse
    {
        $validated = $request->validate([
            'reason_id' => ['required', 'integer', 'exists:delivery_failure_reasons,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->service->reportIssue(
            $this->actingDriver($request),
            $deliveryRunItem,
            (int) $validated['reason_id'],
            $validated['notes'] ?? null,
        );

        return response()->json($result, $result['status'] ?? 200);
    }
}
