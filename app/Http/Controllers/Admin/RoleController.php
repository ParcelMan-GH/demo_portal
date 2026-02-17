<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class RoleController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:roles.view', only: ['index', 'warehouseIndex', 'show', 'data', 'export']),
            new Middleware('permission:roles.create', only: ['create', 'store']),
            new Middleware('permission:roles.edit', only: ['edit', 'update']),
            new Middleware('permission:roles.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of roles.
     */
    public function index(Request $request): View
    {
        return view('admin.roles.index', [
            'roleScope' => $this->resolveScope($request),
        ]);
    }

    /**
     * Display a listing of warehouse roles.
     */
    public function warehouseIndex(Request $request): View
    {
        return view('admin.roles.index', [
            'roleScope' => $this->resolveScope($request, true),
        ]);
    }

    /**
     * Get roles data for datatable (AJAX).
     */
    public function data(Request $request): JsonResponse
    {
        $scope = $this->resolveScope($request);
        $currentUser = Auth::guard('admin')->user();

        $query = Role::query()
            ->withCount(['users', 'permissions'])
            ->where('is_warehouse_role', $scope === 'warehouse');

        $this->applyListingFilters($query, $request);

        $total = $query->count();

        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'users_count', 'permissions_count', 'created_at', 'is_active'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $roles = $query->skip($offset)->take($perPage)->get();

        $data = $roles->map(function (Role $role) use ($scope, $currentUser) {
            $canManage = $currentUser->can('manage-role', $role);
            $canDelete = $currentUser->hasPermission('roles.delete')
                && $canManage
                && $role->canBeDeleted()
                && (int) $role->users_count === 0;

            $typeLabel = 'Custom';
            if ($role->is_system_role) {
                $typeLabel = 'System';
            } elseif ($role->is_warehouse_role) {
                $typeLabel = 'Warehouse';
            }

            return [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'users_count' => (int) $role->users_count,
                'permissions_count' => (int) $role->permissions_count,
                'is_active' => (bool) $role->is_active,
                'is_system_role' => (bool) $role->is_system_role,
                'is_warehouse_role' => (bool) $role->is_warehouse_role,
                'type_label' => $typeLabel,
                'status_label' => $role->is_active ? 'Active' : 'Inactive',
                'created_at' => $role->created_at?->format('d/m/Y, H:i:s') ?? '-',
                'can_edit' => $canManage,
                'can_delete' => $canDelete,
                'view_url' => route('admin.roles.show', ['role' => $role, 'scope' => $scope]),
                'edit_url' => route('admin.roles.edit', ['role' => $role, 'scope' => $scope]),
                'delete_url' => route('admin.roles.destroy', ['role' => $role, 'scope' => $scope]),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max((int) ceil($total / $perPage), 1),
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Export roles data.
     */
    public function export(Request $request)
    {
        $scope = $this->resolveScope($request);

        $query = Role::query()
            ->withCount(['users', 'permissions'])
            ->where('is_warehouse_role', $scope === 'warehouse');

        $this->applyListingFilters($query, $request);
        $query->latest();

        $rows = $query->get()->map(function (Role $role) {
            $typeLabel = 'Custom';
            if ($role->is_system_role) {
                $typeLabel = 'System';
            } elseif ($role->is_warehouse_role) {
                $typeLabel = 'Warehouse';
            }

            return [
                'Role Name' => $role->name,
                'Slug' => $role->slug,
                'Description' => $role->description ?: '-',
                'Users' => (int) $role->users_count,
                'Permissions' => (int) $role->permissions_count,
                'Type' => $typeLabel,
                'Status' => $role->is_active ? 'Active' : 'Inactive',
                'Created At' => $role->created_at?->format('Y-m-d H:i:s') ?? '-',
            ];
        })->values()->toArray();

        $format = $request->input('format', 'json');
        if ($format === 'excel') {
            $filename = 'roles_' . date('Y-m-d_His') . '.xlsx';
            return Excel::download(new UsersExport($rows), $filename);
        }

        if ($format === 'pdf') {
            $filename = 'roles_' . date('Y-m-d_His') . '.pdf';
            return GenericPdfExporter::download($rows, $filename, 'Roles List');
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(Request $request): View
    {
        $scope = $this->resolveScope($request);
        $permissions = $this->getPermissionsForScope($scope);

        return view('admin.roles.create', [
            'permissions' => $permissions,
            'roleScope' => $scope,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse|JsonResponse
    {
        $scope = $this->resolveScope($request);

        $role = Role::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'is_system_role' => false, // User-created roles are never system roles
            'is_warehouse_role' => $scope === 'warehouse',
        ]);

        // Attach selected permissions
        $permissionIds = $this->filterPermissionIdsForScope($request->input('permissions', []), $scope);
        if ($permissionIds !== []) {
            $role->syncPermissions($permissionIds);
        }

        $redirectUrl = route($scope === 'warehouse' ? 'admin.roles.warehouse.index' : 'admin.roles.index');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role created successfully.',
                'data' => [
                    'id' => $role->id,
                    'redirect_url' => $redirectUrl,
                ],
            ], 201);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role, Request $request): View
    {
        $role->load(['permissions', 'users']);

        return view('admin.roles.show', [
            'role' => $role,
            'roleScope' => $this->resolveScope($request, $role->is_warehouse_role),
        ]);
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role, Request $request): View
    {
        // Check authorization using the manage-role gate
        $this->authorize('manage-role', $role);

        $scope = $this->resolveScope($request, $role->is_warehouse_role);
        $permissions = $this->getPermissionsForScope($scope);
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
            'roleScope' => $scope,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse|JsonResponse
    {
        // Check authorization using the manage-role gate
        $this->authorize('manage-role', $role);
        $scope = $this->resolveScope($request, $role->is_warehouse_role);

        $role->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Sync permissions
        $role->syncPermissions($this->filterPermissionIdsForScope($request->input('permissions', []), $scope));

        // Clear permission cache for all users with this role
        foreach ($role->users as $user) {
            $user->flushPermissionCache();
        }

        $redirectUrl = route($scope === 'warehouse' ? 'admin.roles.warehouse.index' : 'admin.roles.index');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
                'data' => [
                    'id' => $role->id,
                    'redirect_url' => $redirectUrl,
                ],
            ]);
        }

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role, Request $request): RedirectResponse|JsonResponse
    {
        $scope = $this->resolveScope($request, $role->is_warehouse_role);

        // Check if role can be deleted (not a system role)
        if (!$role->canBeDeleted()) {
            $message = 'System roles cannot be deleted.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        // Check if role has users assigned
        if ($role->users()->count() > 0) {
            $message = 'Cannot delete a role that has users assigned to it. Please reassign or remove the users first.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        $role->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.',
            ]);
        }

        return redirect()
            ->route($scope === 'warehouse' ? 'admin.roles.warehouse.index' : 'admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Apply filters used by data and export endpoints.
     */
    private function applyListingFilters($query, Request $request): void
    {
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('permissions', function ($permissionQuery) use ($search) {
                        $permissionQuery->where('permissions.name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        $type = $request->input('type');
        if ($type === 'system') {
            $query->where('is_system_role', true);
        } elseif ($type === 'custom') {
            $query->where('is_system_role', false);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }

    /**
     * Resolve role scope from request.
     */
    private function resolveScope(Request $request, ?bool $defaultWarehouse = null): string
    {
        $scope = $request->query('scope', $request->input('scope'));
        if ($scope === 'warehouse' || $scope === 'system') {
            return $scope;
        }

        if ($request->routeIs('admin.roles.warehouse.*')) {
            return 'warehouse';
        }

        if ($defaultWarehouse === true) {
            return 'warehouse';
        }

        if ($defaultWarehouse === false) {
            return 'system';
        }

        return 'system';
    }

    /**
     * Get grouped permissions constrained by role scope.
     */
    private function getPermissionsForScope(string $scope): Collection
    {
        $permissions = Permission::getGroupedByModule()->toBase();

        if ($scope === 'system') {
            $permissions = $permissions->except(['warehouse_roles', 'warehouse']);
        } elseif ($scope === 'warehouse') {
            $permissions = $permissions->only(['warehouse']);
        }

        return $permissions;
    }

    /**
     * Filter submitted permission IDs by role scope.
     */
    private function filterPermissionIdsForScope(array $permissionIds, string $scope): array
    {
        if ($permissionIds === []) {
            return [];
        }

        $query = Permission::query()->whereIn('id', $permissionIds);

        if ($scope === 'system') {
            $query->whereNotIn('module', ['warehouse_roles', 'warehouse']);
        } elseif ($scope === 'warehouse') {
            $query->where('module', 'warehouse');
        }

        return $query->pluck('id')->map(static fn ($id) => (int) $id)->all();
    }
}
