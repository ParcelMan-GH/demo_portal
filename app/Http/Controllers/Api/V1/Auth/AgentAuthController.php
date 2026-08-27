<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentAuthController extends Controller
{
    /**
     * Login Agent via Email and Password
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Find the user/agent by email
        $agent = User::where('email', $request->email)->first();

        // Check if agent exists and password matches
        if (! $agent || ! Hash::check($request->password, $agent->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Generate a fresh Sanctum token bound to this Agent model
        $token = $agent->createToken('agent-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $agent,
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