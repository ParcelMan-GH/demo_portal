<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorActive
{
    /**
     * Ensure the authenticated vendor is active and not soft-deleted.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof Vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Check if vendor was soft-deleted
        if ($user->trashed()) {
            $user->currentAccessToken()->delete();

            return response()->json([
                'success' => false,
                'message' => 'Your account has been removed. Please contact support.',
            ], 401);
        }

        // Check if vendor is active
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support.',
            ], 403);
        }

        return $next($request);
    }
}
