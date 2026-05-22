<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminAuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function __construct(private readonly AdminAuditLogService $auditLogService)
    {
    }

    public function start(Request $request, User $user): JsonResponse
    {
        $actor = Auth::guard('admin')->user();

        abort_unless($actor && $actor->isHqUser() && $actor->hasPermission('warehouse.users.impersonate'), 403);
        abort_if($request->session()->has('impersonation.impersonator_id'), 422, 'End the current impersonation before starting another one.');
        abort_if($actor->id === $user->id, 422, 'You cannot impersonate your own account.');
        abort_unless($user->is_active, 422, 'You cannot impersonate an inactive user.');
        abort_unless((int) ($user->warehouse_id ?? 0) > 0, 422, 'Target user is not linked to a warehouse.');

        $referer = (string) $request->headers->get('referer');
        $returnUrl = str_starts_with($referer, url('/')) ? $referer : route('warehouse.users.index');
        $previousSelectedWarehouseId = $request->session()->get('backoffice.selected_warehouse_id');

        $metadata = [
            'impersonator_id' => $actor->id,
            'impersonator_name' => $actor->name,
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'target_warehouse_id' => $user->warehouse_id,
        ];

        $this->auditLogService->logAuthEvent(
            'impersonation.started',
            "Started impersonating {$user->name}",
            $request,
            $actor,
            $metadata
        );

        Auth::guard('admin')->login($user);
        $request->session()->migrate(true);
        $request->session()->forget('backoffice.selected_warehouse_id');
        $request->session()->put([
            'impersonation.impersonator_id' => $actor->id,
            'impersonation.started_at' => now()->toIso8601String(),
            'impersonation.return_url' => $returnUrl,
            'impersonation.previous_selected_warehouse_id' => $previousSelectedWarehouseId,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Logged in as {$user->name}.",
            'redirect_url' => route('warehouse.dashboard'),
        ]);
    }

    public function stop(Request $request): RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();
        $impersonatorId = (int) $request->session()->get('impersonation.impersonator_id');
        $returnUrl = $request->session()->get('impersonation.return_url') ?: route('warehouse.users.index');
        $previousSelectedWarehouseId = $request->session()->get('impersonation.previous_selected_warehouse_id');

        abort_unless($impersonatorId > 0, 403);

        $impersonator = User::query()
            ->whereKey($impersonatorId)
            ->where('is_active', true)
            ->firstOrFail();

        $metadata = [
            'impersonator_id' => $impersonator->id,
            'impersonator_name' => $impersonator->name,
            'target_user_id' => $currentUser?->id,
            'target_user_name' => $currentUser?->name,
        ];

        Auth::guard('admin')->login($impersonator);
        $request->session()->migrate(true);
        $request->session()->forget('impersonation');

        if ($previousSelectedWarehouseId) {
            $request->session()->put('backoffice.selected_warehouse_id', $previousSelectedWarehouseId);
        } else {
            $request->session()->forget('backoffice.selected_warehouse_id');
        }

        $this->auditLogService->logAuthEvent(
            'impersonation.stopped',
            "Stopped impersonating {$currentUser?->name}",
            $request,
            $impersonator,
            $metadata
        );

        return redirect()->to($returnUrl)->with('success', 'Returned to your account.');
    }
}
