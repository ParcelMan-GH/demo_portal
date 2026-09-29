<?php

namespace App\Services;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\UploadedFile;

/**
 * The signed-in user's own profile, for the app.
 *
 * Mirrors DriverProfileService/VendorProfileService so all three go through the
 * same ProfilePhotoService — one size limit, one set of accepted types, and one
 * place that deletes the photo being replaced.
 */
class UserProfileService
{
    public function __construct(
        private readonly ProfilePhotoService $photoService,
    ) {}

    /**
     * The user as the app needs them: identity, plus an absolute photo URL.
     *
     * `photo_url` is included beside `profile_photo_url` because the driver and
     * vendor screens already read `photo_url`; sending both means a shared
     * component does not have to know which app it is in.
     *
     * @return array<string, mixed>
     */
    public function formatUser(User $user): array
    {
        $url = $this->photoService->url($user->photo_path);

        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'photo_path' => $user->photo_path,
            'profile_photo_url' => $url,
            'photo_url' => $url,
        ];
    }

    /**
     * Replace the user's profile photo, removing the one it replaces.
     *
     * @return array<string, mixed>
     */
    public function updatePhoto(User $user, UploadedFile $file): array
    {
        // Fails with a readable message if the code is deployed before the
        // migration, instead of a raw unknown-column 500.
        $this->photoService->assertSupported($user->getTable());

        $user->photo_path = $this->photoService->replace(
            $file,
            ProfilePhotoService::FOLDER_USER,
            $user->photo_path,
        );
        $user->save();

        return [
            'success' => true,
            'message' => 'Profile photo updated successfully.',
            'data' => ['user' => $this->formatUser($user->fresh())],
        ];
    }

    /**
     * The signed-in user's own profile.
     *
     * @return array<string, mixed>
     */
    public function getProfile(User $user): array
    {
        return [
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => ['user' => $this->formatUser($user)],
        ];
    }

    /**
     * Update the parts of the profile the agent maintains about themselves.
     *
     * A phone change is normalised on the way in. The column already holds two
     * shapes, and letting the app add a third would make the phone login this
     * same release introduces less reliable over time, not more.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function update(User $user, array $attributes): array
    {
        if (array_key_exists('name', $attributes)) {
            $user->name = $attributes['name'];
        }

        if (array_key_exists('phone', $attributes)) {
            $user->phone = PhoneNumber::normalise($attributes['phone']);
        }

        if (array_key_exists('email', $attributes)) {
            $user->email = $attributes['email'];
        }

        $user->save();

        return [
            'success' => true,
            'message' => 'Profile updated.',
            'data' => ['user' => $this->formatUser($user->fresh())],
        ];
    }
}
