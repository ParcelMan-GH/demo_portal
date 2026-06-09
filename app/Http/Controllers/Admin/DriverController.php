<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DriversExport;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\StorageService;
use App\Support\GenericPdfExporter;
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

    public function redirectLegacyIndex()
    {
        return redirect()->route('admin.drivers.index');
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

        // Account status filter
        if ($request->has('account_status') && $request->get('account_status') !== '') {
            $accountStatus = $request->get('account_status');

            if (in_array($accountStatus, ['active', 'inactive'], true)) {
                $query->where('is_active', $accountStatus === 'active');
            }
        }

        // Availability filter
        if ($request->has('availability') && $request->get('availability') !== '') {
            $availability = $request->get('availability');

            if (in_array($availability, ['available', 'busy', 'offline'], true)) {
                $query->where('status', $availability);
            }
        }

        // Capability filter
        if ($request->has('capability') && $request->get('capability') !== '') {
            $capability = $request->get('capability');

            if (in_array($capability, Driver::CAPABILITIES, true)) {
                $query->whereJsonContains('task_capabilities', $capability);
            }
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
                    'avatar' => strtoupper(substr($driver->name, 0, 1)),
                    'photo_url' => $driver->photo_path ? app(StorageService::class)->getUrl($driver->photo_path) : null,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                    'vehicle_type' => $driver->vehicle_type,
                    'vehicle_number' => $driver->vehicle_number,
                    'license_number' => $driver->license_number,
                    'status' => $driver->status,
                    'is_active' => $driver->is_active,
                    'task_capabilities' => $driver->getCapabilities(),
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

        // Statistics
        $pickupsCount = $driver->pickupAssignments()->count();
        $completedCount = $driver->pickupAssignments()->where('status', 'completed')->count();
        $transportManifestsCount = $driver->transportManifests()->count();
        $deliveryRunsCount = $driver->deliveryRuns()->count();
        $currentPackagesCount = \App\Models\LabelCustodyEvent::query()
            ->where('driver_id', $driver->id)
            ->where('event_type', 'claimed')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('label_custody_events')->groupBy('warehouse_receipt_item_label_id');
            })
            ->count();
        $activeAssignment = $driver->activeAssignment;
        $lastLogin = $driver->activityLogs()->where('action', 'driver_login')->latest('created_at')->first();
        $activityLogsCount = $driver->activityLogs()->count();

        $canManage = Auth::guard('admin')->user()->hasPermission('drivers.edit');

        return view('admin.drivers.show', [
            'driver' => $driver,
            'assignmentsCount' => $pickupsCount,
            'pickupsCount' => $pickupsCount,
            'completedCount' => $completedCount,
            'transportManifestsCount' => $transportManifestsCount,
            'deliveryRunsCount' => $deliveryRunsCount,
            'currentPackagesCount' => $currentPackagesCount,
            'activityLogsCount' => $activityLogsCount,
            'activeAssignment' => $activeAssignment,
            'lastLogin' => $lastLogin,
            'canManage' => $canManage,
        ]);
    }

    public function redirectLegacyShow(Driver $driver)
    {
        return redirect()->route('admin.drivers.show', $driver);
    }

    /**
     * Store a new driver.
     */
    public function store(Request $request)
    {
        $this->authorizePermission('drivers.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:drivers,email'],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!PhoneHelper::isValid($value)) {
                    $fail('Please enter a valid Ghana phone number.');
                }
            }, 'unique:drivers,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'vehicle_type' => ['nullable', Rule::in(['motorcycle', 'car', 'van', 'truck'])],
            'vehicle_number' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'task_capabilities' => ['required', 'array', 'min:1'],
            'task_capabilities.*' => ['string', Rule::in(Driver::CAPABILITIES)],
            'is_active' => ['boolean'],
        ]);

        $phone = PhoneHelper::format($validated['phone']);
        $taskCapabilities = $this->normalizeTaskCapabilities($validated['task_capabilities'] ?? []);

        $driver = new Driver();
        $driver->name = $validated['name'];
        $driver->email = $validated['email'] ?? null;
        $driver->phone = $phone;
        if ($request->hasFile('profile_photo')) {
            $driver->photo_path = app(StorageService::class)->upload($request->file('profile_photo'), 'driver-photos')['path'];
        }
        $driver->password = Hash::make($validated['password']);
        $driver->vehicle_type = $validated['vehicle_type'] ?? 'motorcycle';
        $driver->vehicle_number = $validated['vehicle_number'] ?? null;
        $driver->license_number = $validated['license_number'] ?? null;
        $driver->task_capabilities = $taskCapabilities;
        $driver->is_active = $validated['is_active'] ?? true;
        $driver->save();

        return response()->json([
            'success' => true,
            'message' => 'Rider created successfully.',
            'driver' => $this->driverPayload($driver->fresh()),
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
            'email' => ['nullable', 'email', 'max:255', Rule::unique('drivers')->ignore($driver->id)],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!PhoneHelper::isValid($value)) {
                    $fail('Please enter a valid Ghana phone number.');
                }
            }, Rule::unique('drivers')->ignore($driver->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'vehicle_type' => ['nullable', Rule::in(['motorcycle', 'car', 'van', 'truck'])],
            'vehicle_number' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'task_capabilities' => ['nullable', 'array', 'min:1'],
            'task_capabilities.*' => ['string', Rule::in(Driver::CAPABILITIES)],
            'is_active' => ['boolean'],
        ]);

        $phone = PhoneHelper::format($validated['phone']);

        $driver->name = $validated['name'];
        $driver->email = $validated['email'] ?? null;
        $driver->phone = $phone;
        $driver->vehicle_type = $validated['vehicle_type'] ?? $driver->vehicle_type;
        $driver->vehicle_number = $validated['vehicle_number'] ?? null;
        $driver->license_number = $validated['license_number'] ?? null;
        if (array_key_exists('task_capabilities', $validated)) {
            $driver->task_capabilities = $this->normalizeTaskCapabilities($validated['task_capabilities'] ?? []);
        }
        $driver->is_active = $validated['is_active'] ?? $driver->is_active;

        if (!empty($validated['password'])) {
            $driver->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('profile_photo')) {
            if ($driver->photo_path) {
                app(StorageService::class)->delete($driver->photo_path);
            }

            $driver->photo_path = app(StorageService::class)->upload($request->file('profile_photo'), 'driver-photos')['path'];
        }

        $driver->save();

        return response()->json([
            'success' => true,
            'message' => 'Rider updated successfully.',
            'driver' => $this->driverPayload($driver->fresh()),
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
            'message' => $driver->is_active ? 'Rider activated.' : 'Rider deactivated.',
            'is_active' => $driver->is_active,
        ]);
    }

    /**
     * Delete a rider.
     */
    public function destroy(Driver $driver)
    {
        $this->authorizePermission('drivers.delete');

        $driver->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rider deleted successfully.',
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

        if ($request->has('account_status') && $request->get('account_status') !== '') {
            $accountStatus = $request->get('account_status');

            if (in_array($accountStatus, ['active', 'inactive'], true)) {
                $query->where('is_active', $accountStatus === 'active');
            }
        }

        if ($request->has('availability') && $request->get('availability') !== '') {
            $availability = $request->get('availability');

            if (in_array($availability, ['available', 'busy', 'offline'], true)) {
                $query->where('status', $availability);
            }
        }

        if ($request->has('capability') && $request->get('capability') !== '') {
            $capability = $request->get('capability');

            if (in_array($capability, Driver::CAPABILITIES, true)) {
                $query->whereJsonContains('task_capabilities', $capability);
            }
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
                'Capabilities' => implode(', ', $driver->getCapabilities()),
                'Availability' => $driver->status,
                'Account' => $driver->is_active ? 'Active' : 'Inactive',
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

        $query = $driver->pickupAssignments()->with(['shipment.vendor']);

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

        if ($vendor = $request->get('vendor')) {
            $query->whereHas('shipment.vendor', function ($vendorQuery) use ($vendor) {
                $vendorQuery->where('name', 'like', "%{$vendor}%")
                    ->orWhere('phone', 'like', "%{$vendor}%")
                    ->orWhere('business_name', 'like', "%{$vendor}%");
            });
        }

        if ($recipientPhone = $request->get('recipient_phone')) {
            $query->whereHas('shipment', function ($shipmentQuery) use ($recipientPhone) {
                $shipmentQuery->where('delivery_recipient_phone', 'like', "%{$recipientPhone}%")
                    ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('delivery_recipient_phone', 'like', "%{$recipientPhone}%"));
            });
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
                    'shipment_number' => $assignment->shipment?->shipment_number,
                    'vendor_name' => $assignment->shipment?->vendor?->name,
                    'assigned_at' => $assignment->assigned_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $assignment->completed_at?->format('Y-m-d H:i:s'),
                    'notes' => $assignment->notes,
                    'created_at' => $assignment->created_at?->format('Y-m-d H:i:s'),
                    'view_url' => $assignment->shipment ? route('admin.shipments.show', $assignment->shipment) : null,
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
     * Get transport manifests data for driver.
     */
    public function transportManifests(Request $request, Driver $driver)
    {
        $this->authorizePermission('drivers.view');

        $query = $driver->transportManifests()->with(['originWarehouse', 'destinationWarehouse']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('manifest_number', 'like', "%{$search}%")
                    ->orWhereHas('originWarehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('destinationWarehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($origin = $request->get('origin')) {
            $query->whereHas('originWarehouse', fn ($warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$origin}%"));
        }

        if ($destination = $request->get('destination')) {
            $query->whereHas('destinationWarehouse', fn ($warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$destination}%"));
        }

        // Date range filter
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($receivedFrom = $request->get('received_from')) {
            $query->whereDate('received_at', '>=', $receivedFrom);
        }

        if ($receivedTo = $request->get('received_to')) {
            $query->whereDate('received_at', '<=', $receivedTo);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['manifest_number', 'status', 'assigned_at', 'received_at', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 10), 50);
        $manifests = $query->paginate($perPage);

        return response()->json([
            'data' => $manifests->map(function ($manifest) {
                return [
                    'id' => $manifest->id,
                    'manifest_number' => $manifest->manifest_number,
                    'origin_warehouse' => $manifest->originWarehouse?->name,
                    'destination_warehouse' => $manifest->destinationWarehouse?->name,
                    'status' => $manifest->status,
                    'assigned_at' => $manifest->assigned_at?->format('Y-m-d H:i:s'),
                    'received_at' => $manifest->received_at?->format('Y-m-d H:i:s'),
                    'created_at' => $manifest->created_at?->format('Y-m-d H:i:s'),
                    'view_url' => route('admin.transport-manifests.show', $manifest),
                ];
            }),
            'meta' => [
                'current_page' => $manifests->currentPage(),
                'from' => $manifests->firstItem() ?? 0,
                'to' => $manifests->lastItem() ?? 0,
                'total' => $manifests->total(),
                'last_page' => $manifests->lastPage(),
            ],
        ]);
    }

    /**
     * Get delivery runs data for driver.
     */
    public function deliveryRuns(Request $request, Driver $driver)
    {
        $this->authorizePermission('drivers.view');

        $query = $driver->deliveryRuns()->with(['warehouse'])->withCount('stops');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('run_number', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', function ($wq) use ($search) {
                        $wq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($warehouse = $request->get('warehouse')) {
            $query->whereHas('warehouse', fn ($warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$warehouse}%"));
        }

        // Date range filter
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($completedFrom = $request->get('completed_from')) {
            $query->whereDate('completed_at', '>=', $completedFrom);
        }

        if ($completedTo = $request->get('completed_to')) {
            $query->whereDate('completed_at', '<=', $completedTo);
        }

        if ($stopsMin = $request->get('stops_min')) {
            $query->having('stops_count', '>=', (int) $stopsMin);
        }

        if ($stopsMax = $request->get('stops_max')) {
            $query->having('stops_count', '<=', (int) $stopsMax);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['run_number', 'status', 'assigned_at', 'completed_at', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 10), 50);
        $runs = $query->paginate($perPage);

        return response()->json([
            'data' => $runs->map(function ($run) {
                return [
                    'id' => $run->id,
                    'run_number' => $run->run_number,
                    'warehouse' => $run->warehouse?->name,
                    'stops_count' => $run->stops_count,
                    'status' => $run->status,
                    'assigned_at' => $run->assigned_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $run->completed_at?->format('Y-m-d H:i:s'),
                    'created_at' => $run->created_at?->format('Y-m-d H:i:s'),
                    'view_url' => route('admin.delivery-runs.show', $run),
                ];
            }),
            'meta' => [
                'current_page' => $runs->currentPage(),
                'from' => $runs->firstItem() ?? 0,
                'to' => $runs->lastItem() ?? 0,
                'total' => $runs->total(),
                'last_page' => $runs->lastPage(),
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

        if ($deviceType = $request->get('device_type')) {
            $query->where('device_type', $deviceType);
        }

        if ($ipAddress = $request->get('ip_address')) {
            $query->where('ip_address', 'like', "%{$ipAddress}%");
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
        return GenericPdfExporter::download($rows, $filename, 'Riders List');
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

    /**
     * @param array<int, mixed> $capabilities
     * @return array<int, string>
     */
    protected function normalizeTaskCapabilities(array $capabilities): array
    {
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn ($value) => is_string($value) ? strtolower(trim($value)) : null,
            $capabilities
        ), static fn ($value) => in_array($value, Driver::CAPABILITIES, true))));

        return !empty($normalized)
            ? $normalized
            : [Driver::CAPABILITY_PICKUP];
    }

    protected function driverPayload(Driver $driver): array
    {
        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'avatar' => strtoupper(substr($driver->name, 0, 1)),
            'photo_url' => $driver->photo_path ? app(StorageService::class)->getUrl($driver->photo_path) : null,
            'email' => $driver->email,
            'phone' => $driver->phone,
            'vehicle_type' => $driver->vehicle_type,
            'vehicle_number' => $driver->vehicle_number,
            'license_number' => $driver->license_number,
            'status' => $driver->status,
            'is_active' => $driver->is_active,
            'task_capabilities' => $driver->getCapabilities(),
        ];
    }

    public function packagesData(Request $request, Driver $driver): \Illuminate\Http\JsonResponse
    {
        $this->authorizePermission('drivers.view');

        // Get all custody events for this driver
        $query = \App\Models\LabelCustodyEvent::query()
            ->where('driver_id', $driver->id)
            ->with([
                'label.receiptItem.shipmentItem.shipment:id,shipment_number,delivery_recipient_name,delivery_town',
                'label.receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_town',
            ]);

        // Filter: current (latest event is 'claimed') or all
        $filter = $request->input('filter', 'current');

        if ($filter === 'current') {
            $query->where('event_type', 'claimed')
                ->whereIn('id', function ($q) {
                    $q->selectRaw('MAX(id)')->from('label_custody_events')->groupBy('warehouse_receipt_item_label_id');
                });
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('event_type', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('label', fn ($labelQuery) => $labelQuery->where('barcode_value', 'like', "%{$search}%"))
                    ->orWhereHas('label.receiptItem.shipmentItem', function ($itemQuery) use ($search) {
                        $itemQuery->where('description', 'like', "%{$search}%")
                            ->orWhere('tracking_code', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                            ->orWhere('delivery_town', 'like', "%{$search}%");
                    })
                    ->orWhereHas('label.receiptItem.shipmentItem.shipment', function ($shipmentQuery) use ($search) {
                        $shipmentQuery->where('shipment_number', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                            ->orWhere('delivery_town', 'like', "%{$search}%");
                    });
            });
        }

        if ($eventType = $request->input('event_type')) {
            $query->where('event_type', $eventType);
        }

        if ($recipient = $request->input('recipient')) {
            $query->where(function ($q) use ($recipient) {
                $q->whereHas('label.receiptItem.shipmentItem', fn ($itemQuery) => $itemQuery->where('delivery_recipient_name', 'like', "%{$recipient}%"))
                    ->orWhereHas('label.receiptItem.shipmentItem.shipment', fn ($shipmentQuery) => $shipmentQuery->where('delivery_recipient_name', 'like', "%{$recipient}%"));
            });
        }

        if ($location = $request->input('location')) {
            $query->where(function ($q) use ($location) {
                $q->whereHas('label.receiptItem.shipmentItem', fn ($itemQuery) => $itemQuery->where('delivery_town', 'like', "%{$location}%"))
                    ->orWhereHas('label.receiptItem.shipmentItem.shipment', fn ($shipmentQuery) => $shipmentQuery->where('delivery_town', 'like', "%{$location}%"));
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $page = max((int) $request->input('page', 1), 1);

        $events = $query->latest('created_at')->skip(($page - 1) * $perPage)->take($perPage)->get();

        $rows = $events->map(function ($event) {
            $label = $event->label;
            $item = $label?->receiptItem?->shipmentItem;
            $shipment = $item?->shipment;

            return [
                'barcode' => $label?->barcode_value,
                'event_type' => $event->event_type,
                'shipment_number' => $shipment?->shipment_number,
                'description' => $item?->description,
                'recipient_name' => $item?->delivery_recipient_name ?: $shipment?->delivery_recipient_name,
                'delivery_town' => $item?->delivery_town ?: $shipment?->delivery_town,
                'notes' => $event->notes,
                'created_at' => $event->created_at->format('M d, Y H:i'),
            ];
        });

        return response()->json([
            'data' => $rows,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'from' => $total === 0 ? 0 : (($page - 1) * $perPage) + 1,
                'to' => min($page * $perPage, $total),
            ],
        ]);
    }
}
