<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfilePhotoService;
use App\Services\UserProfileService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The signed-in user's own profile, whatever app they signed in through.
 *
 * Kept separate from the hub endpoints because a hub agent, a bus handoff agent
 * and a vendor all authenticate as the same `users` row; changing an avatar is
 * not a hub operation and should not require a hub role.
 */
class UserProfileController extends Controller
{
    public function __construct(private UserProfileService $service) {}

    /**
     * The signed-in user, with an absolute photo URL.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->service->getProfile($request->user()));
    }

    /**
     * Replace the signed-in user's profile photo.
     *
     * POST rather than PUT: PHP only populates uploaded files on a POST body, so
     * a multipart PUT arrives at the server empty.
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate(ProfilePhotoService::rules());

        return response()->json(
            $this->service->updatePhoto($request->user(), $request->file('photo'))
        );
    }

    /**
     * Update the details the agent maintains about themselves.
     *
     * POST rather than PATCH so a client can send this as multipart alongside a
     * photo if it ever needs both in one trip.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
        ]);

        // A plain `unique` rule cannot see across the two shapes the phone column
        // holds, so duplicates are checked through the same normaliser the login
        // uses. Without it, `024...` and `+233...` could be taken by two accounts
        // and the phone login would resolve ambiguously.
        if (! blank($validated['phone'] ?? null)) {
            $taken = PhoneNumber::match(User::query(), (string) $validated['phone'])
                ->whereKeyNot($user->getKey())
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages([
                    'phone' => 'That phone number already belongs to another account.',
                ]);
            }
        }

        return response()->json($this->service->update($user, $validated));
    }
}
