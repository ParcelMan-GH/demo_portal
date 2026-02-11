<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DriversExport;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class DriverController extends Controller
{
    /**
     * Display drivers index page.
     */
    public function index()
    {
        $this->authorizePermission('drivers.view');

        return view('admin.drivers.index');
    }

    /**
     * Get drivers data for DataTable.
     */
    public function data(Request $request)
    {
        $this->authorizePermission('drivers.view');

        $query = Driver::query();

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
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
        $allowedSorts = ['name', 'email', 'phone', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $drivers = $query->paginate($perPage);

        $canManage = Auth::guard('admin')->user()->hasPermission('drivers.edit');

        return response()->json([
            'data' => $drivers->map(function ($driver) use ($canManage) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                    'vehicle_type' => $driver->vehicle_type,
                    'vehicle_number' => $driver->vehicle_number,
                    'license_number' => $driver->license_number,
                    'status' => $driver->status,
                    'is_active' => $driver->is_active,
                    'assignments_count' => $driver->pickupAssignments()->count(),
                    'created_at' => $driver->created_at->format('Y-m-d H:i:s'),
                    'can_manage' => $canManage,
                ];
            }),
            'meta' => [
                'current_page' => $drivers->currentPage(),
                'from' => $drivers->firstItem() ?? 0,
                'to' => $drivers->lastItem() ?? 0,
                'total' => $drivers->total(),
                'last_page' => $drivers->lastPage(),
            ],
        ]);
    }

    /**
     * Display driver detail page.
     */
    public function showPage(Driver $driver)
    {
        $this->authorizePermission('drivers.view');

        // Assignment statistics
        $assignmentsCount = $driver->pickupAssignments()->count();
        $completedCount = $driver->pickupAssignments()->where('status', 'completed')->count();
        $activeAssignment = $driver->activeAssignment;
        $lastLogin = $driver->activityLogs()->where('action', 'driver_login')->latest('created_at')->first();
        $activityLogsCount = $driver->activityLogs()->count();

        $canManage = Auth::guard('admin')->user()->hasPermission('drivers.edit');

        return view('admin.drivers.show', [
            'driver' => $driver,
            'assignmentsCount' => $assignmentsCount,
            'completedCount' => $completedCount,
            'activityLogsCount' => $activityLogsCount,
            'activeAssignment' => $activeAssignment,
            'lastLogin' => $lastLogin,
            'stats' => [
                'total_assignments' => $assignmentsCount,
                'completed_assignments' => $completedCount,
                'active_assignment' => $activeAssignment,
                'last_login' => $lastLogin,
            ],
            'canManage' => $canManage,
        ]);
    }

    /**
     * Store a new driver.
     */
    public function store(Request $request)
    {
        $this->authorizePermission('drivers.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:drivers,email'],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!PhoneHelper::isValid($value)) {
                    $fail('Please enter a valid Ghana phone number.');
                }
            }, 'unique:drivers,phone'],
            'password' => ['required', 'string', 'min:8'],
            'vehicle_type' => ['nullable', Rule::in(['motorcycle', 'car', 'van', 'truck'])],
            'vehicle_number' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $phone = PhoneHelper::format($validated['phone']);

        $driver = new Driver();
        $driver->name = $validated['name'];
        $driver->email = $validated['email'];
        $driver->phone = $phone;
        $driver->password = Hash::make($validated['password']);
        $driver->vehicle_type = $validated['vehicle_type'] ?? 'motorcycle';
        $driver->vehicle_number = $validated['vehicle_number'] ?? null;
        $driver->license_number = $validated['license_number'] ?? null;
        $driver->is_active = $validated['is_active'] ?? true;
        $driver->save();

        return response()->json([
            'success' => true,
            'message' => 'Driver created successfully.',
            'driver' => $driver,
        ]);
    }

    /**
     * Update an existing driver.
     */
    public function update(Request $request, Driver $driver)
    {
        $this->authorizePermission('drivers.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('drivers')->ignore($driver->id)],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!PhoneHelper::isValid($value)) {
                    $fail('Please enter a valid Ghana phone number.');
                }
            }, Rule::unique('drivers')->ignore($driver->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'vehicle_type' => ['nullable', Rule::in(['motorcycle', 'car', 'van', 'truck'])],
            'vehicle_number' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $phone = PhoneHelper::format($validated['phone']);

        $driver->name = $validated['name'];
        $driver->email = $validated['email'];
        $driver->phone = $phone;
        $driver->vehicle_type = $validated['vehicle_type'] ?? $driver->vehicle_type;
        $driver->vehicle_number = $validated['vehicle_number'] ?? null;
        $driver->license_number = $validated['license_number'] ?? null;
        $driver->is_active = $validated['is_active'] ?? $driver->is_active;

        if (!empty($validated['password'])) {
            $driver->password = Hash::make($validated['password']);
        }

        $driver->save();

        return response()->json([
            'success' => true,
            'message' => 'Driver updated successfully.',
            'driver' => $driver,
        ]);
    }

    /**
     * Toggle driver active status.
     */
    public function toggleActive(Driver $driver)
    {
        $this->authorizePermission('drivers.edit');

        $driver->is_active = !$driver->is_active;
        $driver->save();

        return response()->json([
            'success' => true,
            'message' => $driver->is_active ? 'Driver activated.' : 'Driver deactivated.',
            'is_active' => $driver->is_active,
        ]);
    }

    /**
     * Delete a driver.
     */
    public function destroy(Driver $driver)
    {
        $this->authorizePermission('drivers.delete');

        $driver->delete();

        return response()->json([
            'success' => true,
            'message' => 'Driver deleted successfully.',
        ]);
    }

    /**
     * Export drivers data.
     */
    public function export(Request $request)
    {
        $this->authorizePermission('drivers.view');

        $query = Driver::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
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

        $drivers = $query->orderBy('created_at', 'desc')->get();

        $rows = $drivers->map(function ($driver) {
            return [
                'ID' => $driver->id,
                'Name' => $driver->name,
                'Email' => $driver->email,
                'Phone' => $driver->phone,
                'Vehicle Type' => $driver->vehicle_type,
                'Vehicle Number' => $driver->vehicle_number,
                'Status' => $driver->status,
                'Active' => $driver->is_active ? 'Active' : 'Inactive',
                'Created At' => $driver->created_at->format('Y-m-d H:i:s'),
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
     * Get pickup assignments data for driver.
     */
    public function assignments(Request $request, Driver $driver)
    {
        $this->authorizePermission('drivers.view');

        $query = $driver->pickupAssignments()->with(['shipment']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('status', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('shipment', function ($sq) use ($search) {
                        $sq->where('shipment_number', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                            ->orWhereHas('items', function ($itemQuery) use ($search) {
                                $itemQuery->where('delivery_recipient_name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
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
        $allowedSorts = ['status', 'assigned_at', 'completed_at', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 10), 50);
        $assignments = $query->paginate($perPage);

        return response()->json([
            'data' => $assignments->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'status' => $assignment->status->value,
                    'shipment' => $assignment->shipment ? [
                        'id' => $assignment->shipment->id,
                        'shipment_number' => $assignment->shipment->shipment_number,
                        'recipient_name' => $assignment->shipment->recipient_name,
                        'recipient_phone' => $assignment->shipment->recipient_phone,
                    ] : null,
                    'assigned_at' => $assignment->assigned_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $assignment->completed_at?->format('Y-m-d H:i:s'),
                    'notes' => $assignment->notes,
                    'created_at' => $assignment->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'from' => $assignments->firstItem() ?? 0,
                'to' => $assignments->lastItem() ?? 0,
                'total' => $assignments->total(),
                'last_page' => $assignments->lastPage(),
            ],
        ]);
    }

    /**
     * Get activity logs data for driver.
     */
    public function activityLogs(Request $request, Driver $driver)
    {
        $this->authorizePermission('drivers.view');

        $query = $driver->activityLogs();

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Action filter
        if ($action = $request->get('action')) {
            $query->where('action', $action);
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

        if ($sortBy === 'created_at') {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 10), 50);
        $logs = $query->paginate($perPage);

        return response()->json([
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'device_type' => $log->device_type,
                    'device_name' => $log->device_name,
                    'os_version' => $log->os_version,
                    'app_version' => $log->app_version,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'from' => $logs->firstItem() ?? 0,
                'to' => $logs->lastItem() ?? 0,
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Export drivers to Excel format.
     */
    private function exportExcel(array $rows)
    {
        $filename = 'drivers_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new DriversExport($rows), $filename);
    }

    /**
     * Export drivers to PDF format.
     */
    private function exportPDF(array $rows)
    {
        $filename = 'drivers_' . date('Y-m-d_His') . '.pdf';
        $pdf = Pdf::loadView('admin.drivers.export-pdf', [
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
