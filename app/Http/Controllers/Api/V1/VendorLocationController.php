<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;

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
}
