<?php

namespace App\Http\Controllers\Warehouse;

use App\Exports\UsersExport;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunStop;
use App\Models\PackageContactAttempt;
use App\Models\PackageContactTask;
use App\Models\RecipientPaymentCallAttempt;
use App\Models\RecipientPaymentSession;
use App\Models\RecipientPaymentSessionEntry;
use App\Models\RecipientPaymentTask;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\SortBatch;
use App\Models\SortBatchItem;
use App\Models\TransportManifest;
use App\Models\TransportManifestReceiptLabelScan;
use App\Models\User;
use App\Models\VendorPayout;
use App\Models\Warehouse;
use App\Models\WarehouseCapability;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemPhoto;
use App\Services\Warehouse\WarehousePortalService;
use App\Support\GenericPdfExporter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        $isHqUser = $user->isHqUser();

        return view('warehouse.users.index', [
            'warehouse' => $warehouse,
            'roles' => $roles,
            'warehouses' => $isHqUser
                ? Warehouse::query()->where('is_active', true)->orderByDesc('is_hq')->orderBy('name')->get(['id', 'name', 'code'])
                : collect(),
            'isHqUser' => $isHqUser,
            'canCreateUsers' => $user->hasPermission('warehouse.users.create'),
            'canEditUsers' => $user->hasPermission('warehouse.users.edit'),
            'canDeactivateUsers' => $user->hasPermission('warehouse.users.deactivate'),
            'canAssignRoles' => $user->hasPermission('warehouse.users.assign_roles'),
            'canImpersonateUsers' => $isHqUser && $user->hasPermission('warehouse.users.impersonate'),
            'canAssignRestrictedRoles' => $user->isHqUser(),
        ]);
    }

    public function show(User $user): View
    {
        $this->authorizePermission('warehouse.users.view');

        $actor = Auth::guard('admin')->user();
        $this->assertCanAccessUser($actor, $user);

        $user->load(['roles.permissions', 'creator', 'warehouse']);
        $roles = $this->portalService->getAssignableWarehouseRoles($actor);
        $isHqUser = $actor->isHqUser();
        $tabCounts = $this->userActivityTabCounts($user);

        return view('warehouse.users.show', [
            'admin' => $user,
            'canManage' => $this->canManageWarehouseUser($actor, $user),
            'roles' => $roles,
            'warehouses' => $isHqUser
                ? Warehouse::query()->where('is_active', true)->orderByDesc('is_hq')->orderBy('name')->get(['id', 'name', 'code'])
                : collect(),
            'isHqUser' => $isHqUser,
            'canAssignRestrictedRoles' => $isHqUser,
            'canImpersonate' => $this->canImpersonateUser($actor, $user),
            'tabCounts' => $tabCounts,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.users.view');

        $actor = Auth::guard('admin')->user();
        $query = $this->usersQueryForActor($actor);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                        $warehouseQuery->where('warehouses.name', 'like', "%{$search}%")
                            ->orWhere('warehouses.code', 'like', "%{$search}%");
                    })
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

        if ($actor->isHqUser() && $warehouseId = $request->integer('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
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
                'phone_input' => PhoneHelper::toLocal((string) $user->phone) ?: $user->phone,
                'avatar' => strtoupper(substr($user->name, 0, 1)),
                'photo_url' => $user->photo_path ? Storage::disk('public')->url($user->photo_path) : null,
                'roles' => $user->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values(),
                'warehouse' => $user->warehouse ? [
                    'id' => $user->warehouse->id,
                    'name' => $user->warehouse->name,
                    'code' => $user->warehouse->code,
                ] : null,
                'is_active' => $user->is_active,
                'creator' => $user->creator?->name ?? 'System',
                'created_at' => $user->created_at?->format('d/m/Y, H:i:s'),
                'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Never',
                'can_manage' => $this->canManageWarehouseUser($actor, $user),
                'can_delete' => false,
                'can_impersonate' => $this->canImpersonateUser($actor, $user),
                'is_self' => $actor->id === $user->id,
                'view_url' => route('warehouse.users.show', $user),
                'edit_url' => route('warehouse.users.update', $user) . '/edit',
                'toggle_url' => route('warehouse.users.toggle-active', $user),
                'impersonate_url' => route('warehouse.users.impersonate', $user),
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

        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'digits:10', function ($attribute, $value, $fail) {
                $phone = PhoneHelper::format((string) $value);

                if (!str_starts_with((string) $value, '0') || !$phone || !PhoneHelper::hasValidPrefix((string) $value)) {
                    $fail('Please enter a valid 10-digit Ghana phone number.');
                    return;
                }

                if (User::query()->where('phone', $phone)->exists()) {
                    $fail('This phone number has already been taken.');
                }
            }],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'warehouse_id' => $actor->isHqUser()
                ? ['required', 'integer', Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true))]
                : ['nullable'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = $this->resolveAssignableWarehouseRole((int) $validated['role_id'], $actor);
        $warehouse = $actor->isHqUser()
            ? Warehouse::query()->whereKey($validated['warehouse_id'])->firstOrFail()
            : $this->portalService->resolveWarehouse($actor);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => PhoneHelper::format($validated['phone']),
            'photo_path' => $request->file('profile_photo')?->store('user-photos', 'public'),
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
        $this->assertCanAccessUser($actor, $user);

        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'digits:10', function ($attribute, $value, $fail) use ($user) {
                $phone = PhoneHelper::format((string) $value);

                if (!str_starts_with((string) $value, '0') || !$phone || !PhoneHelper::hasValidPrefix((string) $value)) {
                    $fail('Please enter a valid 10-digit Ghana phone number.');
                    return;
                }

                if (User::query()->where('phone', $phone)->whereKeyNot($user->id)->exists()) {
                    $fail('This phone number has already been taken.');
                }
            }],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'warehouse_id' => $actor->isHqUser()
                ? ['nullable', 'integer', Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('is_active', true))]
                : ['nullable'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => PhoneHelper::format($validated['phone']),
        ];

        if ($actor->isHqUser() && !empty($validated['warehouse_id'])) {
            if ($actor->id === $user->id && (int) $validated['warehouse_id'] !== (int) $user->warehouse_id) {
                return response()->json([
                    'message' => 'You cannot move your own account to another warehouse.',
                ], 422);
            }

            $payload['warehouse_id'] = (int) $validated['warehouse_id'];
        }

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

        if ($request->hasFile('profile_photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }

            $payload['photo_path'] = $request->file('profile_photo')->store('user-photos', 'public');
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
        $this->assertCanAccessUser($actor, $user);

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
        $query = $this->usersQueryForActor($actor);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                        $warehouseQuery->where('warehouses.name', 'like', "%{$search}%")
                            ->orWhere('warehouses.code', 'like', "%{$search}%");
                    })
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

        if ($actor->isHqUser() && $warehouseId = $request->integer('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
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
                'Warehouse' => $user->warehouse?->name ?? '-',
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
        $this->assertCanAccessUser($actor, $user);

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

    public function overviewData(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission('warehouse.users.view');

        $actor = Auth::guard('admin')->user();
        $this->assertCanAccessUser($actor, $user);

        $counts = $this->userActivityTabCounts($user);
        $recent = $this->auditRowsForModule($user, 'overview')->take(8)->values();

        return response()->json([
            'summary' => [
                'total_activity' => array_sum($counts),
                'orders' => $counts['orders'] ?? 0,
                'packages' => ($counts['incoming-packages'] ?? 0) + ($counts['warehouse-packages'] ?? 0),
                'security' => $counts['security-log'] ?? 0,
                'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
            ],
            'counts' => $counts,
            'recent' => $recent,
        ]);
    }

    public function ordersData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'orders');
    }

    public function incomingPackagesData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'incoming-packages');
    }

    public function warehousePackagesData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'warehouse-packages');
    }

    public function sortingData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'sorting');
    }

    public function recipientDeskData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'recipient-desk');
    }

    public function recipientPaymentsData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'recipient-payments');
    }

    public function transportManifestsData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'transport-manifests');
    }

    public function incomingManifestsData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'incoming-manifests');
    }

    public function deliveryRunsData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'delivery-runs');
    }

    public function pendingConfirmationsData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'pending-confirmations');
    }

    public function teamActionsData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'team-actions');
    }

    public function hqControlsData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'hq-controls');
    }

    public function securityLogData(Request $request, User $user): JsonResponse
    {
        return $this->moduleActivityData($request, $user, 'security-log');
    }

    private function moduleActivityData(Request $request, User $user, string $module): JsonResponse
    {
        $this->authorizePermission('warehouse.users.view');

        $actor = Auth::guard('admin')->user();
        $this->assertCanAccessUser($actor, $user);

        $rows = collect($this->directRowsForModule($user, $module)->values()->all())
            ->merge($this->auditRowsForModule($user, $module)->values()->all());

        if ($search = trim((string) $request->input('search'))) {
            $needle = Str::lower($search)->toString();
            $rows = $rows->filter(fn (array $row) => str_contains(Str::lower(implode(' ', array_filter([
                $row['module'] ?? '',
                $row['reference'] ?? '',
                $row['action'] ?? '',
                $row['status'] ?? '',
                $row['details'] ?? '',
                $row['warehouse'] ?? '',
            ])))->toString(), $needle));
        }

        if ($status = trim((string) $request->input('status'))) {
            $rows = $rows->filter(fn (array $row) => strcasecmp((string) ($row['status'] ?? ''), $status) === 0);
        }

        if ($action = trim((string) $request->input('action'))) {
            $needle = Str::lower($action)->toString();
            $rows = $rows->filter(fn (array $row) => str_contains(Str::lower((string) ($row['action'] ?? ''))->toString(), $needle));
        }

        if ($dateFrom = $request->input('date_from')) {
            $rows = $rows->filter(fn (array $row) => ($row['date'] ?? '') >= $dateFrom . ' 00:00:00');
        }

        if ($dateTo = $request->input('date_to')) {
            $rows = $rows->filter(fn (array $row) => ($row['date'] ?? '') <= $dateTo . ' 23:59:59');
        }

        $sort = $request->input('sort', 'date');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['date', 'module', 'reference', 'action', 'status', 'warehouse'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'date';
        $rows = $rows->sortBy(fn (array $row) => $row[$sort] ?? '', SORT_REGULAR, $direction === 'desc')->values();

        if ($format = $request->input('format')) {
            $exportRows = $rows->map(fn (array $row) => [
                'Date' => $row['date'] ?? '-',
                'Module' => $row['module'] ?? '-',
                'Reference' => $row['reference'] ?? '-',
                'Action' => $row['action'] ?? '-',
                'Status' => $row['status'] ?? '-',
                'Warehouse' => $row['warehouse'] ?? '-',
                'Details' => $row['details'] ?? '-',
            ])->values()->toArray();

            if ($format === 'excel') {
                return Excel::download(new UsersExport($exportRows), 'user_' . $module . '_' . date('Y-m-d_His') . '.xlsx');
            }

            if ($format === 'pdf') {
                return GenericPdfExporter::download($exportRows, 'user_' . $module . '_' . date('Y-m-d_His') . '.pdf', Str::of($module)->replace('-', ' ')->title()->toString());
            }

            return response()->json(['data' => $exportRows]);
        }

        $total = $rows->count();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        return response()->json([
            'data' => $rows->slice($offset, $perPage)->values(),
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

    private function userActivityTabCounts(User $user): array
    {
        $modules = [
            'orders',
            'incoming-packages',
            'warehouse-packages',
            'sorting',
            'recipient-desk',
            'recipient-payments',
            'transport-manifests',
            'incoming-manifests',
            'delivery-runs',
            'pending-confirmations',
            'team-actions',
            'hq-controls',
            'security-log',
        ];

        $counts = [];
        foreach ($modules as $module) {
            $counts[$module] = collect($this->directRowsForModule($user, $module)->values()->all())->count()
                + collect($this->auditRowsForModule($user, $module)->values()->all())->count();
        }

        return $counts;
    }

    private function directRowsForModule(User $user, string $module): Collection
    {
        return match ($module) {
            'orders' => $this->orderRows($user),
            'incoming-packages' => $this->incomingPackageRows($user),
            'warehouse-packages' => $this->warehousePackageRows($user),
            'sorting' => $this->sortingRows($user),
            'recipient-desk' => $this->recipientDeskRows($user),
            'recipient-payments' => $this->recipientPaymentRows($user),
            'transport-manifests' => $this->transportManifestRows($user, false),
            'incoming-manifests' => $this->transportManifestRows($user, true),
            'delivery-runs' => $this->deliveryRunRows($user),
            'pending-confirmations' => $this->pendingConfirmationRows($user),
            'team-actions' => $this->teamActionRows($user),
            'hq-controls' => $this->hqControlRows($user),
            default => collect(),
        };
    }

    private function auditRowsForModule(User $user, string $module): Collection
    {
        $query = AdminAuditLog::query()
            ->with('warehouse:id,name')
            ->where('user_id', $user->id);

        $keywords = $this->auditKeywordsForModule($module);
        if ($keywords !== ['*']) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('route_name', 'like', "%{$keyword}%")
                        ->orWhere('action', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('action_type', 'like', "%{$keyword}%");
                }
            });
        }

        return $query->latest('created_at')->limit(250)->get()
            ->map(fn (AdminAuditLog $log) => $this->makeActivityRow(
                'audit-' . $log->id,
                $module === 'overview' ? $this->labelForAuditLog($log) : $this->moduleLabel($module),
                $log->route_name ? Str::of($log->route_name)->afterLast('.')->replace('-', ' ')->replace('_', ' ')->title()->toString() : 'Audit',
                $log->created_at,
                $this->humanizeAction($log->route_name, $log->action_type),
                $this->statusFromAuditLog($log),
                $this->humanizeDescription($log),
                $log->warehouse?->name,
                null
            ));
    }

    private function auditKeywordsForModule(string $module): array
    {
        return match ($module) {
            'overview' => ['*'],
            'orders' => ['orders', 'shipments', 'walkin'],
            'incoming-packages' => ['receipts.pending', 'pickups.received', 'received-pickups', 'receipt'],
            'warehouse-packages' => ['packages', 'items.received', 'warehouse-packages', 'label', 'scan'],
            'sorting' => ['sorting', 'sort-batches', 'sort_batch'],
            'recipient-desk' => ['contacts', 'recipient desk', 'contact'],
            'recipient-payments' => ['recipient-payments', 'payment'],
            'transport-manifests' => ['manifests.transport', 'transport-manifests'],
            'incoming-manifests' => ['manifests.incoming', 'incoming-manifests'],
            'delivery-runs' => ['delivery-runs', 'deliveries.runs'],
            'pending-confirmations' => ['pending-confirmations', 'confirm-handoff'],
            'team-actions' => ['users', 'roles'],
            'hq-controls' => ['vendors', 'vendor-payouts', 'drivers', 'warehouses', 'marketing', 'settings', 'capabilities'],
            'security-log' => ['login', 'logout', 'impersonat', 'auth', 'session', 'otp'],
            default => [$module],
        };
    }

    private function orderRows(User $user): Collection
    {
        return Shipment::query()
            ->with(['vendor:id,name,business_name', 'pickupAssignment.warehouseReceipt:id,pickup_assignment_id,warehouse_id', 'pickupAssignment.warehouseReceipt.warehouse:id,name'])
            ->withCount('items')
            ->where('created_by_user_id', $user->id)
            ->latest('created_at')
            ->limit(250)
            ->get()
            ->map(fn (Shipment $shipment) => $this->makeActivityRow(
                'order-' . $shipment->id,
                'Orders',
                $shipment->shipment_number,
                $shipment->created_at,
                'Created Order',
                $this->modelValue($shipment->status),
                trim(collect([
                    $shipment->vendor?->business_name ?: $shipment->vendor?->name,
                    $shipment->items_count . ' package(s)',
                    $shipment->source ? Str::of($this->modelValue($shipment->source))->replace('_', ' ')->title()->toString() : null,
                ])->filter()->join(' · ')),
                $shipment->pickupAssignment?->warehouseReceipt?->warehouse?->name ?: $user->warehouse?->name,
                route('admin.orders.show', $shipment)
            ));
    }

    private function incomingPackageRows(User $user): Collection
    {
        $receipts = WarehouseReceipt::query()
            ->with(['warehouse:id,name', 'shipment:id,shipment_number'])
            ->where(function ($query) use ($user) {
                $query->where('started_by_user_id', $user->id)
                    ->orWhere('finalized_by_user_id', $user->id)
                    ->orWhere('approved_by_user_id', $user->id);
            })
            ->latest('updated_at')
            ->limit(150)
            ->get()
            ->map(function (WarehouseReceipt $receipt) use ($user) {
                $action = (int) $receipt->finalized_by_user_id === (int) $user->id
                    ? 'Finalized Receipt'
                    : ((int) $receipt->approved_by_user_id === (int) $user->id ? 'Approved Receipt' : 'Started Receipt');

                return $this->makeActivityRow(
                    'receipt-' . $receipt->id,
                    'Incoming Packages',
                    $receipt->shipment?->shipment_number ?: 'Receipt #' . $receipt->id,
                    $receipt->finalized_at ?: $receipt->started_at ?: $receipt->updated_at,
                    $action,
                    $receipt->status,
                    $receipt->notes ?: 'Package receipt activity',
                    $receipt->warehouse?->name,
                    null
                );
            });

        $items = WarehouseReceiptItem::query()
            ->with(['receipt.warehouse:id,name', 'receipt.shipment:id,shipment_number', 'shipmentItem:id,description,tracking_code'])
            ->where('received_by_user_id', $user->id)
            ->latest('received_at')
            ->limit(150)
            ->get()
            ->map(fn (WarehouseReceiptItem $item) => $this->makeActivityRow(
                'receipt-item-' . $item->id,
                'Incoming Packages',
                $item->shipmentItem?->tracking_code ?: $item->receipt?->shipment?->shipment_number ?: 'Package #' . $item->id,
                $item->received_at ?: $item->updated_at,
                'Received Package',
                $item->condition_status ?: $item->discrepancy_type,
                trim(collect([
                    $item->shipmentItem?->description,
                    'Received ' . (int) $item->received_quantity . ' of ' . (int) $item->expected_quantity,
                ])->filter()->join(' · ')),
                $item->receipt?->warehouse?->name,
                null
            ));

        return collect($receipts->values()->all())->merge($items->values()->all());
    }

    private function warehousePackageRows(User $user): Collection
    {
        return WarehouseReceiptItemPhoto::query()
            ->with(['receiptItem.receipt.warehouse:id,name', 'receiptItem.shipmentItem:id,description,tracking_code'])
            ->where('created_by_user_id', $user->id)
            ->latest('created_at')
            ->limit(250)
            ->get()
            ->map(fn (WarehouseReceiptItemPhoto $photo) => $this->makeActivityRow(
                'receipt-photo-' . $photo->id,
                'Warehouse Packages',
                $photo->receiptItem?->shipmentItem?->tracking_code ?: 'Photo #' . $photo->id,
                $photo->created_at,
                'Uploaded Package Photo',
                $photo->type ?: 'photo',
                $photo->receiptItem?->shipmentItem?->description ?: 'Receipt photo uploaded',
                $photo->receiptItem?->receipt?->warehouse?->name,
                null
            ));
    }

    private function sortingRows(User $user): Collection
    {
        $batches = SortBatch::query()
            ->with(['originWarehouse:id,name', 'destinationWarehouse:id,name'])
            ->where(function ($query) use ($user) {
                $query->where('created_by_user_id', $user->id)
                    ->orWhere('sealed_by_user_id', $user->id);
            })
            ->latest('updated_at')
            ->limit(150)
            ->get()
            ->map(fn (SortBatch $batch) => $this->makeActivityRow(
                'sort-batch-' . $batch->id,
                'Sorting',
                $batch->batch_number,
                $batch->sealed_at ?: $batch->created_at,
                (int) $batch->sealed_by_user_id === (int) $user->id ? 'Sealed Sort Batch' : 'Created Sort Batch',
                $batch->status,
                trim(collect([
                    Str::of($batch->dispatch_mode)->replace('_', ' ')->title()->toString(),
                    $batch->destinationWarehouse?->name ? 'To ' . $batch->destinationWarehouse->name : null,
                ])->filter()->join(' · ')),
                $batch->originWarehouse?->name,
                null
            ));

        $items = SortBatchItem::query()
            ->with(['sortBatch:id,batch_number,origin_warehouse_id', 'sortBatch.originWarehouse:id,name', 'warehouseReceiptItem.shipmentItem:id,tracking_code,description'])
            ->where('added_by_user_id', $user->id)
            ->latest('created_at')
            ->limit(150)
            ->get()
            ->map(fn (SortBatchItem $item) => $this->makeActivityRow(
                'sort-batch-item-' . $item->id,
                'Sorting',
                $item->sortBatch?->batch_number ?: 'Batch Item #' . $item->id,
                $item->created_at,
                'Added Package To Batch',
                $item->removed_at ? 'removed' : 'active',
                $item->warehouseReceiptItem?->shipmentItem?->description ?: $item->warehouseReceiptItem?->shipmentItem?->tracking_code,
                $item->sortBatch?->originWarehouse?->name,
                null
            ));

        return collect($batches->values()->all())->merge($items->values()->all());
    }

    private function recipientDeskRows(User $user): Collection
    {
        $tasks = PackageContactTask::query()
            ->with('warehouse:id,name')
            ->where('assigned_to_user_id', $user->id)
            ->latest('updated_at')
            ->limit(150)
            ->get()
            ->map(fn (PackageContactTask $task) => $this->makeActivityRow(
                'contact-task-' . $task->id,
                'Recipient Desk',
                $task->recipient_phone ?: 'Task #' . $task->id,
                $task->resolved_at ?: $task->assigned_at ?: $task->updated_at,
                'Handled Recipient Task',
                $task->status,
                trim(collect([$task->recipient_name, $task->delivery_town, $task->outcome])->filter()->join(' · ')),
                $task->warehouse?->name,
                route('warehouse.contacts.index')
            ));

        $attempts = PackageContactAttempt::query()
            ->with('task.warehouse:id,name')
            ->where('attempted_by_user_id', $user->id)
            ->latest('attempted_at')
            ->limit(150)
            ->get()
            ->map(fn (PackageContactAttempt $attempt) => $this->makeActivityRow(
                'contact-attempt-' . $attempt->id,
                'Recipient Desk',
                $attempt->task?->recipient_phone ?: 'Call #' . $attempt->id,
                $attempt->attempted_at,
                'Logged Call Attempt',
                $attempt->outcome,
                $attempt->notes ?: $attempt->task?->recipient_name,
                $attempt->task?->warehouse?->name,
                route('warehouse.contacts.index')
            ));

        return collect($tasks->values()->all())->merge($attempts->values()->all());
    }

    private function recipientPaymentRows(User $user): Collection
    {
        $sessions = RecipientPaymentSession::query()
            ->with(['warehouse:id,name', 'paymentWallet:id,name,phone_number'])
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('reviewed_by_user_id', $user->id);
            })
            ->latest('started_at')
            ->limit(120)
            ->get()
            ->map(fn (RecipientPaymentSession $session) => $this->makeActivityRow(
                'payment-session-' . $session->id,
                'Recipient Payments',
                $session->paymentWallet?->phone_number ?: 'Session #' . $session->id,
                $session->closed_at ?: $session->started_at ?: $session->created_at,
                (int) $session->reviewed_by_user_id === (int) $user->id ? 'Reviewed Payment Session' : 'Managed Payment Session',
                $session->status,
                'Expected: GHS ' . number_format((float) $session->expected_closing_balance, 2),
                $session->warehouse?->name,
                route('warehouse.recipient-payments.index', ['tab' => 'sessions'])
            ));

        $entries = RecipientPaymentSessionEntry::query()
            ->with('session.warehouse:id,name')
            ->where('recorded_by_user_id', $user->id)
            ->latest('created_at')
            ->limit(120)
            ->get()
            ->map(fn (RecipientPaymentSessionEntry $entry) => $this->makeActivityRow(
                'payment-entry-' . $entry->id,
                'Recipient Payments',
                $entry->reference ?: 'Entry #' . $entry->id,
                $entry->created_at,
                'Recorded Payment',
                $entry->entry_type,
                ($entry->currency ?: 'GHS') . ' ' . number_format((float) $entry->amount, 2),
                $entry->session?->warehouse?->name,
                route('warehouse.recipient-payments.index')
            ));

        $tasks = RecipientPaymentTask::query()
            ->with('warehouse:id,name')
            ->where(function ($query) use ($user) {
                $query->where('assigned_to_user_id', $user->id)
                    ->orWhere('override_by_user_id', $user->id);
            })
            ->latest('updated_at')
            ->limit(120)
            ->get()
            ->map(fn (RecipientPaymentTask $task) => $this->makeActivityRow(
                'payment-task-' . $task->id,
                'Recipient Payments',
                $task->recipient_phone ?: 'Task #' . $task->id,
                $task->paid_at ?: $task->assigned_at ?: $task->updated_at,
                (int) $task->override_by_user_id === (int) $user->id ? 'Overrode Payment Task' : 'Handled Payment Task',
                $task->status,
                trim(collect([$task->recipient_name, $task->delivery_town, $task->negotiated_amount ? 'GHS ' . number_format((float) $task->negotiated_amount, 2) : null])->filter()->join(' · ')),
                $task->warehouse?->name,
                route('warehouse.recipient-payments.index')
            ));

        $calls = RecipientPaymentCallAttempt::query()
            ->with('task.warehouse:id,name')
            ->where('attempted_by_user_id', $user->id)
            ->latest('attempted_at')
            ->limit(120)
            ->get()
            ->map(fn (RecipientPaymentCallAttempt $attempt) => $this->makeActivityRow(
                'payment-call-' . $attempt->id,
                'Recipient Payments',
                $attempt->task?->recipient_phone ?: 'Call #' . $attempt->id,
                $attempt->attempted_at,
                'Logged Payment Call',
                $attempt->outcome,
                $attempt->notes ?: $attempt->task?->recipient_name,
                $attempt->task?->warehouse?->name,
                route('warehouse.recipient-payments.index')
            ));

        return collect($sessions->values()->all())
            ->merge($entries->values()->all())
            ->merge($tasks->values()->all())
            ->merge($calls->values()->all());
    }

    private function transportManifestRows(User $user, bool $incoming): Collection
    {
        $manifests = TransportManifest::query()
            ->with(['originWarehouse:id,name', 'destinationWarehouse:id,name'])
            ->where($incoming ? 'received_by_user_id' : 'created_by_user_id', $user->id)
            ->latest($incoming ? 'received_at' : 'created_at')
            ->limit(180)
            ->get()
            ->map(fn (TransportManifest $manifest) => $this->makeActivityRow(
                ($incoming ? 'incoming-manifest-' : 'transport-manifest-') . $manifest->id,
                $incoming ? 'Incoming Manifests' : 'Transport Manifests',
                $manifest->manifest_number,
                $incoming ? ($manifest->received_at ?: $manifest->updated_at) : $manifest->created_at,
                $incoming ? 'Received Manifest' : 'Created Manifest',
                $manifest->status,
                trim(collect([
                    $manifest->originWarehouse?->name ? 'From ' . $manifest->originWarehouse->name : null,
                    $manifest->destinationWarehouse?->name ? 'To ' . $manifest->destinationWarehouse->name : null,
                ])->filter()->join(' · ')),
                $incoming ? $manifest->destinationWarehouse?->name : $manifest->originWarehouse?->name,
                $incoming
                    ? route('warehouse.manifests.incoming.show', $manifest)
                    : route('warehouse.manifests.transport.show', $manifest)
            ));

        if (!$incoming) {
            return $manifests;
        }

        $scans = TransportManifestReceiptLabelScan::query()
            ->with(['manifest:id,manifest_number,destination_warehouse_id', 'manifest.destinationWarehouse:id,name'])
            ->where('scanned_by_user_id', $user->id)
            ->latest('scanned_at')
            ->limit(120)
            ->get()
            ->map(fn (TransportManifestReceiptLabelScan $scan) => $this->makeActivityRow(
                'incoming-scan-' . $scan->id,
                'Incoming Manifests',
                $scan->barcode_value ?: $scan->manifest?->manifest_number ?: 'Scan #' . $scan->id,
                $scan->scanned_at,
                'Scanned Incoming Label',
                'scanned',
                $scan->manifest?->manifest_number,
                $scan->manifest?->destinationWarehouse?->name,
                $scan->manifest ? route('warehouse.manifests.incoming.show', $scan->manifest) : null
            ));

        return collect($manifests->values()->all())->merge($scans->values()->all());
    }

    private function deliveryRunRows(User $user): Collection
    {
        return DeliveryRun::query()
            ->with(['warehouse:id,name', 'assignedDriver:id,name'])
            ->withCount('stops')
            ->where('created_by_user_id', $user->id)
            ->latest('created_at')
            ->limit(250)
            ->get()
            ->map(fn (DeliveryRun $run) => $this->makeActivityRow(
                'delivery-run-' . $run->id,
                'Delivery Runs',
                $run->run_number,
                $run->created_at,
                'Created Delivery Run',
                $run->status,
                trim(collect([$run->assignedDriver?->name, $run->stops_count . ' stop(s)'])->filter()->join(' · ')),
                $run->warehouse?->name,
                route('warehouse.deliveries.runs.show', $run)
            ));
    }

    private function pendingConfirmationRows(User $user): Collection
    {
        return DeliveryRunStop::query()
            ->with('run.warehouse:id,name')
            ->where('confirmed_by_admin_id', $user->id)
            ->latest('confirmed_at')
            ->limit(250)
            ->get()
            ->map(fn (DeliveryRunStop $stop) => $this->makeActivityRow(
                'pending-confirmation-' . $stop->id,
                'Pending Confirmations',
                $stop->recipient_phone ?: 'Stop #' . $stop->id,
                $stop->confirmed_at ?: $stop->updated_at,
                'Confirmed Bus Handoff',
                $stop->status,
                trim(collect([$stop->recipient_name, $stop->town, $stop->confirmation_notes])->filter()->join(' · ')),
                $stop->run?->warehouse?->name,
                route('warehouse.deliveries.pending-confirmations')
            ));
    }

    private function teamActionRows(User $user): Collection
    {
        return User::query()
            ->with(['warehouse:id,name', 'roles:id,name'])
            ->where('created_by_user_id', $user->id)
            ->latest('created_at')
            ->limit(250)
            ->get()
            ->map(fn (User $createdUser) => $this->makeActivityRow(
                'created-user-' . $createdUser->id,
                'Team Actions',
                $createdUser->name,
                $createdUser->created_at,
                'Created User',
                $createdUser->is_active ? 'active' : 'inactive',
                $createdUser->roles->pluck('name')->join(', ') ?: 'No role assigned',
                $createdUser->warehouse?->name,
                route('warehouse.users.show', $createdUser)
            ));
    }

    private function hqControlRows(User $user): Collection
    {
        $payouts = VendorPayout::query()
            ->with('vendor:id,name,business_name')
            ->where('processed_by_admin_id', $user->id)
            ->latest('created_at')
            ->limit(120)
            ->get()
            ->map(fn (VendorPayout $payout) => $this->makeActivityRow(
                'vendor-payout-' . $payout->id,
                'HQ Controls',
                $payout->vendor?->business_name ?: $payout->vendor?->name ?: 'Payout #' . $payout->id,
                $payout->confirmed_at ?: $payout->sent_at ?: $payout->created_at,
                'Processed Commission Payout',
                $payout->status,
                ($payout->payment_method ? strtoupper($payout->payment_method) . ' · ' : '') . 'GHS ' . number_format((float) $payout->amount, 2),
                $user->warehouse?->name,
                route('admin.vendor-payouts.index')
            ));

        $capabilities = WarehouseCapability::query()
            ->with('warehouse:id,name')
            ->where('granted_by_user_id', $user->id)
            ->latest('updated_at')
            ->limit(120)
            ->get()
            ->map(fn (WarehouseCapability $capability) => $this->makeActivityRow(
                'capability-' . $capability->id,
                'HQ Controls',
                $capability->module,
                $capability->updated_at,
                'Updated Warehouse Capability',
                'active',
                Str::of($capability->scope)->replace('_', ' ')->title()->toString(),
                $capability->warehouse?->name,
                $capability->warehouse ? route('admin.warehouses.show', $capability->warehouse) : null
            ));

        return collect($payouts->values()->all())->merge($capabilities->values()->all());
    }

    private function makeActivityRow(
        string $id,
        string $module,
        ?string $reference,
        mixed $date,
        string $action,
        ?string $status,
        ?string $details,
        ?string $warehouse = null,
        ?string $viewUrl = null
    ): array {
        return [
            'id' => $id,
            'module' => $module,
            'reference' => $reference ?: '-',
            'date' => $this->formatActivityDate($date),
            'action' => $action,
            'status' => $status ? Str::of($status)->replace('_', ' ')->title()->toString() : '-',
            'details' => $details ?: '-',
            'warehouse' => $warehouse ?: '-',
            'view_url' => $viewUrl,
        ];
    }

    private function formatActivityDate(mixed $date): string
    {
        if (!$date) {
            return '';
        }

        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        return (string) $date;
    }

    private function modelValue(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return $value !== null ? (string) $value : null;
    }

    private function moduleLabel(string $module): string
    {
        return match ($module) {
            'incoming-packages' => 'Incoming Packages',
            'warehouse-packages' => 'Warehouse Packages',
            'recipient-desk' => 'Recipient Desk',
            'recipient-payments' => 'Recipient Payments',
            'transport-manifests' => 'Transport Manifests',
            'incoming-manifests' => 'Incoming Manifests',
            'delivery-runs' => 'Delivery Runs',
            'pending-confirmations' => 'Pending Confirmations',
            'team-actions' => 'Team Actions',
            'hq-controls' => 'HQ Controls',
            'security-log' => 'Security Log',
            default => Str::of($module)->replace('-', ' ')->title()->toString(),
        };
    }

    private function labelForAuditLog(AdminAuditLog $log): string
    {
        $route = (string) $log->route_name;

        foreach ([
            'orders' => 'Orders',
            'walkin' => 'Orders',
            'receipts' => 'Incoming Packages',
            'packages' => 'Warehouse Packages',
            'sorting' => 'Sorting',
            'contacts' => 'Recipient Desk',
            'recipient-payments' => 'Recipient Payments',
            'manifests.transport' => 'Transport Manifests',
            'manifests.incoming' => 'Incoming Manifests',
            'delivery-runs' => 'Delivery Runs',
            'pending-confirmations' => 'Pending Confirmations',
            'users' => 'Team Actions',
            'roles' => 'Team Actions',
            'vendors' => 'HQ Controls',
            'settings' => 'HQ Controls',
        ] as $needle => $label) {
            if (str_contains($route, $needle)) {
                return $label;
            }
        }

        return 'Activity';
    }

    private function statusFromAuditLog(AdminAuditLog $log): string
    {
        if ($log->status_code) {
            return (int) $log->status_code >= 400 ? 'Failed' : 'Successful';
        }

        return $log->action_type ? Str::of($log->action_type)->replace('_', ' ')->title()->toString() : 'Recorded';
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

    private function usersQueryForActor(User $actor): \Illuminate\Database\Eloquent\Builder
    {
        if ($actor->isHqUser()) {
            return User::query()
                ->with(['roles:id,name,slug,is_warehouse_role', 'creator:id,name', 'warehouse:id,name,code']);
        }

        $warehouse = $this->portalService->resolveWarehouse($actor);

        return $this->portalService->warehouseUsersQuery($warehouse);
    }

    private function assertCanAccessUser(User $actor, User $target): void
    {
        if ($actor->isHqUser()) {
            return;
        }

        $warehouse = $this->portalService->resolveWarehouse($actor);
        $this->assertWarehouseScopedUser($target, $warehouse);
    }

    private function assertWarehouseScopedUser(User $target, Warehouse $warehouse): void
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
        $hasManagePermission = $actor->hasAnyPermission([
            'warehouse.users.edit',
            'warehouse.users.deactivate',
            'warehouse.users.assign_roles',
        ]);

        if (!$hasManagePermission) {
            return false;
        }

        if ($actor->isHqUser()) {
            return true;
        }

        if ((int) $actor->warehouse_id !== (int) $target->warehouse_id) {
            return false;
        }

        if ($actor->id === $target->id) {
            return $actor->hasPermission('warehouse.users.edit');
        }

        return true;
    }

    private function canImpersonateUser(User $actor, User $target): bool
    {
        return $actor->isHqUser()
            && $actor->hasPermission('warehouse.users.impersonate')
            && $actor->id !== $target->id
            && (bool) $target->is_active
            && (int) ($target->warehouse_id ?? 0) > 0;
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
