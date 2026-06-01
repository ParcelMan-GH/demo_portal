<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorEarning;
use App\Models\VendorPayout;
use App\Services\VendorCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorPayoutController extends Controller
{
    protected VendorCommissionService $commissionService;

    public function __construct(VendorCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Display the vendor payouts management page.
     */
    public function index()
    {
        $this->authorizePermission('vendors.manage');

        $payoutConfig = [
            'dataUrl' => route('admin.vendor-payouts.data'),
            'createPayoutUrl' => route('admin.vendors.payouts.store', ['vendor' => '__VENDOR__']),
            'vendorPayoutsUrl' => route('admin.vendors.payouts-data', ['vendor' => '__VENDOR__']),
            'markSentUrl' => route('admin.vendor-payouts.mark-sent', ['payout' => '__PAYOUT__']),
            'confirmUrl' => route('admin.vendor-payouts.confirm', ['payout' => '__PAYOUT__']),
        ];

        return view('admin.vendors.payouts', compact('payoutConfig'));
    }

    /**
     * AJAX: Return paginated vendor balance data.
     */
    public function data(Request $request)
    {
        $this->authorizePermission('vendors.manage');

        $perPage = (int) $request->get('per_page', 15);
        $page = (int) $request->get('page', 1);
        $search = $request->get('search');
        $sort = $request->get('sort', 'available_balance');
        $sortDir = $request->get('sort_dir', 'desc');
        $minPayout = $this->commissionService->getMinPayout();

        // Build the query with aggregated earnings and payouts
        $query = Vendor::query()
            ->select([
                'vendors.id as vendor_id',
                'vendors.name as vendor_name',
                'vendors.phone as vendor_phone',
                'vendors.business_name',
                'vendors.payout_momo_network',
                'vendors.payout_account_name',
                'vendors.payout_account_number',
                'vendors.payout_account_updated_at',
                DB::raw('COALESCE(earnings_agg.total_earned, 0) as total_earned'),
                DB::raw('COALESCE(earnings_agg.available_balance, 0) as available_balance'),
                DB::raw('COALESCE(payouts_agg.total_paid, 0) as total_paid'),
                DB::raw('COALESCE(payouts_agg.pending_payouts, 0) as pending_payouts'),
            ])
            ->leftJoinSub(
                VendorEarning::query()
                    ->select([
                        'vendor_id',
                        DB::raw('SUM(amount) as total_earned'),
                        DB::raw('SUM(CASE WHEN status = \'' . VendorEarning::STATUS_APPROVED . '\' AND payout_id IS NULL THEN amount ELSE 0 END) as available_balance'),
                    ])
                    ->groupBy('vendor_id'),
                'earnings_agg',
                'vendors.id',
                '=',
                'earnings_agg.vendor_id'
            )
            ->leftJoinSub(
                VendorPayout::query()
                    ->select([
                        'vendor_id',
                        DB::raw('SUM(CASE WHEN status IN (\'' . VendorPayout::STATUS_SENT . '\', \'' . VendorPayout::STATUS_CONFIRMED . '\') THEN amount ELSE 0 END) as total_paid'),
                        DB::raw('SUM(CASE WHEN status = \'' . VendorPayout::STATUS_PENDING . '\' THEN amount ELSE 0 END) as pending_payouts'),
                    ])
                    ->groupBy('vendor_id'),
                'payouts_agg',
                'vendors.id',
                '=',
                'payouts_agg.vendor_id'
            )
            ->whereNull('vendors.deleted_at');

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('vendors.name', 'like', "%{$search}%")
                    ->orWhere('vendors.phone', 'like', "%{$search}%")
                    ->orWhere('vendors.business_name', 'like', "%{$search}%")
                    ->orWhere('vendors.payout_account_name', 'like', "%{$search}%")
                    ->orWhere('vendors.payout_account_number', 'like', "%{$search}%");
            });
        }

        // This is the commission payouts worklist, so only vendors with accrued
        // unpaid commission belong here.
        $query->having('available_balance', '>', 0);

        // Status filter
        $status = $request->get('status');
        if ($status === 'ready') {
            $query->having('available_balance', '>=', $minPayout);
        } elseif ($status === 'below_minimum') {
            $query->having('available_balance', '>', 0)
                ->having('available_balance', '<', $minPayout);
        }

        $payoutAccount = $request->get('payout_account');
        if ($payoutAccount === 'set') {
            $query->whereNotNull('vendors.payout_momo_network')
                ->whereNotNull('vendors.payout_account_name')
                ->whereNotNull('vendors.payout_account_number');
        } elseif ($payoutAccount === 'missing') {
            $query->where(function ($q) {
                $q->whereNull('vendors.payout_momo_network')
                    ->orWhereNull('vendors.payout_account_name')
                    ->orWhereNull('vendors.payout_account_number');
            });
        }

        // Sorting
        $allowedSorts = ['available_balance', 'total_earned', 'total_paid', 'vendor_name'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('available_balance', 'desc');
        }

        $summaryRows = (clone $query)->get();
        $total = $summaryRows->count(); // Count after having clauses
        $readyCount = $summaryRows->filter(fn ($row) => (float) $row->available_balance >= $minPayout)->count();
        $summary = [
            'vendors_with_balance' => $total,
            'ready_to_pay' => $readyCount,
            'below_minimum' => max($total - $readyCount, 0),
            'available_balance' => (float) $summaryRows->sum('available_balance'),
            'minimum_payout' => (float) $minPayout,
        ];
        $skip = ($page - 1) * $perPage;
        $items = $query->skip($skip)->take($perPage)->get();

        $data = $items->map(function ($row) use ($minPayout) {
            return [
                'vendor_id' => $row->vendor_id,
                'vendor_name' => $row->vendor_name,
                'vendor_phone' => $row->vendor_phone,
                'business_name' => $row->business_name,
                'payout_account' => $this->formatPayoutAccount($row),
                'total_earned' => (float) $row->total_earned,
                'available_balance' => (float) $row->available_balance,
                'total_paid' => (float) $row->total_paid,
                'pending_payouts' => (float) $row->pending_payouts,
                'can_payout' => (float) $row->available_balance >= $minPayout,
                'payout_state' => (float) $row->available_balance >= $minPayout ? 'ready' : 'below_minimum',
            ];
        });

        return response()->json([
            'data' => $data,
            'summary' => $summary,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $total > 0 ? $skip + 1 : null,
                'to' => $total > 0 ? min($skip + $perPage, $total) : null,
            ],
        ]);
    }

    /**
     * AJAX: Return payout history for a specific vendor.
     */
    public function vendorPayouts(Request $request, Vendor $vendor)
    {
        $this->authorizePermission('vendors.manage');

        $perPage = (int) $request->get('per_page', 15);
        $page = (int) $request->get('page', 1);
        $skip = ($page - 1) * $perPage;

        $query = VendorPayout::where('vendor_id', $vendor->id)
            ->with('processedBy:id,name')
            ->orderBy('created_at', 'desc');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhere('payment_phone', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($method = $request->get('method')) {
            $query->where('payment_method', $method);
        }

        $total = $query->count();
        $payouts = $query->skip($skip)->take($perPage)->get();

        $data = $payouts->map(function (VendorPayout $payout) {
            return [
                'id' => $payout->id,
                'amount' => (float) $payout->amount,
                'status' => $payout->status,
                'payment_method' => $payout->payment_method,
                'payment_phone' => $payout->payment_phone,
                'payment_reference' => $payout->payment_reference,
                'notes' => $payout->notes,
                'processed_by' => $payout->processedBy ? [
                    'id' => $payout->processedBy->id,
                    'name' => $payout->processedBy->name,
                ] : null,
                'sent_at' => $payout->sent_at?->toIso8601String(),
                'confirmed_at' => $payout->confirmed_at?->toIso8601String(),
                'created_at' => $payout->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $total > 0 ? $skip + 1 : null,
                'to' => $total > 0 ? min($skip + $perPage, $total) : null,
            ],
        ]);
    }

    /**
     * Create a new payout for a vendor.
     */
    public function store(Request $request, Vendor $vendor)
    {
        $this->authorizePermission('vendors.manage');

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:momo,bank,cash',
            'payment_phone' => 'nullable|string|max:20',
            'payment_reference' => [$request->boolean('confirm_immediately') ? 'required' : 'nullable', 'string', 'max:255'],
            'confirm_immediately' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $admin = Auth::guard('admin')->user();

        $result = $this->commissionService->createPayout(
            $vendor,
            (float) $request->amount,
            $admin->id,
            [
                'payment_method' => $request->payment_method,
                'payment_phone' => $request->payment_phone,
                'payment_reference' => $request->payment_reference,
                'confirm_immediately' => $request->boolean('confirm_immediately'),
                'notes' => $request->notes,
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Mark a payout as sent.
     */
    public function markSent(Request $request, VendorPayout $payout)
    {
        $this->authorizePermission('vendors.manage');

        $request->validate([
            'payment_reference' => 'required|string|max:255',
        ]);

        $admin = Auth::guard('admin')->user();

        $result = $this->commissionService->markPayoutSent($payout, $request->payment_reference, $admin->id);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Confirm a payout.
     */
    public function confirm(VendorPayout $payout)
    {
        $this->authorizePermission('vendors.manage');

        $result = $this->commissionService->confirmPayout($payout);

        return response()->json($result, $result['success'] ? 200 : 422);
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

    protected function formatPayoutAccount($vendor): array
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
            'updated_at' => $vendor->payout_account_updated_at,
        ];
    }
}
