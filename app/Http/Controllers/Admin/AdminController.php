<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\AdminAuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): View
    {
        $roles = Role::active()
            ->where('is_warehouse_role', false)
            ->orderBy('name')
            ->get();
        return view('admin.admins.index', compact('roles'));
    }

    /**
     * Get users data for datatable (AJAX).
     */
    public function data(Request $request): JsonResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Base query
        if ($currentUser->isSuperAdmin()) {
            $query = User::whereNull('warehouse_id')
                ->with(['creator', 'roles']);
        } else {
            $query = User::where('created_by_user_id', $currentUser->id)
                ->whereNull('warehouse_id')
                ->with(['creator', 'roles']);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('roles', function ($roleQuery) use ($search) {
                      $roleQuery->where('roles.name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by role
        if ($roleId = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        // Filter by status
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        // Filter by date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Get total before pagination
        $total = $query->count();

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['name', 'email', 'created_at', 'last_login_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 10), 100);
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $admins = $query->skip($offset)->take($perPage)->get();

        // Transform data
        $data = $admins->map(function ($admin) use ($currentUser) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'avatar' => strtoupper(substr($admin->name, 0, 1)),
                'roles' => $admin->roles->map(fn($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                ]),
                'is_active' => $admin->is_active,
                'creator' => $admin->creator?->name ?? 'System',
                'created_at' => $admin->created_at->format('d/m/Y, H:i:s'),
                'last_login_at' => $admin->last_login_at?->diffForHumans() ?? 'Never',
                'can_manage' => $currentUser->canManage($admin),
                'can_delete' => $currentUser->hasPermission('users.delete') && $currentUser->canManage($admin) && $admin->id !== $currentUser->id,
                'is_self' => $admin->id === $currentUser->id,
                'view_url' => route('admin.admins.show', $admin),
                'edit_url' => route('admin.admins.edit', $admin),
                'toggle_url' => route('admin.admins.toggle-active', $admin),
                'delete_url' => route('admin.admins.destroy', $admin),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Export users data.
     */
    public function export(Request $request)
    {
        $currentUser = Auth::guard('admin')->user();

        if ($currentUser->isSuperAdmin()) {
            $query = User::whereNull('warehouse_id')
                ->with(['creator', 'roles']);
        } else {
            $query = User::where('created_by_user_id', $currentUser->id)
                ->whereNull('warehouse_id')
                ->with(['creator', 'roles']);
        }

        // Apply same filters as data method
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('roles', function ($roleQuery) use ($search) {
                      $roleQuery->where('roles.name', 'like', "%{$search}%");
                  });
            });
        }

        if ($roleId = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        $admins = $query->latest()->get();

        $data = $admins->map(function ($admin) {
            return [
                'Name' => $admin->name,
                'Email' => $admin->email,
                'Roles' => $admin->roles->pluck('name')->implode(', '),
                'Status' => $admin->is_active ? 'Active' : 'Inactive',
                'Created By' => $admin->creator?->name ?? 'System',
                'Created At' => $admin->created_at->format('Y-m-d H:i:s'),
                'Last Login' => $admin->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
            ];
        });

        $format = $request->input('format', 'json');

        $rows = $data->values()->toArray();

        if ($format === 'excel') {
            return $this->exportExcel($rows);
        }

        if ($format === 'pdf') {
            return $this->exportPDF($rows);
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * Export users to Excel format.
     */
    private function exportExcel(array $rows)
    {
        $filename = 'users_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new UsersExport($rows), $filename);
    }

    /**
     * Export users to PDF format.
     */
    private function exportPDF(array $rows)
    {
        $filename = 'users_' . date('Y-m-d_His') . '.pdf';
        return GenericPdfExporter::download($rows, $filename, 'Users List');
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();

        if (!$currentUser->hasPermission('users.create')) {
            return redirect()->route('admin.admins.index')
                ->with('error', 'You do not have permission to create users.');
        }

        $roles = Role::active()->get();
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $selectedWarehouseId = $request->integer('warehouse_id') ?: null;
        if ($selectedWarehouseId && !$warehouses->contains('id', $selectedWarehouseId)) {
            $selectedWarehouseId = null;
        }

        return view('admin.admins.create', compact('roles', 'warehouses', 'selectedWarehouseId'));
    }

    /**
     * Store a newly created user.
     */
    public function store(CreateAdminRequest $request): RedirectResponse|JsonResponse
    {
        $currentUser = Auth::guard('admin')->user();

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->boolean('is_active', true),
            'created_by_user_id' => $currentUser->id,
            'warehouse_id' => $request->input('warehouse_id'),
        ]);

        $roleId = $this->resolveRequestedRoleId($request);
        if ($roleId) {
            $admin->syncRoles([$roleId]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'user' => $admin,
            ]);
        }

        return redirect()->route('admin.admins.show', $admin)
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $admin): View|RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Check if can view this user
        if (!$currentUser->isSuperAdmin() && $admin->created_by_user_id !== $currentUser->id && $admin->id !== $currentUser->id) {
            return redirect()->route('admin.admins.index')
                ->with('error', 'You do not have permission to view this user.');
        }

        $admin->load(['roles.permissions', 'creator', 'warehouse']);

        return view('admin.admins.show', compact('admin'));
    }

    /**
     * Get audit logs data for a specific user (AJAX datatable).
     */
    public function auditLogsData(Request $request, User $admin): JsonResponse
    {
        $currentUser = Auth::guard('admin')->user();

        if (!$currentUser->isSuperAdmin() && $admin->created_by_user_id !== $currentUser->id && $admin->id !== $currentUser->id) {
            return response()->json(['data' => [], 'meta' => ['total' => 0, 'per_page' => 15, 'current_page' => 1, 'last_page' => 1, 'from' => 0, 'to' => 0]]);
        }

        $query = AdminAuditLog::query()
            ->with(['warehouse:id,name'])
            ->where('user_id', $admin->id);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('action_type', 'like', "%{$search}%")
                  ->orWhere('route_name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Filter by scope
        if ($scope = $request->input('scope')) {
            $query->where('scope', $scope);
        }

        // Filter by action type
        if ($actionType = $request->input('action_type')) {
            $query->where('action_type', $actionType);
        }

        // Filter by date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $total = $query->count();

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['created_at', 'action', 'action_type', 'scope', 'status_code', 'duration_ms'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $logs = $query->skip($offset)->take($perPage)->get();

        $data = $logs->map(fn($log) => [
            'id' => $log->id,
            'created_at' => $log->created_at?->format('Y-m-d H:i:s') ?? '-',
            'scope' => $log->scope ?? 'system',
            'action_type' => $log->action_type ? \Str::of($log->action_type)->replace('_', ' ')->title()->toString() : '-',
            'action' => $this->humanizeAction($log->action, $log->action_type),
            'description' => $this->humanizeDescription($log),
            'method' => $log->method ?: '-',
            'url' => $log->url ? parse_url($log->url, PHP_URL_PATH) : '-',
            'ip_address' => $log->ip_address ?: '-',
            'status_code' => $log->status_code ?: '-',
            'duration_ms' => $log->duration_ms ?? '-',
        ]);

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

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $admin): View|RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Check if can edit this user
        if (!$currentUser->canManage($admin)) {
            return redirect()->route('admin.admins.index')
                ->with('error', 'You do not have permission to edit this user.');
        }

        $roles = Role::active()->get();
        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('admin.admins.edit', compact('admin', 'roles', 'warehouses'));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateAdminRequest $request, User $admin): RedirectResponse|JsonResponse
    {
        $data = $request->only(['name', 'email', 'is_active', 'warehouse_id']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        // Sync roles if provided and if user is not updating themselves
        if ($admin->id !== Auth::guard('admin')->id()) {
            $roleId = $this->resolveRequestedRoleId($request);
            if ($roleId) {
                $admin->syncRoles([$roleId]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'user' => $admin,
            ]);
        }

        return redirect()->route('admin.admins.show', $admin)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleActive(Request $request, User $admin): RedirectResponse|JsonResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Cannot toggle your own status
        if ($admin->id === $currentUser->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own active status.',
                ], 422);
            }
            return back()->with('error', 'You cannot change your own active status.');
        }

        // Check permission
        if (!$currentUser->canManage($admin)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to modify this user.',
                ], 403);
            }
            return back()->with('error', 'You do not have permission to modify this user.');
        }

        $admin->update(['is_active' => !$admin->is_active]);

        $status = $admin->is_active ? 'activated' : 'deactivated';
        $message = "User {$status} successfully.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $admin->id,
                    'is_active' => $admin->is_active,
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $admin): RedirectResponse|JsonResponse
    {
        $currentUser = Auth::guard('admin')->user();

        if ($admin->id === $currentUser->id) {
            $message = 'You cannot delete your own account.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        if (!$currentUser->hasPermission('users.delete')) {
            $message = 'You do not have permission to delete users.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return back()->with('error', $message);
        }

        if (!$currentUser->canManage($admin)) {
            $message = 'You do not have permission to delete this user.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return back()->with('error', $message);
        }

        $admin->delete();

        $message = 'User deleted successfully.';
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.admins.index')->with('success', $message);
    }

    /**
     * Assign roles to the specified user.
     */
    public function assignRoles(Request $request, User $admin): RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Check permission
        if (!$currentUser->hasPermission('roles.assign')) {
            return back()->with('error', 'You do not have permission to assign roles.');
        }

        $validated = $request->validate([
            'role_id' => ['nullable', 'exists:roles,id'],
            'roles' => ['nullable', 'array', 'max:1'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $roleId = $validated['role_id'] ?? null;
        if (!$roleId && !empty($validated['roles'])) {
            $roleId = (int) $validated['roles'][0];
        }

        if (!$roleId) {
            return back()->with('error', 'A single role is required.');
        }

        $admin->syncRoles([(int) $roleId]);

        return back()->with('success', 'Role assigned successfully.');
    }

    /**
     * Convert a route name like "admin.admins.show" into a human-readable label like "Viewed User".
     */
    private function humanizeAction(?string $routeName, ?string $actionType): string
    {
        if (!$routeName) {
            return $actionType ? \Str::of($actionType)->replace('_', ' ')->title()->toString() : '-';
        }

        // Special-case auth actions
        $specialActions = [
            'admin.login' => 'Logged In',
            'admin.logout' => 'Logged Out',
            'admin.dashboard' => 'Viewed Dashboard',
        ];

        if (isset($specialActions[$routeName])) {
            return $specialActions[$routeName];
        }

        // Strip the "admin." prefix
        $stripped = preg_replace('/^admin\./', '', $routeName);
        $segments = explode('.', $stripped);

        // Resource label map
        $resourceLabels = [
            'admins' => 'User',
            'roles' => 'Role',
            'vendors' => 'Vendor',
            'shipments' => 'Shipment',
            'invoices' => 'Invoice',
            'drivers' => 'Driver',
            'warehouses' => 'Warehouse',
            'assignments' => 'Pickup Assignment',
            'settings' => 'Settings',
        ];

        // Action verb map
        $actionVerbs = [
            'index' => 'Listed',
            'show' => 'Viewed',
            'show.json' => 'Loaded',
            'create' => 'Opened Create Form',
            'store' => 'Created',
            'edit' => 'Opened Edit Form',
            'update' => 'Updated',
            'destroy' => 'Deleted',
            'data' => 'Loaded',
            'export' => 'Exported',
            'toggle-active' => 'Toggled Status of',
            'assign-roles' => 'Assigned Role to',
            'audit-logs-data' => 'Loaded Audit Logs for',
            'shipments' => 'Viewed Shipments of',
            'activity-logs' => 'Viewed Activity Logs of',
            'otp-logs' => 'Viewed OTP Logs of',
            'assignments' => 'Viewed Assignments of',
            'items' => 'Viewed Items of',
            'tracking' => 'Viewed Tracking of',
            'send' => 'Sent',
            'cancel' => 'Cancelled',
            'assign' => 'Assigned Driver to',
            'receive' => 'Received',
            'regions' => 'Loaded Regions',
            'districts' => 'Loaded Districts',
            'users.data' => 'Loaded Users of',
            'users.export' => 'Exported Users of',
            'save' => 'Saved',
            'upload' => 'Uploaded File to',
            'logs.data' => 'Loaded Logs',
            'logs.detail' => 'Viewed Log Detail',
            'logs.clear' => 'Cleared Logs',
            'logs.export' => 'Exported Logs',
            'email-logs.data' => 'Loaded Email Logs',
            'sms-logs.data' => 'Loaded SMS Logs',
            'otp-logs.data' => 'Loaded OTP Logs',
            'admin-audit-logs.data' => 'Loaded Admin Audit Logs',
            'admin-audit-logs.export' => 'Exported Admin Audit Logs',
            'test-email' => 'Sent Test Email',
            'test-sms' => 'Sent Test SMS',
            'clear-cache' => 'Cleared Cache',
        ];

        $resource = $segments[0] ?? '';
        $action = implode('.', array_slice($segments, 1));
        $resourceLabel = $resourceLabels[$resource] ?? \Str::of($resource)->replace('-', ' ')->title()->singular()->toString();

        // Settings sub-actions don't need a resource suffix
        if ($resource === 'settings' && isset($actionVerbs[$action])) {
            return $actionVerbs[$action];
        }

        if (isset($actionVerbs[$action])) {
            $verb = $actionVerbs[$action];
            // Verbs ending with "of" or "to" or "for" need the resource after
            if (preg_match('/(of|to|for)$/', $verb)) {
                return "{$verb} {$resourceLabel}";
            }
            return "{$verb} {$resourceLabel}";
        }

        // Fallback: make the action readable
        return \Str::of($action ?: $resource)->replace(['.', '-', '_'], ' ')->title()->toString();
    }

    /**
     * Build a human-readable description from an audit log entry.
     */
    private function humanizeDescription(AdminAuditLog $log): string
    {
        $parts = [];

        if ($log->method) {
            $parts[] = $log->method;
        }

        if ($log->url) {
            $path = parse_url($log->url, PHP_URL_PATH);
            if ($path) {
                $parts[] = $path;
            }
        }

        if ($log->status_code) {
            $parts[] = "→ HTTP {$log->status_code}";
        }

        if ($log->duration_ms !== null) {
            $parts[] = "({$log->duration_ms}ms)";
        }

        return implode(' ', $parts) ?: '-';
    }

    private function resolveRequestedRoleId(Request $request): ?int
    {
        if ($request->filled('role_id')) {
            return (int) $request->input('role_id');
        }

        $roles = array_values(array_filter((array) $request->input('roles', [])));
        if (empty($roles)) {
            return null;
        }

        return (int) $roles[0];
    }
}
