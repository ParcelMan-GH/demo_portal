<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PickupVehicleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorPickupVehicleTypeController extends Controller
{
    /**
     * Pickup vehicle options for the vendor shipment screen.
     *
     * Locked types are returned too, flagged rather than hidden, so the app can
     * keep showing them as disabled with a reason. An admin unlocking a type in
     * the Vehicle Fleet panel is therefore visible immediately, without a new
     * app release.
     *
     * Pass ?active_only=1 for the old "selectable options only" behaviour.
     */
    public function index(Request $request): JsonResponse
    {
        $activeOnly = $request->boolean('active_only');

        $types = PickupVehicleType::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PickupVehicleType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'icon' => $type->icon,
                'capacity_hint' => $type->capacity_hint,
                'sort_order' => $type->sort_order,
                'is_active' => (bool) $type->is_active,
                // Alias kept so older clients checking `is_available` also work.
                'is_available' => (bool) $type->is_active,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'vehicle_types' => $types,
            ],
        ]);
    }
}
