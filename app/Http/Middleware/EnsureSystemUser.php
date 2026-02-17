<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemUser
{
    /**
     * Ensure the authenticated admin user is in system-admin context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('admin.login');
        }

        if (!empty($user->warehouse_id)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'System admin routes are not available for warehouse users.'], 403);
            }

            return redirect()
                ->route('warehouse.dashboard')
                ->with('error', 'You do not have access to system admin routes.');
        }

        return $next($request);
    }
}

