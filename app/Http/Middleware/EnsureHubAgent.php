<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the hub APIs.
 *
 * A hub agent works out of a single hub (`users.warehouse_id`), so a token whose
 * owner has no hub assigned, or does not hold a hub-side role, is refused here
 * rather than half-working deeper in the controllers.
 */
class EnsureHubAgent
{
    /**
     * The roles allowed to call the hub APIs. Both already exist in the role
     * seeder; a hub agent runs the hub, a bus handoff agent puts parcels on the
     * intercity bus.
     *
     * @var array<int, string>
     */
    public const ROLES = ['external_hub_agent', 'external_bus_handoff_agent'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $roleSlugs = $user->roles
            ->pluck('slug')
            ->map(fn ($slug) => strtolower((string) $slug));

        if ($roleSlugs->intersect(self::ROLES)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'This account does not have hub access.',
            ], 403);
        }

        if (! $user->warehouse_id) {
            return response()->json([
                'success' => false,
                'message' => 'No hub is assigned to this account. Ask an administrator to assign you to a hub.',
            ], 403);
        }

        return $next($request);
    }
}
