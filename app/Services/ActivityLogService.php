<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorActivityLog;
use Illuminate\Http\Request;

class ActivityLogService
{
    /**
     * Log a vendor activity.
     */
    public function log(
        ?int $vendorId,
        string $action,
        ?string $description,
        Request $request,
        array $metadata = []
    ): VendorActivityLog {
        $deviceInfo = $this->getDeviceInfo($request);

        return VendorActivityLog::create([
            'vendor_id' => $vendorId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'device_type' => $deviceInfo['device_type'],
            'device_name' => $deviceInfo['device_name'],
            'os_version' => $deviceInfo['os_version'],
            'app_version' => $deviceInfo['app_version'],
            'metadata' => !empty($metadata) ? $metadata : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Log a vendor activity using vendor model.
     */
    public function logVendor(
        Vendor $vendor,
        string $action,
        ?string $description,
        Request $request,
        array $metadata = []
    ): VendorActivityLog {
        return $this->log(
            $vendor->id,
            $action,
            $description,
            $request,
            $metadata
        );
    }

    /**
     * Extract device info from request headers.
     */
    public function getDeviceInfo(Request $request): array
    {
        return [
            'device_type' => $request->header('X-Device-Type'),
            'device_name' => $request->header('X-Device-Name'),
            'os_version' => $request->header('X-OS-Version'),
            'app_version' => $request->header('X-App-Version'),
        ];
    }
}
