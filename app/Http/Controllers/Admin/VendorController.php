<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShipmentStatus;
use App\Exports\VendorsExport;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\Vendor;
use App\Models\VendorActivityLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class VendorController extends Controller
{
    /**
     * Display vendors index page.
     */
    public function index()
    {
        $this->authorizePermission('vendors.view');

        return view('admin.vendors.index');
    }

    /**
     * Display vendor detail page.
     */
    public function showPage(Vendor $vendor)
    {
        $this->authorizePermission('vendors.view');

        // Load counts
        $shipmentsCount = $vendor->shipments()->count();
        $activityLogsCount = $vendor->activityLogs()->count();
        $otpLogsCount = OtpCode::where('phone', $vendor->phone)->count();

        // Shipment statistics
        $shipmentStats = [
            'total' => $shipmentsCount,
            'draft' => $vendor->shipments()->where('status', ShipmentStatus::DRAFT)->count(),
            'in_progress' => $vendor->shipments()->whereNotIn('status', [
                ShipmentStatus::DRAFT,
                ShipmentStatus::DELIVERED,
                ShipmentStatus::CANCELLED,
            ])->count(),
            'delivered' => $vendor->shipments()->where('status', ShipmentStatus::DELIVERED)->count(),
            'cancelled' => $vendor->shipments()->where('status', ShipmentStatus::CANCELLED)->count(),
        ];

        // Recent activity
        $lastActivity = $vendor->activityLogs()->latest('created_at')->first();
        $lastLogin = $vendor->activityLogs()->where('action', 'login')->latest('created_at')->first();

        $canManage = Auth::guard('admin')->user()->hasPermission('vendors.edit');

        return view('admin.vendors.show', [
            'vendor' => $vendor,
            'shipmentsCount' => $shipmentsCount,
            'activityLogsCount' => $activityLogsCount,
            'otpLogsCount' => $otpLogsCount,
            'shipmentStats' => $shipmentStats,
            'lastActivity' => $lastActivity,
            'lastLogin' => $lastLogin,
            'canManage' => $canManage,
            'statuses' => ShipmentStatus::toArray(),
        ]);
    }

    /**
     * Get shipments data for vendor.
     */
    public function shipments(Request $request, Vendor $vendor)
    {
        $this->authorizePermission('vendors.view');

        $query = $vendor->shipments()->with(['region', 'district']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%");
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
        $allowedSorts = ['shipment_number', 'recipient_name', 'status', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 10), 50);
        $shipments = $query->paginate($perPage);

        return response()->json([
            'data' => $shipments->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'shipment_number' => $shipment->shipment_number,
                    'recipient_name' => $shipment->recipient_name,
                    'recipient_phone' => $shipment->recipient_phone,
                    'status' => $shipment->status->value,
                    'status_label' => $shipment->status->label(),
                    'region' => $shipment->region?->name,
                    'district' => $shipment->district?->name,
                    'items_count' => $shipment->items()->count(),
                    'created_at' => $shipment->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'from' => $shipments->firstItem() ?? 0,
                'to' => $shipments->lastItem() ?? 0,
                'total' => $shipments->total(),
                'last_page' => $shipments->lastPage(),
            ],
        ]);
    }

    /**
     * Get activity logs data for vendor.
     */
    public function activityLogs(Request $request, Vendor $vendor)
    {
        $this->authorizePermission('vendors.view');

        $query = $vendor->activityLogs();

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
     * Get OTP logs data for vendor.
     */
    public function otpLogs(Request $request, Vendor $vendor)
    {
        $this->authorizePermission('vendors.view');

        $query = OtpCode::where('phone', $vendor->phone);

        // Purpose filter
        if ($purpose = $request->get('purpose')) {
            $query->where('purpose', $purpose);
        }

        // Date range filter
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Sorting
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 10), 50);
        $logs = $query->paginate($perPage);

        return response()->json([
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'code' => $log->code,
                    'purpose' => $log->purpose,
                    'expires_at' => $log->expires_at?->format('Y-m-d H:i:s'),
                    'verified_at' => $log->verified_at?->format('Y-m-d H:i:s'),
                    'is_expired' => $log->isExpired(),
                    'is_verified' => $log->isVerified(),
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
     * Get vendors data for DataTable.
     */
    public function data(Request $request)
    {
        $this->authorizePermission('vendors.view');

        $query = Vendor::query();

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
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
        $allowedSorts = ['name', 'business_name', 'email', 'phone', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $vendors = $query->paginate($perPage);

        $canManage = Auth::guard('admin')->user()->hasPermission('vendors.edit');

        return response()->json([
            'data' => $vendors->map(function ($vendor) use ($canManage) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'business_name' => $vendor->business_name,
                    'email' => $vendor->email,
                    'phone' => $vendor->phone,
                    'is_active' => $vendor->is_active,
                    'shipments_count' => $vendor->shipments()->count(),
                    'created_at' => $vendor->created_at->format('Y-m-d H:i:s'),
                    'can_manage' => $canManage,
                ];
            }),
            'meta' => [
                'current_page' => $vendors->currentPage(),
                'from' => $vendors->firstItem() ?? 0,
                'to' => $vendors->lastItem() ?? 0,
                'total' => $vendors->total(),
                'last_page' => $vendors->lastPage(),
            ],
        ]);
    }

    /**
     * Store a new vendor.
     */
    public function store(Request $request)
    {
        $this->authorizePermission('vendors.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:vendors,email'],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!PhoneHelper::isValid($value)) {
                    $fail('Please enter a valid Ghana phone number.');
                }
            }, 'unique:vendors,phone'],
            'is_active' => ['boolean'],
        ]);

        $phone = PhoneHelper::format($validated['phone']);

        $vendor = new Vendor();
        $vendor->name = $validated['name'];
        $vendor->business_name = $validated['business_name'] ?? null;
        $vendor->email = $validated['email'] ?? null;
        $vendor->phone = $phone;
        $vendor->is_active = $validated['is_active'] ?? true;
        $vendor->save();

        return response()->json([
            'success' => true,
            'message' => 'Vendor created successfully.',
            'vendor' => $vendor,
        ]);
    }

    /**
     * Get a single vendor for editing.
     */
    public function show(Vendor $vendor)
    {
        $this->authorizePermission('vendors.view');

        return response()->json([
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'business_name' => $vendor->business_name,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'is_active' => $vendor->is_active,
            ],
        ]);
    }

    /**
     * Update an existing vendor.
     */
    public function update(Request $request, Vendor $vendor)
    {
        $this->authorizePermission('vendors.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('vendors')->ignore($vendor->id)],
            'is_active' => ['boolean'],
        ]);

        $vendor->name = $validated['name'];
        $vendor->business_name = $validated['business_name'] ?? null;
        $vendor->email = $validated['email'] ?? null;
        $vendor->is_active = $validated['is_active'] ?? $vendor->is_active;
        $vendor->save();

        return response()->json([
            'success' => true,
            'message' => 'Vendor updated successfully.',
            'vendor' => $vendor,
        ]);
    }

    /**
     * Toggle vendor active status.
     */
    public function toggleActive(Vendor $vendor)
    {
        $this->authorizePermission('vendors.edit');

        $vendor->is_active = !$vendor->is_active;
        $vendor->save();

        return response()->json([
            'success' => true,
            'message' => $vendor->is_active ? 'Vendor activated.' : 'Vendor deactivated.',
            'is_active' => $vendor->is_active,
        ]);
    }

    /**
     * Delete a vendor.
     */
    public function destroy(Vendor $vendor)
    {
        $this->authorizePermission('vendors.delete');

        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor deleted successfully.',
        ]);
    }

    /**
     * Export vendors data.
     */
    public function export(Request $request)
    {
        $this->authorizePermission('vendors.view');

        $query = Vendor::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
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

        $vendors = $query->orderBy('created_at', 'desc')->get();

        $rows = $vendors->map(function ($vendor) {
            return [
                'ID' => $vendor->id,
                'Name' => $vendor->name,
                'Business Name' => $vendor->business_name,
                'Email' => $vendor->email,
                'Phone' => $vendor->phone,
                'Status' => $vendor->is_active ? 'Active' : 'Inactive',
                'Created At' => $vendor->created_at->format('Y-m-d H:i:s'),
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
     * Export vendors to Excel format.
     */
    private function exportExcel(array $rows)
    {
        $filename = 'vendors_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new VendorsExport($rows), $filename);
    }

    /**
     * Export vendors to PDF format.
     */
    private function exportPDF(array $rows)
    {
        $filename = 'vendors_' . date('Y-m-d_His') . '.pdf';
        $pdf = Pdf::loadView('admin.vendors.export-pdf', [
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
