<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorProfileService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get vendor profile.
     */
    public function getProfile(Vendor $vendor): array
    {
        return [
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'user' => $this->formatVendor($vendor),
            ],
        ];
    }

    /**
     * Update vendor profile.
     */
    public function updateProfile(Vendor $vendor, array $data, Request $request): array
    {
        $vendor->name = $data['name'];
        $vendor->business_name = $data['business_name'] ?? null;
        $vendor->email = $data['email'] ?? null;
        $vendor->save();

        $this->activityLogService->log(
            $vendor->id,
            'profile_updated',
            'Profile updated successfully',
            $request,
            ['fields' => array_keys($data)]
        );

        return [
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => $this->formatVendor($vendor),
            ],
        ];
    }

    /**
     * Format vendor for API response.
     */
    protected function formatVendor(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'business_name' => $vendor->business_name,
            'phone' => $vendor->phone,
            'email' => $vendor->email,
        ];
    }
}
