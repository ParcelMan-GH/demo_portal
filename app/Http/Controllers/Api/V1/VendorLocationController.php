<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Region;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorLocationController extends Controller
{
    public function __construct(
        private LocationService $locationService
    ) {}

    /**
     * Get all regions.
     */
    public function regions(): JsonResponse
    {
        $result = $this->locationService->getRegions();

        return response()->json($result);
    }

    /**
     * Get districts for a region.
     */
    public function districts(Region $region): JsonResponse
    {
        $result = $this->locationService->getDistricts($region);

        return response()->json($result);
    }

    /**
     * Get locations.
     * - No `q` param: returns all active locations nested (regions → districts → towns) for local caching.
     * - With `q` param (min 2 chars): searches locations by name (existing typeahead response format).
     */
    public function searchLocations(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        // No search query — return all active locations in same format as search
        if ($q === '') {
            $locations = Location::active()
                ->with(['district:id,name,code', 'region:id,name,code'])
                ->orderBy('name')
                ->get();

            $formatted = $locations->map(fn ($l) => [
                'id'       => $l->id,
                'name'     => $l->name,
                'type'     => $l->type,
                'district' => ['id' => $l->district->id, 'name' => $l->district->name],
                'region'   => ['id' => $l->region->id, 'name' => $l->region->name],
                'display'  => "{$l->name}, {$l->district->name}, {$l->region->name}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All locations retrieved.',
                'data'    => ['locations' => $formatted],
            ]);
        }

        // Search query too short
        if (strlen($q) < 2) {
            return response()->json([
                'success' => true,
                'message' => 'Search results retrieved.',
                'data'    => ['locations' => []],
            ]);
        }

        // Existing search — response format unchanged
        $locations = Location::active()
            ->with(['district:id,name,code', 'region:id,name,code'])
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', $q . '%')
                      ->orWhere('name', 'like', '% ' . $q . '%');
            })
            ->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", [$q . '%'])
            ->orderBy('name')
            ->limit(12)
            ->get();

        $formatted = $locations->map(fn ($l) => [
            'id'       => $l->id,
            'name'     => $l->name,
            'type'     => $l->type,
            'district' => ['id' => $l->district->id, 'name' => $l->district->name],
            'region'   => ['id' => $l->region->id, 'name' => $l->region->name],
            'display'  => "{$l->name}, {$l->district->name}, {$l->region->name}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Search results retrieved.',
            'data'    => ['locations' => $formatted],
        ]);
    }
}
