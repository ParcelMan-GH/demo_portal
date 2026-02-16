<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AdminAuditLogService
{
    /**
     * Keys that must never be stored in plain text.
     *
     * @var array<int, string>
     */
    private array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'remember_token',
        'otp',
        'code',
        'api_key',
        'secret',
        'authorization',
    ];

    public function logRequest(Request $request, Response $response, ?User $actor, int $durationMs): void
    {
        if (!$actor) {
            return;
        }

        $route = $request->route();
        $routeName = $route?->getName();
        $statusCode = $response->getStatusCode();
        $actionType = $this->resolveActionType($request->method(), $routeName);

        $metadata = [
            'query' => $this->sanitize($request->query()),
            'payload' => $this->extractPayload($request),
            'files' => $this->extractFiles($request),
            'route_parameters' => $this->extractRouteParameters($request),
        ];

        AdminAuditLog::query()->create([
            'user_id' => $actor->id,
            'warehouse_id' => $actor->warehouse_id,
            'scope' => $actor->warehouse_id ? 'warehouse' : 'system',
            'action_type' => $actionType,
            'action' => $routeName ?: ($request->method() . ' ' . $request->path()),
            'description' => $this->buildDescription($actionType, $routeName, $request->method(), $statusCode),
            'method' => $request->method(),
            'route_name' => $routeName,
            'url' => Str::limit($request->fullUrl(), 2048, ''),
            'status_code' => $statusCode,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
            'duration_ms' => max(0, $durationMs),
            'metadata' => $metadata,
        ]);
    }

    public function logException(Request $request, Throwable $exception, ?User $actor, int $durationMs): void
    {
        $route = $request->route();
        $routeName = $route?->getName();

        AdminAuditLog::query()->create([
            'user_id' => $actor?->id,
            'warehouse_id' => $actor?->warehouse_id,
            'scope' => $actor?->warehouse_id ? 'warehouse' : ($actor ? 'system' : null),
            'action_type' => 'error',
            'action' => $routeName ?: ($request->method() . ' ' . $request->path()),
            'description' => 'Unhandled exception during request',
            'method' => $request->method(),
            'route_name' => $routeName,
            'url' => Str::limit($request->fullUrl(), 2048, ''),
            'status_code' => 500,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
            'duration_ms' => max(0, $durationMs),
            'metadata' => [
                'query' => $this->sanitize($request->query()),
                'payload' => $this->extractPayload($request),
                'route_parameters' => $this->extractRouteParameters($request),
                'exception' => [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
            ],
        ]);
    }

    public function logAuthEvent(
        string $action,
        string $description,
        Request $request,
        ?User $actor = null,
        array $metadata = [],
        int $statusCode = 200
    ): void {
        AdminAuditLog::query()->create([
            'user_id' => $actor?->id,
            'warehouse_id' => $actor?->warehouse_id,
            'scope' => $actor?->warehouse_id ? 'warehouse' : ($actor ? 'system' : 'system'),
            'action_type' => 'auth',
            'action' => $action,
            'description' => $description,
            'method' => $request->method(),
            'route_name' => $request->route()?->getName(),
            'url' => Str::limit($request->fullUrl(), 2048, ''),
            'status_code' => $statusCode,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
            'duration_ms' => null,
            'metadata' => $this->sanitize($metadata),
        ]);
    }

    private function resolveActionType(string $method, ?string $routeName): string
    {
        $route = strtolower((string) $routeName);
        $method = strtoupper($method);

        if (str_contains($route, 'login') || str_contains($route, 'logout')) {
            return 'auth';
        }

        if (str_contains($route, 'export')) {
            return 'export';
        }

        if ($method === 'GET' || $method === 'HEAD') {
            return 'read';
        }

        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'other',
        };
    }

    private function buildDescription(string $actionType, ?string $routeName, string $method, int $statusCode): string
    {
        $base = $routeName ?: ($method . ' request');
        return sprintf('%s (%s, HTTP %d)', $base, strtoupper($actionType), $statusCode);
    }

    private function extractPayload(Request $request): array
    {
        if (in_array(strtoupper($request->method()), ['GET', 'HEAD'], true)) {
            return [];
        }

        $payload = Arr::except($request->request->all(), ['_token', '_method']);

        return $this->sanitize($payload);
    }

    private function extractRouteParameters(Request $request): array
    {
        $route = $request->route();
        if (!$route) {
            return [];
        }

        $params = [];
        foreach ($route->parameters() as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $params[$key] = $value;
                continue;
            }

            if (is_object($value) && method_exists($value, 'getKey')) {
                $params[$key] = [
                    'model' => class_basename($value),
                    'id' => $value->getKey(),
                ];
                continue;
            }

            $params[$key] = (string) $value;
        }

        return $params;
    }

    private function extractFiles(Request $request): array
    {
        $allFiles = $request->allFiles();
        if (empty($allFiles)) {
            return [];
        }

        $result = [];
        foreach ($allFiles as $key => $file) {
            $result[$key] = $this->flattenFileInput($file);
        }

        return $result;
    }

    private function flattenFileInput(mixed $fileInput): mixed
    {
        if (is_array($fileInput)) {
            return array_map(fn($file) => $this->flattenFileInput($file), $fileInput);
        }

        if (is_object($fileInput) && method_exists($fileInput, 'getClientOriginalName')) {
            return [
                'name' => $fileInput->getClientOriginalName(),
                'mime' => $fileInput->getClientMimeType(),
                'size' => $fileInput->getSize(),
            ];
        }

        return null;
    }

    private function sanitize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $this->normalizeValue($value);
        }

        $sanitized = [];
        foreach ($value as $key => $item) {
            if ($this->isSensitiveKey((string) $key)) {
                $sanitized[$key] = '***';
                continue;
            }

            $sanitized[$key] = is_array($item)
                ? $this->sanitize($item)
                : $this->normalizeValue($item);
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);
        foreach ($this->sensitiveKeys as $sensitive) {
            if (str_contains($normalized, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof \JsonSerializable) {
            return $value->jsonSerialize();
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '[' . gettype($value) . ']';
    }
}
