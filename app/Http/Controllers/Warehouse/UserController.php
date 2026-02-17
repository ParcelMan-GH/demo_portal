<?php

namespace App\Http\Controllers\Warehouse;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Warehouse\WarehousePortalService;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function __construct(private WarehousePortalService $portalService)
    {
    }

    public function index(): View
    {
        $this->authorizePermission('warehouse.users.view');

        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);
        $roles = $this->portalService->getAssignableWarehouseRoles();

        return view('warehouse.users.index', [
            'warehouse' => $warehouse,
            'roles' => $roles,
            'canCreateUsers' => $user->hasPermission('warehouse.users.create'),
            'canEditUsers' => $user->hasPermission('warehouse.users.edit'),
            'canDeactivateUsers' => $user->hasPermission('warehouse.users.deactivate'),
            'canAssignRoles' => $user->hasPermission('warehouse.users.assign_roles'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.users.view');

        $actor = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($actor);
        $query = $this->portalService->warehouseUsersQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleId = $request->integer('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId));
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        $sortBy = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'email', 'created_at', 'last_login_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $users = $query->skip($offset)->take($perPage)->get();

        $data = $users->map(function (User $user) use ($actor) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values(),
                'is_active' => $user->is_active,
                'created_at' => $user->created_at?->format('d/m/Y, H:i:s'),
                'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Never',
                'can_manage' => $this->canManageWarehouseUser($actor, $user),
                'is_self' => $actor->id === $user->id,
                'update_url' => route('warehouse.users.update', $user),
                'toggle_url' => route('warehouse.users.toggle-active', $user),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.users.create');

        $actor = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($actor);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = $this->resolveAssignableWarehouseRole((int) $validated['role_id']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'warehouse_id' => $warehouse->id,
            'created_by_user_id' => $actor->id,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->syncRoles([$role->id]);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse user created successfully.',
            'data' => ['id' => $user->id],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission('warehouse.users.edit');

        $actor = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($actor);
        $this->assertWarehouseScopedUser($user, $warehouse);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (array_key_exists('is_active', $validated)) {
            if ($actor->id === $user->id && !$validated['is_active']) {
                return response()->json([
                    'message' => 'You cannot deactivate your own account.',
                ], 422);
            }
            $payload['is_active'] = $validated['is_active'];
        }

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        if (!empty($validated['role_id']) && $actor->hasPermission('warehouse.users.assign_roles')) {
            $role = $this->resolveAssignableWarehouseRole((int) $validated['role_id']);
            $user->syncRoles([$role->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Warehouse user updated successfully.',
        ]);
    }

    public function toggleActive(User $user): JsonResponse
    {
        $this->authorizePermission('warehouse.users.deactivate');

        $actor = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($actor);
        $this->assertWarehouseScopedUser($user, $warehouse);

        if ($actor->id === $user->id) {
            return response()->json([
                'message' => 'You cannot change your own status.',
            ], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'User activated successfully.' : 'User deactivated successfully.',
            'data' => ['id' => $user->id, 'is_active' => $user->is_active],
        ]);
    }

    public function export(Request $request)
    {
        $this->authorizePermission('warehouse.users.view');

        $actor = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($actor);
        $query = $this->portalService->warehouseUsersQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleId = $request->integer('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId));
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        $rows = $query->latest()->get()->map(function (User $user) {
            return [
                'Name' => $user->name,
                'Email' => $user->email,
                'Role' => $user->roles->first()?->name ?? '-',
                'Status' => $user->is_active ? 'Active' : 'Inactive',
                'Created At' => $user->created_at?->format('Y-m-d H:i:s'),
                'Last Login' => $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
            ];
        })->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            $filename = 'warehouse_users_' . date('Y-m-d_His') . '.xlsx';
            return Excel::download(new UsersExport($rows), $filename);
        }

        if ($format === 'pdf') {
            $filename = 'warehouse_users_' . date('Y-m-d_His') . '.pdf';
            return GenericPdfExporter::download($rows, $filename, 'Warehouse Users');
        }

        return response()->json(['data' => $rows]);
    }

    private function resolveAssignableWarehouseRole(int $roleId): Role
    {
        return Role::query()
            ->active()
            ->warehouseRoles()
            ->where('is_assignable_by_warehouse_manager', true)
            ->whereKey($roleId)
            ->firstOrFail();
    }

    private function assertWarehouseScopedUser(User $target, \App\Models\Warehouse $warehouse): void
    {
        if ((int) $target->warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function canManageWarehouseUser(User $actor, User $target): bool
    {
        if ((int) $actor->warehouse_id !== (int) $target->warehouse_id) {
            return false;
        }

        $hasManagePermission = $actor->hasAnyPermission([
            'warehouse.users.edit',
            'warehouse.users.deactivate',
            'warehouse.users.assign_roles',
        ]);

        if (!$hasManagePermission) {
            return false;
        }

        if ($actor->id === $target->id) {
            return $actor->hasPermission('warehouse.users.edit');
        }

        return true;
    }
}
