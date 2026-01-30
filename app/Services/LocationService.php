<?php

namespace App\Services;

use App\Models\District;
use App\Models\Region;

class LocationService
{
    /**
     * Get all active regions with their districts.
     */
    public function getRegions(): array
    {
        $regions = Region::active()
            ->with(['districts' => function ($query) {
                $query->active()->orderBy('name')->select('id', 'region_id', 'name', 'code');
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return [
            'success' => true,
            'message' => 'Regions retrieved successfully.',
            'data' => [
                'regions' => $regions,
            ],
        ];
    }

    /**
     * Get districts for a region.
     */
    public function getDistricts(Region $region): array
    {
        $districts = $region->districts()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return [
            'success' => true,
            'message' => 'Districts retrieved successfully.',
            'data' => [
                'region' => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'code' => $region->code,
                ],
                'districts' => $districts,
            ],
        ];
    }
}
