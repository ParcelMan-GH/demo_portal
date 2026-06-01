<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use App\Models\PlatformSetting;
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
                'app_config' => $this->formatAppConfig(),
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
                'app_config' => $this->formatAppConfig(),
            ],
        ];
    }

    public function updatePayoutAccount(Vendor $vendor, array $data, Request $request): array
    {
        $oldAccount = $this->formatPayoutAccount($vendor);

        $vendor->payout_momo_network = $data['payout_momo_network'];
        $vendor->payout_account_name = $data['payout_account_name'];
        $vendor->payout_account_number = PhoneHelper::format($data['payout_account_number']);
        $vendor->payout_account_updated_at = now();
        $vendor->save();

        $this->activityLogService->log(
            $vendor->id,
            'payout_account_updated',
            'Payout account updated successfully',
            $request,
            [
                'old' => $this->maskPayoutAccount($oldAccount),
                'new' => $this->maskPayoutAccount($this->formatPayoutAccount($vendor)),
            ]
        );

        return [
            'success' => true,
            'message' => 'Payout account updated successfully.',
            'data' => [
                'payout_account' => $this->formatPayoutAccount($vendor),
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
            'payout_account' => $this->formatPayoutAccount($vendor),
            'created_at' => $vendor->created_at?->toISOString(),
        ];
    }

    public function formatPayoutAccount(Vendor $vendor): array
    {
        $isSet = filled($vendor->payout_momo_network)
            && filled($vendor->payout_account_name)
            && filled($vendor->payout_account_number);

        return [
            'is_set' => $isSet,
            'method' => 'momo',
            'network' => $vendor->payout_momo_network,
            'account_name' => $vendor->payout_account_name,
            'account_number' => $vendor->payout_account_number,
            'updated_at' => $vendor->payout_account_updated_at?->toISOString(),
        ];
    }

    protected function maskPayoutAccount(array $account): array
    {
        $number = (string) ($account['account_number'] ?? '');

        if ($number !== '') {
            $digits = preg_replace('/\D/', '', $number);
            $account['account_number'] = strlen($digits) >= 4
                ? str_repeat('*', max(strlen($digits) - 4, 0)) . substr($digits, -4)
                : '****';
        }

        return $account;
    }

    /**
     * Format mobile app configuration values exposed to vendors.
     */
    protected function formatAppConfig(): array
    {
        return [
            'support_phone' => PlatformSetting::getValue('platform_phone', ''),
        ];
    }
}
