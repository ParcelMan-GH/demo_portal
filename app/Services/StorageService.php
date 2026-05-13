<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Aws\S3\S3Client;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    private ?S3Client $s3Client = null;
    private ?string $driver = null;
    private ?Filesystem $disk = null;
    private ?Filesystem $s3Disk = null;
    private ?array $s3Config = null;

    /**
     * Get the current storage driver from platform settings.
     */
    public function getDriver(): string
    {
        return $this->driver ??= PlatformSetting::getValue('storage.driver', 'local');
    }

    /**
     * Get the configured disk instance.
     */
    public function getDisk(): Filesystem
    {
        if ($this->disk !== null) {
            return $this->disk;
        }

        $driver = $this->getDriver();

        if ($driver === 's3') {
            return $this->disk = $this->getS3Disk();
        }

        return $this->disk = Storage::disk('local');
    }

    /**
     * Get S3 disk with dynamic configuration.
     */
    private function getS3Disk(): Filesystem
    {
        if ($this->s3Disk !== null) {
            return $this->s3Disk;
        }

        // Create a dynamic filesystem config
        config(['filesystems.disks.storj' => $this->getS3Config()]);

        return $this->s3Disk = Storage::disk('storj');
    }

    /**
     * Get S3 settings once per service instance.
     *
     * @return array<string, mixed>
     */
    private function getS3Config(): array
    {
        return $this->s3Config ??= [
            'driver' => 's3',
            'key' => PlatformSetting::getValue('storage.s3.access_key', ''),
            'secret' => PlatformSetting::getValue('storage.s3.secret_key', ''),
            'region' => 'us-east-1',
            'bucket' => PlatformSetting::getValue('storage.s3.bucket', ''),
            'endpoint' => PlatformSetting::getValue('storage.s3.endpoint', ''),
            'use_path_style_endpoint' => true,
        ];
    }

    /**
     * Get the S3 client for signed URLs.
     */
    private function getS3Client(): S3Client
    {
        if ($this->s3Client === null) {
            $config = $this->getS3Config();

            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region' => 'us-east-1',
                'endpoint' => $config['endpoint'],
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => $config['key'],
                    'secret' => $config['secret'],
                ],
            ]);
        }

        return $this->s3Client;
    }

    /**
     * Get the environment folder prefix for S3.
     */
    private function getEnvPrefix(): string
    {
        return PlatformSetting::getValue('storage.s3.env', 'demo');
    }

    /**
     * Upload a file to storage.
     *
     * @param UploadedFile $file
     * @param string $path Path within storage (e.g., "shipments/1/items/1")
     * @return array{path: string, original_name: string, size: int}
     */
    public function upload(UploadedFile $file, string $path): array
    {
        $driver = $this->getDriver();
        $disk = $this->getDisk();

        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Add environment prefix for S3
        $fullPath = $driver === 's3'
            ? $this->getEnvPrefix() . '/' . $path . '/' . $filename
            : $path . '/' . $filename;

        // Store the file
        $disk->put($fullPath, file_get_contents($file->getRealPath()));

        return [
            'path' => $fullPath,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Delete a file from storage.
     */
    public function delete(string $path): bool
    {
        $disk = $this->getDisk();

        if ($disk->exists($path)) {
            return $disk->delete($path);
        }

        return false;
    }

    /**
     * Get URL for a file.
     * Returns signed URL for S3, direct URL for local.
     */
    public function getUrl(string $path): string
    {
        $driver = $this->getDriver();
        $disk = $this->getDisk();

        if ($driver === 's3') {
            return $this->getSignedUrl($path);
        }

        // Local/private files are served through signed temporary URLs.
        if (method_exists($disk, 'temporaryUrl')) {
            try {
                $expiry = (int) PlatformSetting::getValue('storage.local.signed_url_expiry', 60);
                return $disk->temporaryUrl($path, now()->addMinutes($expiry));
            } catch (\Throwable) {
                // Fallback below if temporary URLs are unavailable in current environment.
            }
        }

        return url($disk->url($path));
    }

    /**
     * Generate a temporary signed URL for S3.
     */
    public function getSignedUrl(string $path, ?int $expiryMinutes = null): string
    {
        if ($expiryMinutes === null) {
            $expiryMinutes = (int) PlatformSetting::getValue('storage.s3.signed_url_expiry', 60);
        }

        $bucket = $this->getS3Config()['bucket'];
        $client = $this->getS3Client();

        $cmd = $client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $path,
        ]);

        $request = $client->createPresignedRequest($cmd, "+{$expiryMinutes} minutes");

        return (string) $request->getUri();
    }

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool
    {
        return $this->getDisk()->exists($path);
    }

    /**
     * Get file size.
     */
    public function size(string $path): int
    {
        return $this->getDisk()->size($path);
    }
}
