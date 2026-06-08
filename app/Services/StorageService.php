<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Aws\S3\S3Client;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    private const S3_REQUIRED_FIELDS = [
        'key' => 'Access key',
        'secret' => 'Secret key',
        'bucket' => 'Bucket',
        'endpoint' => 'Endpoint',
        'region' => 'Region',
    ];

    private ?S3Client $s3Client = null;

    private ?string $driver = null;

    private ?Filesystem $disk = null;

    private ?Filesystem $s3Disk = null;

    private ?array $s3Config = null;

    private ?Filesystem $publicDisk = null;

    /**
     * Get the current storage driver from platform settings.
     */
    public function getDriver(): string
    {
        return $this->driver ??= PlatformSetting::getValue('storage.driver', 'local');
    }

    /**
     * Check whether S3-compatible storage has enough config to sign and upload files.
     */
    public function hasCompleteS3Config(): bool
    {
        return $this->missingS3ConfigFields() === [];
    }

    /**
     * List missing S3-compatible config labels without exposing stored secrets.
     *
     * @return array<int, string>
     */
    public function missingS3ConfigFields(): array
    {
        $config = $this->getS3Config();

        return collect(self::S3_REQUIRED_FIELDS)
            ->filter(fn (string $label, string $key) => blank($config[$key] ?? null))
            ->values()
            ->all();
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

        return $this->disk = Storage::disk('public');
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
            'region' => PlatformSetting::getValue('storage.s3.region', 'us-east-1'),
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
                'region' => $config['region'],
                'endpoint' => $config['endpoint'],
                'use_path_style_endpoint' => true,
                'http' => [
                    'connect_timeout' => 5,
                    'timeout' => 10,
                ],
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
        $prefix = trim((string) PlatformSetting::getValue('storage.s3.env', 'demo'), '/');

        return filled($prefix) ? $prefix : 'demo';
    }

    /**
     * Upload a file to storage.
     *
     * @param  string  $path  Path within storage (e.g., "shipments/1/items/1")
     * @return array{path: string, original_name: string, size: int}
     */
    public function upload(UploadedFile $file, string $path): array
    {
        $driver = $this->getDriver();

        if ($driver === 's3' && ! $this->hasCompleteS3Config()) {
            throw new \RuntimeException('S3/Storj storage is selected but access key, secret key, bucket, endpoint, and region are not configured.');
        }

        $disk = $this->getDisk();

        // Generate unique filename
        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

        // Add environment prefix for S3
        $fullPath = $driver === 's3'
            ? $this->getEnvPrefix().'/'.$path.'/'.$filename
            : $path.'/'.$filename;

        $this->writeToDisk($disk, $fullPath, file_get_contents($file->getRealPath()));

        return [
            'path' => $fullPath,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Compatibility alias for older controllers.
     *
     * @param  string  $path  Path within storage.
     * @return array{path: string, original_name: string, size: int}
     */
    public function uploadFile(UploadedFile $file, string $path): array
    {
        return $this->upload($file, $path);
    }

    /**
     * Store generated content using the active storage driver.
     *
     * @return array{path: string, size: int}
     */
    public function putContent(string $path, string $contents): array
    {
        $driver = $this->getDriver();

        if ($driver === 's3' && ! $this->hasCompleteS3Config()) {
            throw new \RuntimeException('S3/Storj storage is selected but access key, secret key, bucket, endpoint, and region are not configured.');
        }

        $fullPath = $driver === 's3'
            ? $this->getEnvPrefix().'/'.ltrim($path, '/')
            : ltrim($path, '/');

        $this->writeToDisk($this->getDisk(), $fullPath, $contents);

        return [
            'path' => $fullPath,
            'size' => strlen($contents),
        ];
    }

    /**
     * Delete a file from storage.
     */
    public function delete(string $path): bool
    {
        $deleted = false;

        if ($this->publicDisk()->exists($path)) {
            $deleted = (bool) $this->publicDisk()->delete($path);
        }

        if ($this->getDriver() === 's3' && ! $this->hasCompleteS3Config()) {
            return $deleted;
        }

        $disk = $this->getDisk();

        if ($disk->exists($path)) {
            $deleted = (bool) $disk->delete($path) || $deleted;
        }

        return $deleted;
    }

    /**
     * Get URL for a file.
     * Returns signed URL for S3, direct URL for local.
     */
    public function getUrl(string $path): string
    {
        if ($publicUrl = $this->getPublicUrlIfExists($path)) {
            return $publicUrl;
        }

        $driver = $this->getDriver();

        if ($driver === 's3') {
            if (! $this->hasCompleteS3Config()) {
                return '';
            }

            return $this->getSignedUrl($path);
        }

        $disk = $this->getDisk();

        return url($disk->url($path));
    }

    /**
     * Generate a temporary signed URL for S3.
     */
    public function getSignedUrl(string $path, ?int $expiryMinutes = null): string
    {
        if (! $this->hasCompleteS3Config()) {
            return '';
        }

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
        if ($this->publicDisk()->exists($path)) {
            return true;
        }

        if ($this->getDriver() === 's3' && ! $this->hasCompleteS3Config()) {
            return false;
        }

        return $this->getDisk()->exists($path);
    }

    /**
     * Return a redacted storage health summary for the admin settings screen.
     *
     * @return array<string, mixed>
     */
    public function connectionStatus(): array
    {
        $driver = $this->getDriver();

        if ($driver !== 's3') {
            $localPath = storage_path('app/public');
            $publicLink = public_path('storage');
            $linked = is_link($publicLink) || file_exists($publicLink);
            $writable = is_dir($localPath) && is_writable($localPath);

            return [
                'driver' => 'local',
                'configured' => true,
                'reachable' => $linked && $writable,
                'message' => $linked && $writable
                    ? 'Local public storage is linked and writable.'
                    : 'Local public storage needs a writable directory and public storage link.',
                'local_path' => $localPath,
                'public_linked' => $linked,
                'writable' => $writable,
            ];
        }

        $config = $this->getS3Config();
        $missing = $this->missingS3ConfigFields();

        $status = [
            'driver' => 's3',
            'configured' => $missing === [],
            'reachable' => false,
            'message' => $missing === []
                ? 'Storj configuration is complete; checking bucket reachability.'
                : 'Storj configuration is incomplete: '.implode(', ', $missing).'.',
            'bucket' => $config['bucket'],
            'endpoint' => $config['endpoint'],
            'region' => $config['region'],
            'prefix' => $this->getEnvPrefix(),
            'signed_url_expiry' => (int) PlatformSetting::getValue('storage.s3.signed_url_expiry', 60),
            'access_key_configured' => filled($config['key']),
            'secret_key_configured' => filled($config['secret']),
            'missing_fields' => $missing,
        ];

        if ($missing !== []) {
            return $status;
        }

        try {
            $this->getS3Client()->listObjectsV2([
                'Bucket' => $config['bucket'],
                'Prefix' => trim($this->getEnvPrefix(), '/').'/',
                'MaxKeys' => 1,
            ]);

            $status['reachable'] = true;
            $status['message'] = 'Storj bucket is reachable with the saved database settings.';
        } catch (\Throwable $e) {
            $status['message'] = 'Storj bucket check failed: '.$this->sanitizeStorageError($e->getMessage());
        }

        return $status;
    }

    /**
     * Get file size.
     */
    public function size(string $path): int
    {
        if ($this->publicDisk()->exists($path)) {
            return $this->publicDisk()->size($path);
        }

        return $this->getDisk()->size($path);
    }

    private function publicDisk(): Filesystem
    {
        return $this->publicDisk ??= Storage::disk('public');
    }

    private function writeToDisk(Filesystem $disk, string $path, string $contents): void
    {
        try {
            $written = $disk->put($path, $contents);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unable to write file to configured storage disk.', previous: $e);
        }

        if ($written === false) {
            throw new \RuntimeException('Unable to write file to configured storage disk.');
        }
    }

    private function sanitizeStorageError(string $message): string
    {
        $message = preg_replace('/(Credential=)[^,\s&]+/', '$1REDACTED', $message) ?? $message;
        $message = preg_replace('/(AWSAccessKeyId=)[^,\s&]+/', '$1REDACTED', $message) ?? $message;

        return str($message)->limit(300)->toString();
    }

    private function getPublicUrlIfExists(string $path): ?string
    {
        if ($path === '' || ! $this->publicDisk()->exists($path)) {
            return null;
        }

        return url($this->publicDisk()->url($path));
    }
}
