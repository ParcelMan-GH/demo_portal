<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupVehicleType;
use App\Services\BackOfficeAccess;
use Illuminate\View\View;

/**
 * Dedicated Vehicle Fleet panel.
 *
 * Availability lives in `pickup_vehicle_types.is_active`, which is the single
 * switch the vendor app reads: locking a type here disables it for vendors,
 * unlocking it makes it selectable again.
 */
class FleetVehicleController extends Controller
{
    public function index(): View
    {
        $user = auth('admin')->user();

        abort_unless(
            $user && app(BackOfficeAccess::class)->canUsePermission($user, 'settings.view'),
            403
        );

        $vehicleTypes = PickupVehicleType::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PickupVehicleType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'capacity_hint' => $type->capacity_hint,
                'sort_order' => $type->sort_order,
                'is_active' => (bool) $type->is_active,
            ])
            ->values()
            ->all();

        $available = count(array_filter($vehicleTypes, fn (array $type) => $type['is_active']));

        return view('admin.fleet.vehicles', [
            'vehicleTypes' => $vehicleTypes,
            'summary' => [
                'total' => count($vehicleTypes),
                'available' => $available,
                'locked' => count($vehicleTypes) - $available,
            ],
        ]);
    }
}
