<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ProfilePhotoService;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
