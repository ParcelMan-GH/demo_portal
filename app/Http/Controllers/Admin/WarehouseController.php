<?php

namespace App\Http\Controllers\Admin;

use App\Exports\WarehousesExport;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Models\PickupAssignment;
use App\Models\PickupItemConfirmation;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\GenericPdfExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseController extends Controller
{
    /**
     * Display warehouses index page.
     */
    public function index()
    {
        $this->authorizePermission('warehouses.view');

        return view('admin.warehouses.index');
    }

    /**
     * Get warehouses data for DataTable.
     */
    public function data(Request $request)
    {
        $this->authorizePermission('warehouses.view');

        $query = Warehouse::with(['region', 'district']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->get('status') !== '') {
            $query->where('is_active', $request->get('status') === 'active');
        }

        // Date range filter
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'code', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $warehouses = $query->paginate($perPage);

        $canManage = Auth::guard('admin')->user()->hasPermission('warehouses.edit');
        $currentAdmin = Auth::guard('admin')->user();

        return response()->json([
            'data' => $warehouses->map(function ($warehouse) use ($canManage) {
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'address' => $warehouse->address,
                    'region' => $warehouse->region?->name,
                    'district' => $warehouse->district?->name,
                    'region_id' => $warehouse->region_id,
                    'district_id' => $warehouse->district_id,
                    'contact_phone' => $warehouse->contact_phone,
                    'contact_email' => $warehouse->contact_email,
                    'capacity' => $warehouse->capacity,
                    'is_active' => $warehouse->is_active,
                    'created_at' => $warehouse->created_at->format('Y-m-d H:i:s'),
                    'can_manage' => $canManage,
                ];
            }),
            'meta' => [
                'current_page' => $warehouses->currentPage(),
                'from' => $warehouses->firstItem() ?? 0,
                'to' => $warehouses->lastItem() ?? 0,
                'total' => $warehouses->total(),
                'last_page' => $warehouses->lastPage(),
            ],
        ]);
    }

    /**
     * Display warehouse detail page.
     */
    public function showPage(Warehouse $warehouse)
    {
        $this->authorizePermission('warehouses.view');

        $currentAdmin = Auth::guard('admin')->user();
        $canManage = $currentAdmin->hasPermission('warehouses.edit');

        $warehouseUsers = $warehouse->users()
            ->with('roles:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'warehouse_id', 'name', 'email', 'is_active', 'last_login_at', 'created_at']);

        $userRoles = Role::active()
            ->warehouseRoles()
            ->orderBy('name')
            ->get(['id', 'name']);

        $receivedPickups = PickupAssignment::query()
            ->with([
                'shipment:id,shipment_number',
                'driver:id,name,phone',
            ])
            ->where('received_warehouse_id', $warehouse->id)
            ->whereNotNull('received_at')
            ->latest('received_at')
            ->limit(100)
            ->get([
                'id',
                'shipment_id',
                'driver_id',
                'status',
                'assigned_at',
                'arrived_warehouse_at',
                'received_at',
                'receive_notes',
            ]);

        $pendingReceipts = PickupAssignment::query()
            ->with([
                'shipment:id,shipment_number',
                'driver:id,name,phone',
            ])
            ->where('target_warehouse_id', $warehouse->id)
            ->whereNull('received_at')
            ->where('status', '!=', 'cancelled')
            ->latest('assigned_at')
            ->limit(100)
            ->get([
                'id',
                'shipment_id',
                'driver_id',
                'status',
                'assigned_at',
                'arrived_warehouse_at',
                'receive_notes',
            ]);

        $receivedItems = PickupItemConfirmation::query()
            ->with([
                'shipmentItem:id,shipment_id,description,quantity',
                'shipmentItem.shipment:id,shipment_number',
                'pickupAssignment:id,driver_id,received_warehouse_id,received_at',
                'pickupAssignment.driver:id,name,phone',
            ])
            ->whereHas('pickupAssignment', function ($query) use ($warehouse) {
                $query->where('received_warehouse_id', $warehouse->id)
                    ->whereNotNull('received_at');
            })
            ->latest('confirmed_at')
            ->limit(200)
            ->get([
                'id',
                'pickup_assignment_id',
                'shipment_item_id',
                'expected_quantity',
                'confirmed_quantity',
                'notes',
                'confirmed_at',
            ]);

        $warehouseStats = [
            'total_received_items' => (int) PickupItemConfirmation::query()
                ->whereHas('pickupAssignment', function ($query) use ($warehouse) {
                    $query->where('received_warehouse_id', $warehouse->id)
                        ->whereNotNull('received_at');
                })
                ->sum('confirmed_quantity'),
            'received_pickups' => PickupAssignment::query()
                ->where('received_warehouse_id', $warehouse->id)
                ->whereNotNull('received_at')
                ->count(),
            'pending_receipts' => PickupAssignment::query()
                ->where('target_warehouse_id', $warehouse->id)
                ->whereNull('received_at')
                ->where('status', '!=', 'cancelled')
                ->count(),
            'users_count' => $warehouse->users()->count(),
        ];

        return view('admin.warehouses.show', [
            'warehouse' => $warehouse,
            'canManage' => $canManage,
            'warehouseStats' => $warehouseStats,
            'warehouseUsers' => $warehouseUsers,
            'userRoles' => $userRoles,
            'receivedPickups' => $receivedPickups,
            'pendingReceipts' => $pendingReceipts,
            'receivedItems' => $receivedItems,
            'canCreateUsers' => $currentAdmin->hasPermission('users.create'),
        ]);
    }

    /**
     * Get a single warehouse for editing.
     */
    public function show(Warehouse $warehouse)
    {
        $this->authorizePermission('warehouses.view');

        return response()->json([
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'address' => $warehouse->address,
                'region_id' => $warehouse->region_id,
                'district_id' => $warehouse->district_id,
                'contact_phone' => $warehouse->contact_phone,
                'contact_email' => $warehouse->contact_email,
                'capacity' => $warehouse->capacity,
                'is_active' => $warehouse->is_active,
            ],
        ]);
    }

    /**
     * Store a new warehouse.
     */
    public function store(Request $request)
    {
        $this->authorizePermission('warehouses.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', 'unique:warehouses,code'],
            'address' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'capacity' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $warehouse = new Warehouse();
        $warehouse->name = $validated['name'];
        $warehouse->code = $validated['code'] ?? null;
        $warehouse->address = $validated['address'] ?? null;
        $warehouse->region_id = $validated['region_id'] ?? null;
        $warehouse->district_id = $validated['district_id'] ?? null;
        $warehouse->contact_phone = $validated['contact_phone'] ?? null;
        $warehouse->contact_email = $validated['contact_email'] ?? null;
        $warehouse->capacity = $validated['capacity'] ?? null;
        $warehouse->is_active = $validated['is_active'] ?? true;
        $warehouse->save();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse created successfully.',
            'warehouse' => $warehouse,
        ]);
    }

    /**
     * Update an existing warehouse.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $this->authorizePermission('warehouses.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('warehouses')->ignore($warehouse->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'capacity' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $warehouse->name = $validated['name'];
        $warehouse->code = $validated['code'] ?? null;
        $warehouse->address = $validated['address'] ?? null;
        $warehouse->region_id = $validated['region_id'] ?? null;
        $warehouse->district_id = $validated['district_id'] ?? null;
        $warehouse->contact_phone = $validated['contact_phone'] ?? null;
        $warehouse->contact_email = $validated['contact_email'] ?? null;
        $warehouse->capacity = $validated['capacity'] ?? null;
        $warehouse->is_active = $validated['is_active'] ?? $warehouse->is_active;
        $warehouse->save();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse updated successfully.',
            'warehouse' => $warehouse,
        ]);
    }

    /**
     * Toggle warehouse active status.
     */
    public function toggleActive(Warehouse $warehouse)
    {
        $this->authorizePermission('warehouses.edit');

        $warehouse->is_active = !$warehouse->is_active;
        $warehouse->save();

        return response()->json([
            'success' => true,
            'message' => $warehouse->is_active ? 'Warehouse activated.' : 'Warehouse deactivated.',
            'is_active' => $warehouse->is_active,
        ]);
    }

    /**
     * Delete a warehouse.
     */
    public function destroy(Warehouse $warehouse)
    {
        $this->authorizePermission('warehouses.delete');

        $warehouse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully.',
        ]);
    }

    /**
     * Export warehouses data.
     */
    public function export(Request $request)
    {
        $this->authorizePermission('warehouses.view');

        $query = Warehouse::with(['region', 'district']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->get('status') !== '') {
            $query->where('is_active', $request->get('status') === 'active');
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $warehouses = $query->orderBy('created_at', 'desc')->get();

        $rows = $warehouses->map(function ($warehouse) {
            return [
                'ID' => $warehouse->id,
                'Name' => $warehouse->name,
                'Code' => $warehouse->code,
                'Region' => $warehouse->region?->name,
                'District' => $warehouse->district?->name,
                'Contact Phone' => $warehouse->contact_phone,
                'Capacity (m³)' => $warehouse->capacity,
                'Status' => $warehouse->is_active ? 'Active' : 'Inactive',
                'Created At' => $warehouse->created_at->format('Y-m-d H:i:s'),
            ];
        })->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return $this->exportExcel($rows);
        }

        if ($format === 'pdf') {
            return $this->exportPDF($rows);
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * Export warehouses to Excel format.
     */
    private function exportExcel(array $rows)
    {
        $filename = 'warehouses_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new WarehousesExport($rows), $filename);
    }

    /**
     * Export warehouses to PDF format.
     */
    private function exportPDF(array $rows)
    {
        $filename = 'warehouses_' . date('Y-m-d_His') . '.pdf';
        return GenericPdfExporter::download($rows, $filename, 'Warehouses List');
    }

    /**
     * Get regions for warehouse forms.
     */
    public function regions()
    {
        $this->authorizePermission('warehouses.view');

        return response()->json([
            'success' => true,
            'data' => Region::orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Get districts for a region.
     */
    public function districts(Region $region)
    {
        $this->authorizePermission('warehouses.view');

        return response()->json([
            'success' => true,
            'data' => $region->districts()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Get warehouse users data for DataTable.
     */
    public function usersData(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorizePermission('warehouses.view');

        $currentUser = Auth::guard('admin')->user();

        if ($currentUser->isSuperAdmin()) {
            $query = User::query()
                ->where('warehouse_id', $warehouse->id)
                ->with(['creator', 'roles', 'warehouse']);
        } else {
            $query = User::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('created_by_user_id', $currentUser->id)
                ->with(['creator', 'roles', 'warehouse']);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
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

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $total = $query->count();

        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['name', 'email', 'created_at', 'last_login_at'];

        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $users = $query->skip($offset)->take($perPage)->get();

        $data = $users->map(function ($user) use ($currentUser) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn($role) => ['id' => $role->id, 'name' => $role->name]),
                'is_active' => $user->is_active,
                'creator' => $user->creator?->name ?? 'System',
                'created_at' => $user->created_at->format('d/m/Y, H:i:s'),
                'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Never',
                'can_manage' => $currentUser->canManage($user),
                'is_self' => $user->id === $currentUser->id,
                'view_url' => route('admin.admins.show', $user),
                'edit_url' => route('admin.admins.edit', $user),
                'update_url' => route('admin.admins.update', $user),
                'toggle_url' => route('admin.admins.toggle-active', $user),
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
     * Export warehouse users data.
     */
    public function usersExport(Request $request, Warehouse $warehouse)
    {
        $this->authorizePermission('warehouses.view');

        $currentUser = Auth::guard('admin')->user();

        if ($currentUser->isSuperAdmin()) {
            $query = User::query()
                ->where('warehouse_id', $warehouse->id)
                ->with(['creator', 'roles', 'warehouse']);
        } else {
            $query = User::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('created_by_user_id', $currentUser->id)
                ->with(['creator', 'roles', 'warehouse']);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
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

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $users = $query->latest()->get();

        $rows = $users->map(function ($user) {
            return [
                'Name' => $user->name,
                'Email' => $user->email,
                'Roles' => $user->roles->pluck('name')->implode(', '),
                'Status' => $user->is_active ? 'Active' : 'Inactive',
                'Warehouse' => $user->warehouse?->name ?? 'Unassigned',
                'Created By' => $user->creator?->name ?? 'System',
                'Created At' => $user->created_at->format('Y-m-d H:i:s'),
                'Last Login' => $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
            ];
        })->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            $filename = 'warehouse_users_' . $warehouse->id . '_' . date('Y-m-d_His') . '.xlsx';
            return Excel::download(new UsersExport($rows), $filename);
        }

        if ($format === 'pdf') {
            $filename = 'warehouse_users_' . $warehouse->id . '_' . date('Y-m-d_His') . '.pdf';
            return GenericPdfExporter::download($rows, $filename, 'Warehouse Users List');
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * Check if current admin has permission.
     */
    protected function authorizePermission(string $permission): void
    {
        if (!Auth::guard('admin')->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}

