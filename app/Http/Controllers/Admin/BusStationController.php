<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BusStationController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission('settings.view');
        $stations = BusStation::orderBy('name')->get();
        return view('admin.bus-stations.index', compact('stations'));
    }

    public function data(): JsonResponse
    {
        $stations = BusStation::orderBy('name')->get()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'is_active' => $s->is_active,
            'packages_count' => $s->shipmentItems()->count(),
            'created_at' => $s->created_at?->format('M d, Y'),
        ]);

        return response()->json(['success' => true, 'data' => $stations]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('settings.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:bus_stations,name'],
        ]);

        $station = BusStation::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Bus station \"{$station->name}\" created.",
            'data' => ['id' => $station->id, 'name' => $station->name, 'is_active' => true],
        ]);
    }

    public function update(Request $request, BusStation $busStation): JsonResponse
    {
        $this->authorizePermission('settings.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:bus_stations,name,' . $busStation->id],
        ]);

        $busStation->update($validated);

        return response()->json(['success' => true, 'message' => 'Bus station updated.']);
    }

    public function toggleActive(BusStation $busStation): JsonResponse
    {
        $this->authorizePermission('settings.edit');

        $busStation->update(['is_active' => !$busStation->is_active]);

        return response()->json([
            'success' => true,
            'message' => $busStation->is_active ? 'Bus station activated.' : 'Bus station deactivated.',
            'is_active' => $busStation->is_active,
        ]);
    }

    public function destroy(BusStation $busStation): JsonResponse
    {
        $this->authorizePermission('settings.edit');

        if ($busStation->shipmentItems()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete — this station has packages assigned to it.'], 422);
        }

        $busStation->delete();

        return response()->json(['success' => true, 'message' => 'Bus station deleted.']);
    }

    public function list(): JsonResponse
    {
        $stations = BusStation::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return response()->json(['data' => $stations]);
    }

    protected function authorizePermission(string $permission): void
    {
        if (!Auth::guard('admin')->user()?->hasPermission($permission)) {
            abort(403, 'Unauthorized.');
        }
    }
}
