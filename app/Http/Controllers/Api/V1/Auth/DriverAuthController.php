<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DriverAuthController extends Controller
{
    /**
     * Authenticate Transporters and Riders for the driver mobile app binary.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string', 'in:transporter,rider'],
        ]);

        // Normalize phone number search
        $phone = preg_replace('/[^0-9]/', '', $request->phone);

        $user = User::query()
            ->where(function ($q) use ($phone, $request) {
                $q->where('phone', $request->phone)
                  ->orWhere('phone', 'like', "%{$phone}");
            })
            ->with(['role', 'warehouse'])
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Invalid phone number or password.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account is currently inactive. Please contact your warehouse supervisor.',
            ], 403);
        }

        // Verify the user's database role matches the selected app login role
        $userRoleSlug = strtolower($user->role?->slug ?? '');
        $requestedRole = strtolower($request->role);

        if ($userRoleSlug !== $requestedRole) {
            return response()->json([
                'message' => "Access denied. Your assigned role ({$user->role?->name}) does not match the {$request->role} portal.",
            ], 403);
        }

        // Generate Sanctum access token
        $token = $user->createToken('driver-app-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => strtoupper($userRoleSlug),
                'warehouse_id' => $user->warehouse_id,
                'warehouse_name' => $user->warehouse?->name,
                'vehicle' => $user->vehicle_type ?? 'Standard Transport',
                'plateNumber' => $user->plate_number ?? 'N/A',
            ],
        ]);
    }

    /**
     * Terminate the driver session and revoke access tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ]);
    }
}