<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentAuthController extends Controller
{
    /**
     * Agent login, by phone number or email.
     *
     * Phone is the primary identifier now: the agent app signs in with the
     * number the hub files them under. Email is kept working so no existing
     * client is forced to migrate, and `phone_number` is accepted alongside
     * `phone` because the app's field naming has drifted between screens.
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string|max:32',
            'phone_number' => 'nullable|string|max:32',
            'email' => 'nullable|email',
            'password' => 'required|string',
        ]);

        $phone = $request->input('phone') ?: $request->input('phone_number');
        $email = $request->input('email');

        if (blank($phone) && blank($email)) {
            return response()->json([
                'success' => false,
                'message' => 'Enter your phone number to sign in.',
            ], 422);
        }

        // Comparison goes through PhoneNumber because the column is not uniform:
        // most rows hold `+233...` but at least one holds `024...`, so a rider
        // typing the local form would otherwise never be found.
        $agent = $phone
            ? PhoneNumber::match(User::query(), (string) $phone)->first()
            : User::where('email', $email)->first();

        if (! $agent || ! Hash::check($request->password, $agent->password)) {
            return response()->json([
                'success' => false,
                // One message for both halves deliberately: saying which was
                // wrong would let anyone enumerate staff numbers.
                'message' => $phone
                    ? 'That phone number and PIN do not match our records.'
                    : 'Invalid email or password.',
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