<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\WalkinShipmentService;
use App\Services\Warehouse\WarehousePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WalkinController extends Controller
{
    public function __construct(private WarehousePortalService $portalService) {}

    public function create(): View
    {
        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);

        return view('warehouse.walkin.create', compact('warehouse'));
    }

    public function store(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);

        $validated = $request->validate([
            'vendor_id'                          => 'required|exists:vendors,id',
            'fulfillment_type'                   => 'nullable|in:warehouse,self_pickup,direct',
            'destination_mode'                   => 'required|in:single,per_item',
            'items'                              => 'required|array|min:1',
            'items.*.description'                => 'required|string|max:500',
            'items.*.quantity'                    => 'required|integer|min:1',
            'items.*.delivery.recipient_name'    => 'required_if:destination_mode,per_item|nullable|string|max:255',
            'items.*.delivery.recipient_phone'   => 'required_if:destination_mode,per_item|nullable|string|max:20',
            'items.*.delivery.region_id'         => 'required_if:destination_mode,per_item|nullable|integer',
            'items.*.delivery.district_id'       => 'required_if:destination_mode,per_item|nullable|integer',
            'items.*.delivery.town'              => 'nullable|string|max:255',
            'items.*.delivery.landmark'          => 'nullable|string|max:255',
            'items.*.delivery.instructions'      => 'nullable|string|max:1000',
            'delivery.recipient_name'            => 'required_if:destination_mode,single|nullable|string|max:255',
            'delivery.recipient_phone'           => 'required_if:destination_mode,single|nullable|string|max:20',
            'delivery.region_id'                 => 'required_if:destination_mode,single|nullable|integer',
            'delivery.district_id'               => 'required_if:destination_mode,single|nullable|integer',
            'delivery.town'                      => 'nullable|string|max:255',
            'delivery.landmark'                  => 'nullable|string|max:255',
            'delivery.instructions'              => 'nullable|string|max:1000',
        ]);

        $validated['warehouse_id']       = $warehouse->id;
        $validated['source']             = 'warehouse_walkin';
        $validated['created_by_user_id'] = $user->id;

        $result = $service->createWalkinShipment($validated);

        return response()->json([
            'success'  => true,
            'message'  => 'Walk-in shipment created successfully.',
            'redirect' => route('warehouse.receipts.pending.index'),
        ]);
    }

    public function vendorLookup(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $request->validate(['phone' => 'required|string|min:9']);

        $vendor = $service->lookupVendor($request->get('phone'));

        return response()->json([
            'found'  => $vendor !== null,
            'vendor' => $vendor ? [
                'id'            => $vendor->id,
                'name'          => $vendor->name,
                'business_name' => $vendor->business_name,
                'phone'         => $vendor->phone,
                'email'         => $vendor->email,
                'is_active'     => $vendor->is_active,
            ] : null,
        ]);
    }

    public function vendorCreate(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'phone'         => 'required|string|min:9|unique:vendors,phone',
            'email'         => 'nullable|email|unique:vendors,email',
        ]);

        $vendor = $service->createVendorInline($validated);

        return response()->json([
            'success' => true,
            'vendor'  => [
                'id'            => $vendor->id,
                'name'          => $vendor->name,
                'business_name' => $vendor->business_name,
                'phone'         => $vendor->phone,
                'email'         => $vendor->email,
                'is_active'     => $vendor->is_active,
            ],
        ]);
    }

    public function locationSearch(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['locations' => []]);
        }

        $locations = Location::where('is_active', true)
            ->with(['district:id,name', 'region:id,name'])
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', $q . '%')
                      ->orWhere('name', 'like', '% ' . $q . '%');
            })
            ->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", [$q . '%'])
            ->orderBy('name')
            ->limit(12)
            ->get();

        return response()->json([
            'locations' => $locations->map(fn ($l) => [
                'id'       => $l->id,
                'name'     => $l->name,
                'district' => ['id' => $l->district->id, 'name' => $l->district->name],
                'region'   => ['id' => $l->region->id, 'name' => $l->region->name],
                'display'  => "{$l->name}, {$l->district->name}, {$l->region->name}",
            ]),
        ]);
    }
}
