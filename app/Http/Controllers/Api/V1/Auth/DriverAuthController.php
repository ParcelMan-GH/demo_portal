<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
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

        /*
         * The users.phone column is not uniform (`+233…` and `0…` both occur), so
         * the lookup goes through PhoneNumber: it normalises whatever the driver
         * typed and compares on the subscriber number. The previous
         * `orWhere('phone', 'like', "%{$phone}")` never matched a stored
         * `+233551234567` for a driver typing `0551234567`, so the local form
         * could not sign in at all.
         */
        $user = PhoneNumber::match(User::query(), (string) $request->phone)
            ->with(['roles', 'warehouse'])
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

        // Get the primary assigned role model
        $primaryRole = $user->roles->first();
        $userRoleSlug = strtolower($primaryRole?->slug ?? '');
        $requestedRole = strtolower($request->role);

        if ($userRoleSlug !== $requestedRole) {
            return response()->json([
                'message' => "Access denied. Your assigned role ({$primaryRole?->name}) does not match the {$request->role} portal.",
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