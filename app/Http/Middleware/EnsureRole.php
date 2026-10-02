<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate an API group on the roles allowed to call it.
 *
 * The hub and vendor APIs each already had their own gate (`hub.agent`,
 * `vendor.active`). The agent and driver APIs had only `auth:sanctum`, so a
 * token issued to *any* `users` row reached them — a hub agent or a back-office
 * account could sign in at `/v1/agent/login` and work as a call agent, and any
 * account with a phone could be auto-provisioned a rider profile on
 * `/v1/driver/*`. This is the missing equivalent for those two groups.
 *
 * Roles are matched against the account's assigned roles, so a user holding
 * several still passes if any one of them is allowed. Like EnsureHubAgent, this
 * fails closed: an unrecognised or missing role is a refusal, never a pass.
 */
class EnsureRole
{
    /**
     * @param  string  ...$roles  Allowed role slugs, e.g. `role:transporter,rider`.
     *                            Laravel splits the middleware argument on commas,
     *                            so both forms arrive here already separated.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $allowed = collect($roles)
            ->flatMap(fn ($role) => explode(',', (string) $role))
            ->map(fn ($role) => strtolower(trim($role)))
            ->filter()
            ->values();

        /*
         * A route wired with no roles is a mistake, and a mistake here would be an
         * open door. Refuse it rather than treating "no roles named" as "any role".
         */
        if ($allowed->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'This account does not have access to this app.',
            ], 403);
        }

        /*
         * Not every authenticatable model carries roles: tokens are issued to
         * `User`, but a model without the relation would throw on `->roles` and
         * turn a permissions question into a 500. Absent relation means no roles,
         * which means no access.
         */
        if (! method_exists($user, 'roles')) {
            return response()->json([
                'success' => false,
                'message' => 'This account does not have access to this app.',
            ], 403);
        }

        $held = $user->roles
            ->pluck('slug')
            ->map(fn ($slug) => strtolower((string) $slug));

        if ($held->intersect($allowed)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'This account does not have access to this app.',
            ], 403);
        }

        return $next($request);
    }
}
