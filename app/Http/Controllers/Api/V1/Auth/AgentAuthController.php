<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AgentAuthController extends Controller
{
    /**
     * Send OTP for Agent Phone Authentication
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        // Mock OTP response or integrate your SMS gateway (e.g. 123456)
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
            'expires_in' => 300,
        ]);
    }

    /**
     * Verify Agent Phone / OTP and issue Sanctum token
     */
    public function verifyPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        // Find existing user/agent by phone
        $agent = User::where('phone', $request->phone)->first();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent account not found for this phone number.',
            ], 404);
        }

        // Update FCM token if provided
        if ($request->filled('fcm_token')) {
            $agent->update(['fcm_token' => $request->fcm_token]);
        }

        // Generate a fresh Sanctum token bound to this Agent model
        $token = $agent->createToken('agent-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $agent,
            'user_exists' => true,
        ]);
    }

    /**
     * Agent Logout
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ]);
    }
}