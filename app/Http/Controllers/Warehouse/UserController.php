<?php

namespace App\Http\Controllers\Warehouse;

use App\Exports\UsersExport;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Warehouse\WarehousePortalService;
use App\Support\GenericPdfExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        $roles = $this->portalService->getAssignableWarehouseRoles($user);

        return view('warehouse.users.index', [
            'warehouse' => $warehouse,
            'roles' => $roles,
            'canCreateUsers' => $user->hasPermission('warehouse.users.create'),
            'canEditUsers' => $user->hasPermission('warehouse.users.edit'),
            'canDeactivateUsers' => $user->hasPermission('warehouse.users.deactivate'),
            'canAssignRoles' => $user->hasPermission('warehouse.users.assign_roles'),
            'canAssignRestrictedRoles' => $user->isHqUser(),
        ]);
    }

    public function show(User $user): View
    {
        $this->authorizePermission('warehouse.users.view');

        $actor = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($actor);
        $this->assertWarehouseScopedUser($user, $warehouse);

        $user->load(['roles.permissions', 'creator', 'warehouse']);
        $roles = $this->portalService->getAssignableWarehouseRoles($actor);

        // Reuse the existing rich admin profile UI with warehouse routes.
        return view('warehouse.users.show', [
            'admin' => $user,
            'canManage' => $this->canManageWarehouseUser($actor, $user),
            'roles' => $roles,
            'canAssignRestrictedRoles' => $actor->isHqUser(),
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
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($roleQuery) use ($search) {
                        $roleQuery->where('roles.name', 'like', "%{$search}%");
                    });
            });
        }

        if ($email = trim((string) $request->input('email'))) {
            $query->where('email', 'like', "%{$email}%");
        }

        if ($phone = trim((string) $request->input('phone'))) {
            $query->where('phone', 'like', "%{$phone}%");
        }

        if ($creator = trim((string) $request->input('created_by'))) {
            $query->whereHas('creator', fn ($q) => $q->where('name', 'like', "%{$creator}%"));
        }

        if ($roleId = $request->integer('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId));
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        if ($request->input('login_state') === 'logged_in') {
            $query->whereNotNull('last_login_at');
        } elseif ($request->input('login_state') === 'never') {
            $query->whereNull('last_login_at');
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($lastLoginFrom = $request->input('last_login_from')) {
            $query->whereDate('last_login_at', '>=', $lastLoginFrom);
        }

        if ($lastLoginTo = $request->input('last_login_to')) {
            $query->whereDate('last_login_at', '<=', $lastLoginTo);
        }

        $sortBy = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'email', 'phone', 'created_at', 'last_login_at'];
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
                'phone' => $user->phone,
                'avatar' => strtoupper(substr($user->name, 0, 1)),
                'roles' => $user->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values(),
                'is_active' => $user->is_active,
                'creator' => $user->creator?->name ?? 'System',
                'created_at' => $user->created_at?->format('d/m/Y, H:i:s'),
                'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Never',
                'can_manage' => $this->canManageWarehouseUser($actor, $user),
                'can_delete' => false,
                'is_self' => $actor->id === $user->id,
                'view_url' => route('warehouse.users.show', $user),
                'edit_url' => route('warehouse.users.update', $user) . '/edit',
                'toggle_url' => route('warehouse.users.toggle-active', $user),
                'delete_url' => null,
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

        $request->merge([
            'phone' => PhoneHelper::format((string) $request->input('phone')) ?? $request->input('phone'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!PhoneHelper::isValid((string) $value)) {
                    $fail('Please enter a valid Ghana phone number.');
                }
            }, 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = $this->resolveAssignableWarehouseRole((int) $validated['role_id'], $actor);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'warehouse_id' => $warehouse->id,
            'created_by_user_id' => $actor->id,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->syncRoles([$role->id]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => ['id' => $user->id],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission('warehouse.users.edit');

        $actor = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($actor);
        $this->assertWarehouseScopedUser($user, $warehouse);

        $request->merge([
            'phone' => PhoneHelper::format((string) $request->input('phone')) ?? $request->input('phone'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!PhoneHelper::isValid((string) $value)) {
                    $fail('Please enter a valid Ghana phone number.');
                }
            }, Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
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
            $requestedRoleId = (int) $validated['role_id'];
            $currentRoleId = (int) ($user->roles()->value('roles.id') ?? 0);

            if ($requestedRoleId !== $currentRoleId) {
                $role = $this->resolveAssignableWarehouseRole($requestedRoleId, $actor);
                $user->syncRoles([$role->id]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
        ]);
    }

    public function toggleActive(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorizePermission('warehouse.users.deactivate');

        $actor = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($actor);
        $this->assertWarehouseScopedUser($user, $warehouse);

        if ($actor->id === $user->id) {
            $message = 'You cannot change your own status.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $user->update(['is_active' => !$user->is_active]);
        $message = $user->is_active ? 'User activated successfully.' : 'User deactivated successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['id' => $user->id, 'is_active' => $user->is_active],
            ]);
        }

        return back()->with('success', $message);
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
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($roleQuery) use ($search) {
                        $roleQuery->where('roles.name', 'like', "%{$search}%");
                    });
            });
        }

        if ($email = trim((string) $request->input('email'))) {
            $query->where('email', 'like', "%{$email}%");
        }

        if ($phone = trim((string) $request->input('phone'))) {
            $query->where('phone', 'like', "%{$phone}%");
        }

        if ($creator = trim((string) $request->input('created_by'))) {
            $query->whereHas('creator', fn ($q) => $q->where('name', 'like', "%{$creator}%"));
        }

        if ($roleId = $request->integer('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId));
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        if ($request->input('login_state') === 'logged_in') {
            $query->whereNotNull('last_login_at');
        } elseif ($request->input('login_state') === 'never') {
            $query->whereNull('last_login_at');
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($lastLoginFrom = $request->input('last_login_from')) {
            $query->whereDate('last_login_at', '>=', $lastLoginFrom);
        }

        if ($lastLoginTo = $request->input('last_login_to')) {
            $query->whereDate('last_login_at', '<=', $lastLoginTo);
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
            $filename = 'users_' . date('Y-m-d_His') . '.xlsx';
            return Excel::download(new UsersExport($rows), $filename);
        }

        if ($format === 'pdf') {
            $filename = 'users_' . date('Y-m-d_His') . '.pdf';
            return GenericPdfExporter::download($rows, $filename, 'Users');
        }

        return response()->json(['data' => $rows]);
    }

    public function auditLogsData(Request $request, User $user)
    {
        $this->authorizePermission('warehouse.users.view');

        $actor = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($actor);
        $this->assertWarehouseScopedUser($user, $warehouse);

        $query = AdminAuditLog::query()
            ->with(['warehouse:id,name'])
            ->where('user_id', $user->id)
            ->where('scope', 'warehouse');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('action_type', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['created_at', 'action', 'action_type', 'scope', 'status_code', 'duration_ms'];

        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $format = $request->input('format');
        if ($format) {
            $rows = $query->get()->map(function ($log) {
                return [
                    'Date' => $log->created_at?->format('Y-m-d H:i:s') ?? '-',
                    'Scope' => $log->scope ?? 'warehouse',
                    'Type' => $log->action_type ? Str::of($log->action_type)->replace('_', ' ')->title()->toString() : '-',
                    'Action' => $this->humanizeAction($log->action, $log->action_type),
                    'Description' => $this->humanizeDescription($log),
                    'Method' => $log->method ?: '-',
                    'URL' => $log->url ? (parse_url($log->url, PHP_URL_PATH) ?: $log->url) : '-',
                    'IP Address' => $log->ip_address ?: '-',
                    'HTTP Status' => $log->status_code ?: '-',
                    'Duration (ms)' => $log->duration_ms ?? '-',
                ];
            })->values()->toArray();

            if ($format === 'excel') {
                $filename = 'warehouse_user_activity_logs_' . date('Y-m-d_His') . '.xlsx';
                return Excel::download(new UsersExport($rows), $filename);
            }

            if ($format === 'pdf') {
                $filename = 'warehouse_user_activity_logs_' . date('Y-m-d_His') . '.pdf';
                return GenericPdfExporter::download($rows, $filename, 'Warehouse User Activity Logs');
            }

            return response()->json(['data' => $rows]);
        }

        $total = $query->count();
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $logs = $query->skip($offset)->take($perPage)->get();

        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'created_at' => $log->created_at?->format('Y-m-d H:i:s') ?? '-',
                'scope' => $log->scope ?? 'warehouse',
                'action_type' => $log->action_type ? Str::of($log->action_type)->replace('_', ' ')->title()->toString() : '-',
                'action' => $this->humanizeAction($log->action, $log->action_type),
                'description' => $this->humanizeDescription($log),
                'method' => $log->method ?: '-',
                'url' => $log->url ? (parse_url($log->url, PHP_URL_PATH) ?: $log->url) : '-',
                'ip_address' => $log->ip_address ?: '-',
                'status_code' => $log->status_code ?: '-',
                'duration_ms' => $log->duration_ms ?? '-',
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

    private function resolveAssignableWarehouseRole(int $roleId, ?User $actor = null): Role
    {
        $query = Role::query()
            ->active()
            ->warehouseRoles()
            ->whereKey($roleId);

        if (!$actor?->isHqUser()) {
            $query->where('is_assignable_by_warehouse_manager', true);
        }

        return $query->firstOrFail();
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

    private function humanizeAction(?string $routeName, ?string $actionType): string
    {
        if (!$routeName) {
            return $actionType ? Str::of($actionType)->replace('_', ' ')->title()->toString() : '-';
        }

        $segments = explode('.', preg_replace('/^warehouse\./', '', $routeName));
        $resource = isset($segments[0]) ? Str::of($segments[0])->replace('_', ' ')->title()->toString() : 'Warehouse';
        $action = isset($segments[1]) ? Str::of($segments[1])->replace('-', ' ')->replace('_', ' ')->title()->toString() : null;

        if ($action) {
            return "{$action} {$resource}";
        }

        return Str::of($routeName)->replace('.', ' ')->replace('_', ' ')->title()->toString();
    }

    private function humanizeDescription($log): string
    {
        if ($log->description) {
            return $log->description;
        }

        if ($log->route_name) {
            return 'Route: ' . $log->route_name;
        }

        return '-';
    }
}
