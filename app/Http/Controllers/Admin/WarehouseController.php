<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WarehouseType;
use App\Exports\WarehousesExport;
use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view('admin.warehouses.index', [
            'types' => WarehouseType::toArray(),
        ]);
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

        // Type filter
        if ($type = $request->get('type')) {
            $query->where('type', $type);
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
        $allowedSorts = ['name', 'code', 'type', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $warehouses = $query->paginate($perPage);

        $canManage = Auth::guard('admin')->user()->hasPermission('warehouses.edit');

        return response()->json([
            'data' => $warehouses->map(function ($warehouse) use ($canManage) {
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'type' => $warehouse->type?->value,
                    'type_label' => $warehouse->type?->label(),
                    'address' => $warehouse->address,
                    'region' => $warehouse->region?->name,
                    'district' => $warehouse->district?->name,
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

        $canManage = Auth::guard('admin')->user()->hasPermission('warehouses.edit');

        return view('admin.warehouses.show', [
            'warehouse' => $warehouse,
            'canManage' => $canManage,
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
                'type' => $warehouse->type?->value,
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
            'type' => ['nullable', 'string', Rule::in(['origin', 'destination', 'both'])],
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
        $warehouse->type = $validated['type'] ?? null;
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
            'type' => ['nullable', 'string', Rule::in(['origin', 'destination', 'both'])],
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
        $warehouse->type = $validated['type'] ?? null;
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

        // Type filter
        if ($type = $request->get('type')) {
            $query->where('type', $type);
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
                'Type' => $warehouse->type?->label(),
                'Region' => $warehouse->region?->name,
                'District' => $warehouse->district?->name,
                'Contact Phone' => $warehouse->contact_phone,
                'Capacity' => $warehouse->capacity,
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
        $pdf = Pdf::loadView('admin.warehouses.export-pdf', [
            'rows' => $rows,
            'generatedAt' => now()->format('F d, Y H:i:s'),
        ]);
        return $pdf->download($filename);
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
