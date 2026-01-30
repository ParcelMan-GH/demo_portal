<?php

namespace App\Services;

use App\Models\DriverActivityLog;
use Illuminate\Http\Request;

class DriverActivityLogService
{
    /**
     * Log a driver activity.
     */
    public function log(
        ?int $driverId,
        string $action,
        ?string $description,
        Request $request
    ): ?DriverActivityLog {
        if (!$driverId) {
            return null;
        }

        $deviceInfo = $this->getDeviceInfo($request);

        return DriverActivityLog::create([
            'driver_id' => $driverId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'device_type' => $deviceInfo['device_type'],
            'device_name' => $deviceInfo['device_name'],
            'os_version' => $deviceInfo['os_version'],
            'app_version' => $deviceInfo['app_version'],
            'created_at' => now(),
        ]);
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
