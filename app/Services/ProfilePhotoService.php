<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Profile photo storage, shared by the driver, vendor and user profile
 * services so the size limit, accepted types and cleanup behaviour cannot
 * drift between them.
 */
class ProfilePhotoService
{
    public const FOLDER_DRIVER = 'driver-photos';
    public const FOLDER_VENDOR = 'vendor-photos';
    public const FOLDER_USER = 'user-photos';

    /**
     * Photos are capped at 5MB. The app resizes before upload, but the server
     * has to enforce its own limit rather than trust the client.
     */
    public const MAX_KILOBYTES = 5120;

    /**
     * Validation rules for an incoming photo.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(string $field = 'photo'): array
    {
        return [
            $field => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_KILOBYTES],
        ];
    }

    public function __construct(
        private readonly StorageService $storage,
    ) {}

    /**
     * Store a new photo for a profile, removing the one it replaces.
     *
     * @return string the stored path
     */
    public function replace(UploadedFile $file, string $folder, ?string $previousPath = null): string
    {
        $stored = $this->storage->upload($file, $folder);
        $newPath = $stored['path'];

        // Only remove the old file once the new one is safely written — losing
        // the previous photo because an upload failed halfway would be worse
        // than keeping an orphan.
        if ($previousPath && $previousPath !== $newPath) {
            try {
                $this->storage->delete($previousPath);
            } catch (\Throwable $e) {
                // A stale file is not worth failing the request over.
                report($e);
            }
        }

        return $newPath;
    }

    /**
     * Public URL for a stored path, or null when there is no photo.
     */
    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            return $this->storage->getUrl($path);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
