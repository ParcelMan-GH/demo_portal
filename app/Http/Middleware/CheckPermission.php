<?php

namespace App\Http\Middleware;

use App\Services\BackOfficeAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(private BackOfficeAccess $access)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::guard('admin')->user();

        // Check if user is authenticated
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
            abort(401, 'Unauthenticated');
        }

        // Check if user has the required permission
        if (!$this->access->canUsePermission($user, $permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
