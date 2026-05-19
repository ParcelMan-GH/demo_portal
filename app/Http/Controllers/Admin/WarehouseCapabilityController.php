<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseCapability;
use App\Services\BackOfficeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseCapabilityController extends Controller
{
    public function index(Warehouse $warehouse): JsonResponse
    {
        $this->authorizeHq();

        $warehouse->load('capabilities.grantedBy:id,name');

        return response()->json([
            'modules' => config('backoffice.modules', []),
            'capabilities' => $warehouse->capabilities
                ->mapWithKeys(fn (WarehouseCapability $capability) => [
                    $capability->module => [
                        'scope' => $capability->scope,
                        'allowed_warehouse_ids' => $capability->allowed_warehouse_ids ?? [],
                        'granted_by' => $capability->grantedBy?->name,
                    ],
                ])
                ->all(),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorizeHq();

        abort_if($warehouse->is_hq, 422, 'HQ warehouses already have full access.');

        $modules = array_keys(config('backoffice.modules', []));
        $validated = $request->validate([
            'capabilities' => ['array'],
            'capabilities.*.module' => ['required', 'string', 'in:' . implode(',', $modules)],
            'capabilities.*.scope' => ['required', 'string', 'in:own,selected,global'],
            'capabilities.*.allowed_warehouse_ids' => ['nullable', 'array'],
            'capabilities.*.allowed_warehouse_ids.*' => ['integer', 'exists:warehouses,id'],
        ]);

        $submitted = collect($validated['capabilities'] ?? []);
        $submittedModules = $submitted->pluck('module')->all();

        $warehouse->capabilities()
            ->whereNotIn('module', $submittedModules)
            ->delete();

        foreach ($submitted as $capability) {
            $warehouse->capabilities()->updateOrCreate(
                ['module' => $capability['module']],
                [
                    'scope' => $capability['scope'],
                    'allowed_warehouse_ids' => $capability['scope'] === WarehouseCapability::SCOPE_SELECTED
                        ? array_values(array_unique(array_map('intval', $capability['allowed_warehouse_ids'] ?? [])))
                        : null,
                    'granted_by_user_id' => Auth::guard('admin')->id(),
                ]
            );
        }

        return $this->index($warehouse->fresh());
    }

    private function authorizeHq(): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user && app(BackOfficeAccess::class)->isHq($user), 403);
    }
}
