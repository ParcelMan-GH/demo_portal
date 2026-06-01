<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShipmentStatus;
use App\Exports\VendorsExport;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Vendor;
use App\Models\VendorActivityLog;
use App\Models\VendorEarning;
use App\Models\VendorPayout;
use App\Services\VendorCommissionService;
use App\Support\GenericPdfExporter;
use Illuminate\Database\Eloquent\Builder;
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
    public function showPage(Vendor $vendor, VendorCommissionService $commissionService)
    {
        $this->authorizePermission('vendors.view');

        // Load counts
        $shipmentsCount = $vendor->shipments()->count();
        $packagesCount = ShipmentItem::whereHas('shipment', fn ($query) => $query->where('vendor_id', $vendor->id))->count();
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
            'packagesCount' => $packagesCount,
            'activityLogsCount' => $activityLogsCount,
            'otpLogsCount' => $otpLogsCount,
            'shipmentStats' => $shipmentStats,
            'lastActivity' => $lastActivity,
            'lastLogin' => $lastLogin,
            'canManage' => $canManage,
            'statuses' => ShipmentStatus::toArray(),
            'globalCommissionRate' => $commissionService->getRatePerPackage(),
            'payoutSummary' => $commissionService->getVendorSummary($vendor),
        ]);
    }

    /**
     * Get package data for vendor.
     */
    public function packages(Request $request, Vendor $vendor)
    {
        $this->authorizePermission('vendors.view');

        $query = ShipmentItem::query()
            ->with(['shipment:id,shipment_number,vendor_id,status', 'deliveryRegion:id,name', 'deliveryDistrict:id,name'])
            ->whereHas('shipment', fn ($shipmentQuery) => $shipmentQuery->where('vendor_id', $vendor->id));

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('tracking_code', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                    ->orWhere('delivery_town', 'like', "%{$search}%")
                    ->orWhereHas('shipment', fn ($shipmentQuery) => $shipmentQuery->where('shipment_number', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($deliveryMethod = $request->get('delivery_method')) {
            $query->where(function ($q) use ($deliveryMethod) {
                if ($deliveryMethod === ShipmentItem::DELIVERY_METHOD_DIRECT) {
                    $q->where('delivery_method', ShipmentItem::DELIVERY_METHOD_DIRECT)
                        ->orWhereNull('delivery_method');
                } else {
                    $q->where('delivery_method', $deliveryMethod);
                }
            });
        }

        if ($recipientPhone = $request->get('recipient_phone')) {
            $query->where('delivery_recipient_phone', 'like', "%{$recipientPhone}%");
        }

        if ($location = $request->get('location')) {
            $query->where(function ($q) use ($location) {
                $q->where('delivery_town', 'like', "%{$location}%")
                    ->orWhereHas('deliveryRegion', fn ($regionQuery) => $regionQuery->where('name', 'like', "%{$location}%"))
                    ->orWhereHas('deliveryDistrict', fn ($districtQuery) => $districtQuery->where('name', 'like', "%{$location}%"));
            });
        }

        if ($quantityMin = $request->get('quantity_min')) {
            $query->where('quantity', '>=', (int) $quantityMin);
        }

        if ($quantityMax = $request->get('quantity_max')) {
            $query->where('quantity', '<=', (int) $quantityMax);
        }

        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['description', 'quantity', 'status', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest('created_at');
        }

        $perPage = min($request->get('per_page', 10), 50);
        $packages = $query->paginate($perPage);

        return response()->json([
            'data' => $packages->map(function (ShipmentItem $item) {
                $status = $item->status;
                $statusValue = $status?->value ?? (string) $item->getRawOriginal('status');

                return [
                    'id' => $item->id,
                    'shipment_id' => $item->shipment_id,
                    'shipment_number' => $item->shipment?->shipment_number,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'tracking_code' => $item->tracking_code,
                    'recipient_name' => $item->delivery_recipient_name,
                    'recipient_phone' => $item->delivery_recipient_phone,
                    'location' => collect([$item->delivery_town, $item->deliveryDistrict?->name, $item->deliveryRegion?->name])->filter()->implode(', '),
                    'delivery_method' => $item->delivery_method ?: ShipmentItem::DELIVERY_METHOD_DIRECT,
                    'delivery_method_label' => ($item->delivery_method ?: ShipmentItem::DELIVERY_METHOD_DIRECT) === ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF ? 'Bus handoff' : 'Recipient',
                    'status' => $statusValue,
                    'status_label' => method_exists($status, 'label') ? $status->label() : ucwords(str_replace('_', ' ', $statusValue)),
                    'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $packages->currentPage(),
                'from' => $packages->firstItem() ?? 0,
                'to' => $packages->lastItem() ?? 0,
                'total' => $packages->total(),
                'last_page' => $packages->lastPage(),
            ],
        ]);
    }

    /**
     * Get shipments data for vendor.
     */
    public function shipments(Request $request, Vendor $vendor)
    {
        $this->authorizePermission('vendors.view');

        $query = $vendor->shipments()->with(['region', 'district'])->withCount('items');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                    ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('delivery_recipient_name', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_phone', 'like', "%{$search}%");
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

        if ($recipientPhone = $request->get('recipient_phone')) {
            $query->where(function ($q) use ($recipientPhone) {
                $q->where('delivery_recipient_phone', 'like', "%{$recipientPhone}%")
                    ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('delivery_recipient_phone', 'like', "%{$recipientPhone}%"));
            });
        }

        if ($location = $request->get('location')) {
            $query->where(function ($q) use ($location) {
                $q->whereHas('region', fn ($regionQuery) => $regionQuery->where('name', 'like', "%{$location}%"))
                    ->orWhereHas('district', fn ($districtQuery) => $districtQuery->where('name', 'like', "%{$location}%"))
                    ->orWhereHas('items', function ($itemQuery) use ($location) {
                        $itemQuery->where('delivery_town', 'like', "%{$location}%")
                            ->orWhereHas('deliveryRegion', fn ($regionQuery) => $regionQuery->where('name', 'like', "%{$location}%"))
                            ->orWhereHas('deliveryDistrict', fn ($districtQuery) => $districtQuery->where('name', 'like', "%{$location}%"));
                    });
            });
        }

        if ($packageCount = $request->get('package_count')) {
            if ($packageCount === 'one') {
                $query->has('items', '=', 1);
            } elseif ($packageCount === 'multiple') {
                $query->has('items', '>', 1);
            }
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['shipment_number', 'delivery_recipient_name', 'status', 'created_at'];

        if ($sortBy === 'recipient_name') {
            $sortBy = 'delivery_recipient_name';
        }

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
                    'items_count' => $shipment->items_count,
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

        if ($status = $request->get('status')) {
            if ($status === 'verified') {
                $query->whereNotNull('verified_at');
            } elseif ($status === 'expired') {
                $query->whereNull('verified_at')->where('expires_at', '<', now());
            } elseif ($status === 'pending') {
                $query->whereNull('verified_at')->where('expires_at', '>=', now());
            }
        }

        // Date range filter
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($expiresFrom = $request->get('expires_from')) {
            $query->whereDate('expires_at', '>=', $expiresFrom);
        }

        if ($expiresTo = $request->get('expires_to')) {
            $query->whereDate('expires_at', '<=', $expiresTo);
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

        $query = $this->vendorReportQuery($request);
        $summary = $this->vendorReportSummary($query);

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = [
            'name',
            'business_name',
            'email',
            'phone',
            'created_at',
            'shipments_count',
            'delivered_shipments_count',
            'open_shipments_count',
            'cancelled_shipments_count',
            'last_shipment_at',
            'last_activity_at',
            'total_earnings',
            'unpaid_earnings',
            'available_balance',
            'pending_payouts',
            'total_paid',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $vendors = $query->paginate($perPage);

        $user = Auth::guard('admin')->user();
        $canManage = $user->hasPermission('vendors.edit');
        $canManagePayouts = $user->hasPermission('vendors.manage');
        $minPayout = app(VendorCommissionService::class)->getMinPayout();

        return response()->json([
            'data' => $vendors->map(function ($vendor) use ($canManage, $canManagePayouts, $minPayout) {
                $availableBalance = (float) ($vendor->available_balance ?? 0);
                $pendingPayouts = (float) ($vendor->pending_payouts ?? 0);

                return [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'business_name' => $vendor->business_name,
                    'email' => $vendor->email,
                    'phone' => $vendor->phone,
                    'payout_momo_network' => $vendor->payout_momo_network,
                    'payout_account_name' => $vendor->payout_account_name,
                    'payout_account_number' => $vendor->payout_account_number,
                    'payout_account_updated_at' => $vendor->payout_account_updated_at?->format('Y-m-d H:i:s'),
                    'payout_account' => $this->formatVendorPayoutAccount($vendor),
                    'is_active' => $vendor->is_active,
                    'is_deleted' => $vendor->trashed(),
                    'deleted_at' => $vendor->deleted_at?->format('Y-m-d H:i:s'),
                    'shipments_count' => (int) ($vendor->shipments_count ?? 0),
                    'delivered_shipments_count' => (int) ($vendor->delivered_shipments_count ?? 0),
                    'open_shipments_count' => (int) ($vendor->open_shipments_count ?? 0),
                    'cancelled_shipments_count' => (int) ($vendor->cancelled_shipments_count ?? 0),
                    'last_shipment_at' => $vendor->last_shipment_at ? date('Y-m-d H:i:s', strtotime($vendor->last_shipment_at)) : null,
                    'last_activity_at' => $vendor->last_activity_at ? date('Y-m-d H:i:s', strtotime($vendor->last_activity_at)) : null,
                    'has_push_token' => filled($vendor->fcm_token),
                    'total_earnings' => (float) ($vendor->total_earnings ?? 0),
                    'unpaid_earnings' => (float) ($vendor->unpaid_earnings ?? 0),
                    'available_balance' => $availableBalance,
                    'pending_payouts' => $pendingPayouts,
                    'total_paid' => (float) ($vendor->total_paid ?? 0),
                    'min_payout' => $minPayout,
                    'can_create_payout' => $canManagePayouts && $availableBalance >= $minPayout,
                    'payout_state' => $this->vendorPayoutState($availableBalance, $pendingPayouts, $minPayout),
                    'commission_rate_override' => $vendor->commission_rate_override !== null
                        ? (float) $vendor->commission_rate_override
                        : null,
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
                'summary' => $summary,
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
                $phone = PhoneHelper::format((string) $value);

                if (!$phone) {
                    $fail('Please enter a valid Ghana phone number.');
                    return;
                }

                if ($this->vendorPhoneExists($phone)) {
                    $fail('This phone number is already registered to another vendor.');
                }
            }],
            'is_active' => ['boolean'],
            'payout_momo_network' => ['nullable', 'string', Rule::in(['mtn', 'telecel', 'airteltigo'])],
            'payout_account_name' => ['nullable', 'string', 'max:255'],
            'payout_account_number' => ['nullable', 'string', 'max:20'],
        ]);
        $payoutAccount = $this->validatedPayoutAccount($validated);

        $phone = PhoneHelper::format($validated['phone']);

        $vendor = new Vendor();
        $vendor->name = $validated['name'];
        $vendor->business_name = $validated['business_name'] ?? null;
        $vendor->email = $validated['email'] ?? null;
        $vendor->phone = $phone;
        $vendor->is_active = $validated['is_active'] ?? true;
        $this->fillPayoutAccount($vendor, $payoutAccount);
        $vendor->save();

        return response()->json([
            'success' => true,
            'message' => 'Vendor created successfully.',
            'vendor' => $this->formatVendorForResponse($vendor),
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
                'payout_momo_network' => $vendor->payout_momo_network,
                'payout_account_name' => $vendor->payout_account_name,
                'payout_account_number' => $vendor->payout_account_number,
                'payout_account' => $this->formatVendorPayoutAccount($vendor),
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

        // Empty string on commission_rate_override means "inherit the global default".
        if ($request->exists('commission_rate_override') && $request->input('commission_rate_override') === '') {
            $request->merge(['commission_rate_override' => null]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('vendors')->ignore($vendor->id)],
            'phone' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) use ($vendor) {
                $phone = PhoneHelper::format((string) $value);

                if (!$phone) {
                    $fail('Please enter a valid Ghana phone number.');
                    return;
                }

                if ($this->vendorPhoneExists($phone, $vendor->id)) {
                    $fail('This phone number is already registered to another vendor.');
                }
            }],
            'is_active' => ['boolean'],
            'commission_rate_override' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'payout_momo_network' => ['nullable', 'string', Rule::in(['mtn', 'telecel', 'airteltigo'])],
            'payout_account_name' => ['nullable', 'string', 'max:255'],
            'payout_account_number' => ['nullable', 'string', 'max:20'],
        ]);
        $payoutAccount = $this->validatedPayoutAccount($validated);

        $vendor->name = $validated['name'];
        $vendor->business_name = $validated['business_name'] ?? null;
        $vendor->email = $validated['email'] ?? null;
        $vendor->phone = PhoneHelper::format($validated['phone']);
        $vendor->is_active = $validated['is_active'] ?? $vendor->is_active;

        if ($request->exists('commission_rate_override')) {
            $vendor->commission_rate_override = $validated['commission_rate_override'] ?? null;
        }

        $this->fillPayoutAccount($vendor, $payoutAccount);
        $vendor->save();

        return response()->json([
            'success' => true,
            'message' => 'Vendor updated successfully.',
            'vendor' => $this->formatVendorForResponse($vendor),
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

        // Revoke all API tokens when deactivating
        if (!$vendor->is_active) {
            $vendor->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => $vendor->is_active ? 'Vendor activated.' : 'Vendor deactivated.',
            'is_active' => $vendor->is_active,
        ]);
    }

    /**
     * Soft-delete a vendor.
     */
    public function destroy(Vendor $vendor)
    {
        $this->authorizePermission('vendors.delete');

        // Revoke all API tokens
        $vendor->tokens()->delete();

        // Mangle phone to free it for re-registration, clear FCM, deactivate
        $vendor->update([
            'phone'     => $vendor->phone . '_deleted_' . time(),
            'fcm_token' => null,
            'is_active' => false,
        ]);

        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor deleted successfully.',
        ]);
    }

    /**
     * Restore a soft-deleted vendor.
     */
    public function restore(Vendor $vendor)
    {
        $this->authorizePermission('vendors.edit');

        if (!$vendor->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor is not deleted.',
            ], 422);
        }

        // Recover the original phone by stripping the _deleted_ suffix
        $originalPhone = preg_replace('/_deleted_\d+$/', '', $vendor->phone);

        // Check if the phone is now taken by another active vendor
        $phoneTaken = $this->vendorPhoneExists($originalPhone, $vendor->id);

        if ($phoneTaken) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot restore: this phone number is now registered to another vendor.',
            ], 422);
        }

        $vendor->phone = PhoneHelper::format($originalPhone) ?: $originalPhone;
        $vendor->save();
        $vendor->restore();

        return response()->json([
            'success' => true,
            'message' => 'Vendor restored successfully.',
        ]);
    }

    private function vendorPhoneExists(string $phone, ?int $ignoreVendorId = null): bool
    {
        $formatted = PhoneHelper::format($phone);

        if (!$formatted) {
            return false;
        }

        $query = Vendor::withTrashed()
            ->whereIn('phone', $this->phoneLookupVariants($formatted));

        if ($ignoreVendorId !== null) {
            $query->where('id', '!=', $ignoreVendorId);
        }

        return $query->exists();
    }

    private function phoneLookupVariants(string $formattedPhone): array
    {
        $local = PhoneHelper::toLocal($formattedPhone);
        $withoutPlus = ltrim($formattedPhone, '+');
        $withoutCountry = str_starts_with($formattedPhone, '+233') ? substr($formattedPhone, 4) : null;

        return collect([$formattedPhone, $withoutPlus, $local, $withoutCountry])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function openShipmentStatuses(): array
    {
        return collect(ShipmentStatus::cases())
            ->reject(fn (ShipmentStatus $status) => in_array($status, [ShipmentStatus::DELIVERED, ShipmentStatus::CANCELLED], true))
            ->map(fn (ShipmentStatus $status) => $status->value)
            ->all();
    }

    private function vendorReportSummary(Builder $query): array
    {
        $vendorIds = (clone $query)->select('vendors.id');
        $minPayout = app(VendorCommissionService::class)->getMinPayout();

        return [
            'total_vendors' => (clone $query)->count(),
            'active_vendors' => (clone $query)->where('is_active', true)->count(),
            'vendors_with_shipments' => (clone $query)->has('shipments')->count(),
            'eligible_payout_vendors' => (clone $query)->whereRaw(
                '(select coalesce(sum(amount), 0) from vendor_earnings where vendor_earnings.vendor_id = vendors.id and status = ? and payout_id is null) >= ?',
                [VendorEarning::STATUS_APPROVED, $minPayout]
            )->count(),
            'open_shipments' => Shipment::query()
                ->whereIn('vendor_id', clone $vendorIds)
                ->whereIn('status', $this->openShipmentStatuses())
                ->count(),
            'delivered_shipments' => Shipment::query()
                ->whereIn('vendor_id', clone $vendorIds)
                ->where('status', ShipmentStatus::DELIVERED->value)
                ->count(),
            'unpaid_earnings' => (float) VendorEarning::query()
                ->whereIn('vendor_id', clone $vendorIds)
                ->where('status', VendorEarning::STATUS_APPROVED)
                ->sum('amount'),
            'available_balance' => (float) VendorEarning::query()
                ->whereIn('vendor_id', clone $vendorIds)
                ->where('status', VendorEarning::STATUS_APPROVED)
                ->whereNull('payout_id')
                ->sum('amount'),
            'pending_payouts' => (float) VendorPayout::query()
                ->whereIn('vendor_id', clone $vendorIds)
                ->where('status', VendorPayout::STATUS_PENDING)
                ->sum('amount'),
            'total_paid' => (float) VendorPayout::query()
                ->whereIn('vendor_id', clone $vendorIds)
                ->whereIn('status', [VendorPayout::STATUS_SENT, VendorPayout::STATUS_CONFIRMED])
                ->sum('amount'),
        ];
    }

    private function vendorPayoutState(float $availableBalance, float $pendingPayouts, float $minPayout): string
    {
        if ($pendingPayouts > 0) {
            return 'pending';
        }

        if ($availableBalance >= $minPayout) {
            return 'eligible';
        }

        if ($availableBalance > 0) {
            return 'below_minimum';
        }

        return 'no_balance';
    }

    private function vendorReportQuery(Request $request): Builder
    {
        $openStatuses = $this->openShipmentStatuses();

        $query = Vendor::query()
            ->withCount([
                'shipments',
                'shipments as delivered_shipments_count' => fn (Builder $q) => $q->where('status', ShipmentStatus::DELIVERED->value),
                'shipments as cancelled_shipments_count' => fn (Builder $q) => $q->where('status', ShipmentStatus::CANCELLED->value),
                'shipments as open_shipments_count' => fn (Builder $q) => $q->whereIn('status', $openStatuses),
            ])
            ->withMax('shipments as last_shipment_at', 'created_at')
            ->withMax('activityLogs as last_activity_at', 'created_at')
            ->withSum('earnings as total_earnings', 'amount')
            ->withSum(['earnings as unpaid_earnings' => fn (Builder $q) => $q->where('status', VendorEarning::STATUS_APPROVED)], 'amount')
            ->withSum(['earnings as available_balance' => fn (Builder $q) => $q
                ->where('status', VendorEarning::STATUS_APPROVED)
                ->whereNull('payout_id')], 'amount')
            ->withSum(['payouts as pending_payouts' => fn (Builder $q) => $q->where('status', VendorPayout::STATUS_PENDING)], 'amount')
            ->withSum(['payouts as total_paid' => fn (Builder $q) => $q->whereIn('status', [VendorPayout::STATUS_SENT, VendorPayout::STATUS_CONFIRMED])], 'amount');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->get('status') !== '') {
            $status = $request->get('status');
            if ($status === 'deleted') {
                $query->onlyTrashed();
            } elseif ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $this->applyNullablePresenceFilter($query, $request->get('has_email'), 'email');
        $this->applyNullablePresenceFilter($query, $request->get('has_business_name'), 'business_name');
        $this->applyNullablePresenceFilter($query, $request->get('has_push_token'), 'fcm_token');

        if ($request->get('has_commission_override') === 'yes') {
            $query->whereNotNull('commission_rate_override');
        } elseif ($request->get('has_commission_override') === 'no') {
            $query->whereNull('commission_rate_override');
        }

        if ($request->filled('commission_min')) {
            $commissionMin = $request->get('commission_min');
            $query->where('commission_rate_override', '>=', (float) $commissionMin);
        }

        if ($request->filled('commission_max')) {
            $commissionMax = $request->get('commission_max');
            $query->where('commission_rate_override', '<=', (float) $commissionMax);
        }

        if ($request->filled('shipment_count_min')) {
            $shipmentMin = $request->get('shipment_count_min');
            $query->has('shipments', '>=', (int) $shipmentMin);
        }

        if ($request->filled('shipment_count_max')) {
            $shipmentMax = $request->get('shipment_count_max');
            $query->has('shipments', '<=', (int) $shipmentMax);
        }

        if ($shipmentStatus = $request->get('shipment_status')) {
            $query->whereHas('shipments', fn (Builder $q) => $q->where('status', $shipmentStatus));
        }

        if ($shipmentSource = $request->get('shipment_source')) {
            $query->whereHas('shipments', fn (Builder $q) => $q->where('source', $shipmentSource));
        }

        if ($destinationMode = $request->get('destination_mode')) {
            $query->whereHas('shipments', fn (Builder $q) => $q->where('destination_mode', $destinationMode));
        }

        if ($lastShipmentFrom = $request->get('last_shipment_from')) {
            $query->whereHas('shipments', fn (Builder $q) => $q->whereDate('created_at', '>=', $lastShipmentFrom));
        }

        if ($lastShipmentTo = $request->get('last_shipment_to')) {
            $query->whereHas('shipments', fn (Builder $q) => $q->whereDate('created_at', '<=', $lastShipmentTo));
        }

        if ($request->get('activity_state') === 'never_logged_in') {
            $query->whereDoesntHave('activityLogs', fn (Builder $q) => $q->where('action', 'login'));
        } elseif ($request->get('activity_state') === 'has_activity') {
            $query->whereHas('activityLogs');
        }

        if ($lastActivityFrom = $request->get('last_activity_from')) {
            $query->whereHas('activityLogs', fn (Builder $q) => $q->whereDate('created_at', '>=', $lastActivityFrom));
        }

        if ($lastActivityTo = $request->get('last_activity_to')) {
            $query->whereHas('activityLogs', fn (Builder $q) => $q->whereDate('created_at', '<=', $lastActivityTo));
        }

        if ($earningsStatus = $request->get('earnings_status')) {
            if ($earningsStatus === 'none') {
                $query->whereDoesntHave('earnings');
            } else {
                $query->whereHas('earnings', fn (Builder $q) => $q->where('status', $earningsStatus));
            }
        }

        if ($payoutStatus = $request->get('payout_status')) {
            if ($payoutStatus === 'none') {
                $query->whereDoesntHave('payouts');
            } else {
                $query->whereHas('payouts', fn (Builder $q) => $q->where('status', $payoutStatus));
            }
        }

        $minPayout = app(VendorCommissionService::class)->getMinPayout();
        $availableBalanceSql = '(select coalesce(sum(amount), 0) from vendor_earnings where vendor_earnings.vendor_id = vendors.id and status = ? and payout_id is null)';
        $pendingPayoutSql = '(select coalesce(sum(amount), 0) from vendor_payouts where vendor_payouts.vendor_id = vendors.id and status = ?)';

        if ($payoutState = $request->get('payout_state')) {
            if ($payoutState === 'eligible') {
                $query->whereRaw("{$availableBalanceSql} >= ?", [VendorEarning::STATUS_APPROVED, $minPayout]);
            } elseif ($payoutState === 'below_minimum') {
                $query->whereRaw("{$availableBalanceSql} > 0 and {$availableBalanceSql} < ?", [
                    VendorEarning::STATUS_APPROVED,
                    VendorEarning::STATUS_APPROVED,
                    $minPayout,
                ]);
            } elseif ($payoutState === 'no_balance') {
                $query->whereRaw("{$availableBalanceSql} = 0", [VendorEarning::STATUS_APPROVED]);
            } elseif ($payoutState === 'pending') {
                $query->whereRaw("{$pendingPayoutSql} > 0", [VendorPayout::STATUS_PENDING]);
            }
        }

        if ($request->filled('available_balance_min')) {
            $query->whereRaw("{$availableBalanceSql} >= ?", [VendorEarning::STATUS_APPROVED, (float) $request->get('available_balance_min')]);
        }

        if ($request->filled('available_balance_max')) {
            $query->whereRaw("{$availableBalanceSql} <= ?", [VendorEarning::STATUS_APPROVED, (float) $request->get('available_balance_max')]);
        }

        if ($request->get('pending_payout_state') === 'has_pending') {
            $query->whereRaw("{$pendingPayoutSql} > 0", [VendorPayout::STATUS_PENDING]);
        } elseif ($request->get('pending_payout_state') === 'no_pending') {
            $query->whereRaw("{$pendingPayoutSql} = 0", [VendorPayout::STATUS_PENDING]);
        }

        if ($request->filled('unpaid_earnings_min')) {
            $query->whereRaw(
                '(select coalesce(sum(amount), 0) from vendor_earnings where vendor_earnings.vendor_id = vendors.id and status = ?) >= ?',
                [VendorEarning::STATUS_APPROVED, (float) $request->get('unpaid_earnings_min')]
            );
        }

        if ($request->filled('unpaid_earnings_max')) {
            $query->whereRaw(
                '(select coalesce(sum(amount), 0) from vendor_earnings where vendor_earnings.vendor_id = vendors.id and status = ?) <= ?',
                [VendorEarning::STATUS_APPROVED, (float) $request->get('unpaid_earnings_max')]
            );
        }

        return $query;
    }

    private function applyNullablePresenceFilter(Builder $query, mixed $value, string $column): void
    {
        if ($value === 'yes') {
            $query->whereNotNull($column)->where($column, '!=', '');
        } elseif ($value === 'no') {
            $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, ''));
        }
    }

    /**
     * Export vendors data.
     */
    public function export(Request $request)
    {
        $this->authorizePermission('vendors.view');

        $query = $this->vendorReportQuery($request);

        $vendors = $query->orderBy('created_at', 'desc')->get();

        $rows = $vendors->map(function ($vendor) {
            $status = $vendor->trashed() ? 'Deleted' : ($vendor->is_active ? 'Active' : 'Inactive');

            return [
                'ID' => $vendor->id,
                'Name' => $vendor->name,
                'Business Name' => $vendor->business_name,
                'Email' => $vendor->email,
                'Phone' => $vendor->phone,
                'Status' => $status,
                'Has Email' => $vendor->email ? 'Yes' : 'No',
                'Has Business Name' => $vendor->business_name ? 'Yes' : 'No',
                'Push Ready' => $vendor->fcm_token ? 'Yes' : 'No',
                'Shipment Count' => (int) ($vendor->shipments_count ?? 0),
                'Delivered Shipments' => (int) ($vendor->delivered_shipments_count ?? 0),
                'Open Shipments' => (int) ($vendor->open_shipments_count ?? 0),
                'Cancelled Shipments' => (int) ($vendor->cancelled_shipments_count ?? 0),
                'Last Shipment At' => $vendor->last_shipment_at ? date('Y-m-d H:i:s', strtotime($vendor->last_shipment_at)) : '',
                'Last Activity At' => $vendor->last_activity_at ? date('Y-m-d H:i:s', strtotime($vendor->last_activity_at)) : '',
                'Commission Override' => $vendor->commission_rate_override,
                'Total Earnings' => (float) ($vendor->total_earnings ?? 0),
                'Unpaid Earnings' => (float) ($vendor->unpaid_earnings ?? 0),
                'Available Balance' => (float) ($vendor->available_balance ?? 0),
                'Pending Payouts' => (float) ($vendor->pending_payouts ?? 0),
                'Total Paid' => (float) ($vendor->total_paid ?? 0),
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
        return GenericPdfExporter::download($rows, $filename, 'Vendors List');
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

    protected function validatedPayoutAccount(array $data): ?array
    {
        $network = $data['payout_momo_network'] ?? null;
        $name = trim((string) ($data['payout_account_name'] ?? ''));
        $number = trim((string) ($data['payout_account_number'] ?? ''));
        $hasAny = filled($network) || $name !== '' || $number !== '';

        if (!$hasAny) {
            return null;
        }

        if (!filled($network) || $name === '' || $number === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'payout_account_number' => ['Complete the MoMo network, account name, and account number.'],
            ]);
        }

        $local = PhoneHelper::toLocal($number);
        if (!$local || !preg_match('/^0(?:2\d|5\d)\d{7}$/', $local)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'payout_account_number' => ['Enter a valid 10-digit Ghana phone number'],
            ]);
        }

        return [
            'network' => $network,
            'name' => $name,
            'number' => PhoneHelper::format($number),
        ];
    }

    protected function fillPayoutAccount(Vendor $vendor, ?array $account): void
    {
        $before = [
            $vendor->payout_momo_network,
            $vendor->payout_account_name,
            $vendor->payout_account_number,
        ];

        $vendor->payout_momo_network = $account['network'] ?? null;
        $vendor->payout_account_name = $account['name'] ?? null;
        $vendor->payout_account_number = $account['number'] ?? null;

        $after = [
            $vendor->payout_momo_network,
            $vendor->payout_account_name,
            $vendor->payout_account_number,
        ];

        if ($before !== $after) {
            $vendor->payout_account_updated_at = $account ? now() : null;
        }
    }

    protected function formatVendorPayoutAccount(Vendor $vendor): array
    {
        $isSet = filled($vendor->payout_momo_network)
            && filled($vendor->payout_account_name)
            && filled($vendor->payout_account_number);

        return [
            'is_set' => $isSet,
            'method' => 'momo',
            'network' => $vendor->payout_momo_network,
            'account_name' => $vendor->payout_account_name,
            'account_number' => $vendor->payout_account_number,
            'updated_at' => $vendor->payout_account_updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function formatVendorForResponse(Vendor $vendor): array
    {
        return array_merge($vendor->toArray(), [
            'payout_account' => $this->formatVendorPayoutAccount($vendor),
        ]);
    }
}
