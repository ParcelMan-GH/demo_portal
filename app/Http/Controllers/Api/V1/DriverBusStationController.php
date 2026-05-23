<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use Illuminate\Http\JsonResponse;

class DriverBusStationController extends Controller
{
    public function index(): JsonResponse
    {
        $stations = BusStation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (BusStation $station) => [
                'id' => $station->id,
                'name' => $station->name,
                'location_hint' => $station->location_hint,
                'sort_order' => $station->sort_order,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'bus_stations' => $stations,
            ],
        ]);
    }
}
