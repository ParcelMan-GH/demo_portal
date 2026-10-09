<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Login for the hub app.
 *
 * The app talks in two portal roles (`hub_agent`, `bus_handoff`); the database
 * knows them as the `external_hub_agent` and `external_bus_handoff_agent` roles
 * created by the role seeder.
 */
class HubAuthController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const ROLE_MAP = [
        'hub_agent' => 'external_hub_agent',
        'bus_handoff' => 'external_bus_handoff_agent',
    ];

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string', 'in:hub_agent,bus_handoff'],
            'fcm_token' => ['nullable', 'string'],
        ]);

        // PhoneNumber normalises the typed number and matches across the two
        // shapes users.phone holds. The previous `like "%024…"` search missed a
        // stored `+233…` row, so an agent typing the local form could not sign in.
        $user = PhoneNumber::match(User::query(), (string) $validated['phone'])
            ->with(['roles', 'warehouse'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Invalid phone number or password.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account is currently inactive. Please contact your supervisor.',
            ], 403);
        }

        $expectedSlug = self::ROLE_MAP[$validated['role']];

        $roleSlugs = $user->roles
            ->pluck('slug')
            ->map(fn ($slug) => strtolower((string) $slug));

        if (! $roleSlugs->contains($expectedSlug)) {
            return response()->json([
                'message' => 'Access denied. Your assigned role does not match this hub portal.',
            ], 403);
        }

        if (! $user->warehouse_id) {
            return response()->json([
                'message' => 'No hub is assigned to this account. Ask an administrator to assign you to a hub.',
            ], 403);
        }

        if (! empty($validated['fcm_token'])) {
            $user->forceFill(['fcm_token' => $validated['fcm_token']])->save();
        }

        $token = $user->createToken('hub-app-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                // The app's own vocabulary, so it can route on it.
                'role' => $validated['role'],
                'role_slug' => $expectedSlug,
                'warehouse_id' => $user->warehouse_id,
                'hub_id' => $user->warehouse_id,
                'hub_name' => $user->warehouse?->name,
                'hub_code' => $user->warehouse?->code,
                // Sent at login too, so the header is right before the
                // dashboard call lands. Null means the app shows initials.
                'profile_photo_url' => $user->photo_path
                    ? app(StorageService::class)->getUrl($user->photo_path)
                    : null,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Signed out.',
        ]);
    }
}
