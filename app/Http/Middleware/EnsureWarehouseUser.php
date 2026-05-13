<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureWarehouseUser
{
    /**
     * Ensure the authenticated admin user belongs to a warehouse context.
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

        // Super admins can enter the warehouse portal only when a warehouse
        // context has been explicitly assigned or selected.
        if ($user->isSuperAdmin()) {
            if ((int) ($user->warehouse_id ?? 0) <= 0) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Select a warehouse context before using warehouse routes.'], 403);
                }

                return redirect()
                    ->route('admin.dashboard')
                    ->with('error', 'Select a warehouse context before using warehouse routes.');
            }

            return $next($request);
        }

        $hasWarehouseRole = $user->roles()->where('is_warehouse_role', true)->exists();

        if ((int) ($user->warehouse_id ?? 0) <= 0 || !$hasWarehouseRole) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This route is only available to warehouse users.'], 403);
            }

            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'This section is only available to warehouse users.');
        }

        $warehouse = $user->warehouse()->first();

        if (!$warehouse || !$warehouse->is_active) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Assigned warehouse is unavailable.'], 403);
            }

            return redirect()
                ->route('admin.login')
                ->with('error', 'Your warehouse account is inactive. Please contact an administrator.');
        }

        return $next($request);
    }
}
