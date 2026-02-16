<?php

namespace App\Http\Middleware;

use App\Services\AdminAuditLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogAdminAuditActivity
{
    public function __construct(private AdminAuditLogService $auditLogService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $actor = Auth::guard('admin')->user();

        try {
            /** @var Response $response */
            $response = $next($request);
        } catch (Throwable $exception) {
            $duration = (int) round((microtime(true) - $startedAt) * 1000);
            $this->auditLogService->logException($request, $exception, $actor, $duration);

            throw $exception;
        }

        $duration = (int) round((microtime(true) - $startedAt) * 1000);
        $this->auditLogService->logRequest($request, $response, $actor, $duration);

        return $response;
    }
}

