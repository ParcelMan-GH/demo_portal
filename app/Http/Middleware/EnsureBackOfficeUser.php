<?php

namespace App\Http\Middleware;

use App\Services\BackOfficeAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBackOfficeUser
{
    public function __construct(private BackOfficeAccess $access)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('admin.login');
        }

        $warehouse = $this->access->warehouseFor($user);

        if (!$warehouse) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'A warehouse context is required for back-office access.'], 403);
            }

            return redirect()
                ->route('admin.login')
                ->with('error', 'Your account is not linked to a warehouse. Please contact an administrator.');
        }

        if (!$warehouse->is_active) {
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

        app()->instance('backoffice.access', $this->access);

        return $next($request);
    }
}
