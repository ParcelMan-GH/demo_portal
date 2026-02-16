<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:roles.view', only: ['index', 'warehouseIndex', 'show']),
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
        $roles = Role::with('permissions')
            ->withCount('users')
            ->where('is_warehouse_role', false)
            ->latest()
            ->paginate(20);

        return view('admin.roles.index', [
            'roles' => $roles,
            'roleScope' => $this->resolveScope($request),
        ]);
    }

    /**
     * Display a listing of warehouse roles.
     */
    public function warehouseIndex(Request $request): View
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->where('is_warehouse_role', true)
            ->latest()
            ->paginate(20);

        return view('admin.roles.index', [
            'roles' => $roles,
            'roleScope' => $this->resolveScope($request, true),
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(Request $request): View
    {
        $permissions = Permission::getGroupedByModule();

        return view('admin.roles.create', [
            'permissions' => $permissions,
            'roleScope' => $this->resolveScope($request),
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
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
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()
            ->route('admin.roles.show', ['role' => $role, 'scope' => $scope])
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

        $permissions = Permission::getGroupedByModule();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
            'roleScope' => $this->resolveScope($request, $role->is_warehouse_role),
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
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
        $role->syncPermissions($request->permissions ?? []);

        // Clear permission cache for all users with this role
        foreach ($role->users as $user) {
            $user->flushPermissionCache();
        }

        return redirect()
            ->route('admin.roles.show', ['role' => $role, 'scope' => $scope])
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role, Request $request): RedirectResponse
    {
        $scope = $this->resolveScope($request, $role->is_warehouse_role);

        // Check if role can be deleted (not a system role)
        if (!$role->canBeDeleted()) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        // Check if role has users assigned
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete a role that has users assigned to it. Please reassign or remove the users first.');
        }

        $role->delete();

        return redirect()
            ->route($scope === 'warehouse' ? 'admin.roles.warehouse.index' : 'admin.roles.index')
            ->with('success', 'Role deleted successfully.');
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
}
