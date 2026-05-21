<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PickupVehicleType;
use Illuminate\Http\JsonResponse;

class VendorPickupVehicleTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = PickupVehicleType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PickupVehicleType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'capacity_hint' => $type->capacity_hint,
                'sort_order' => $type->sort_order,
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
