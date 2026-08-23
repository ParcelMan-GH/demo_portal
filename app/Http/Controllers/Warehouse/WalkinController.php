<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Warehouse;
use App\Services\WalkinShipmentService;
use App\Services\Warehouse\WarehousePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class WalkinController extends Controller
{
    public function __construct(private WarehousePortalService $portalService) {}

    public function create(): View
    {
        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);

        $transferWarehouses = Warehouse::query()
            ->where('is_active', true)
            ->whereKeyNot($warehouse->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Warehouse $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
            ])
            ->values();

        // ── DASHBOARD METRICS CALCULATION ─────────────────────────────────
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $monthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $calcChange = function ($current, $previous) {
            if ($previous == 0) return $current > 0 ? 100 : 0;
            return round((($current - $previous) / $previous) * 100, 2);
        };

        // 1. Total Walk-ins (This Month)
        $totalWalkinsMonth = Shipment::where('source', 'warehouse_walkin')->where('created_at', '>=', $monthStart)->count();
        $lastMonthWalkins = Shipment::where('source', 'warehouse_walkin')->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $totalWalkinsChange = $calcChange($totalWalkinsMonth, $lastMonthWalkins);

        // 2. Today Walk-ins
        $todayWalkins = Shipment::where('source', 'warehouse_walkin')->whereDate('created_at', $today)->count();
        $yesterdayWalkins = Shipment::where('source', 'warehouse_walkin')->whereDate('created_at', $yesterday)->count();
        $todayWalkinsChange = $calcChange($todayWalkins, $yesterdayWalkins);

        // 3. Amount Made (Sum of Delivery Fees for Walk-ins)
        $amountMadeMonth = ShipmentItem::whereHas('shipment', function($q) use ($monthStart) {
            $q->where('source', 'warehouse_walkin')->where('created_at', '>=', $monthStart);
        })->sum('delivery_fee');

        $lastMonthAmount = ShipmentItem::whereHas('shipment', function($q) use ($lastMonthStart, $lastMonthEnd) {
            $q->where('source', 'warehouse_walkin')->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd]);
        })->sum('delivery_fee');
        $amountMadeChange = $calcChange($amountMadeMonth, $lastMonthAmount);

        // 4. On Time Delivery
        $deliveredThisMonth = Shipment::where('source', 'warehouse_walkin')->where('status', 'delivered')->where('updated_at', '>=', $monthStart)->count();
        $onTimeThisMonth = Shipment::where('source', 'warehouse_walkin')->where('status', 'delivered')
            ->where('updated_at', '>=', $monthStart)
            ->where(function ($query) {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $query->whereRaw('(julianday(updated_at) - julianday(created_at)) <= 2');
                } else {
                    $query->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 48');
                }
            })->count();

        $onTimeDeliveryRate = $deliveredThisMonth > 0 ? round(($onTimeThisMonth / $deliveredThisMonth) * 100, 1) : 100;
        $onTimeChange = 0; // Mocked historical SLA change

        // ── RECENT WALKINS ────────────────────────────────────────────────
        $recentWalkins = Shipment::query()
            ->where('source', 'warehouse_walkin')
            ->with(['vendor', 'items'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'shipment_number' => $shipment->shipment_number,
                    'vendor' => $shipment->vendor ? [
                        'id' => $shipment->vendor->id,
                        'name' => $shipment->vendor->name,
                        'phone' => $shipment->vendor->phone,
                    ] : null,
                    'items_count' => $shipment->items->count(),
                    'items' => $shipment->items->map(fn ($item) => [
                        'id' => $item->id,
                        'delivery_fee' => (float) $item->delivery_fee,
                    ]),
                    'total_fee' => (float) $shipment->items->sum('delivery_fee'),
                    'status' => is_object($shipment->status) && property_exists($shipment->status, 'value') 
                        ? $shipment->status->value 
                        : (string) $shipment->status,
                    'time_formatted' => $shipment->created_at ? $shipment->created_at->format('h:i A') : 'Just now',
                ];
            });

        return view('warehouse.walkin.create', compact(
            'warehouse', 'transferWarehouses', 'recentWalkins',
            'totalWalkinsMonth', 'totalWalkinsChange', 'todayWalkins', 
            'todayWalkinsChange', 'amountMadeMonth', 'amountMadeChange', 
            'onTimeDeliveryRate', 'onTimeChange'
        ));
    }

    public function store(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);
        $ghanaPhoneRule = function (string $attribute, mixed $value, \Closure $fail): void {
            if (blank($value)) {
                return;
            }

            if (!PhoneHelper::hasValidPrefix((string) $value)) {
                $fail('Please enter a valid Ghana phone number.');
            }
        };

        // 1. Extract items from JSON or raw POST array
        $rawItems = [];
        if ($request->filled('items_json')) {
            $decoded = json_decode((string) $request->input('items_json'), true);
            $rawItems = is_array($decoded) ? $decoded : [];
        } elseif (is_array($request->input('items'))) {
            $rawItems = $request->input('items');
        }

        // 2. Normalize delivery_fee key across every possible frontend naming convention
        $normalizedItems = array_map(function (array $item) {
            $fee = $item['delivery_fee'] 
                ?? $item['price'] 
                ?? $item['fee'] 
                ?? $item['amount'] 
                ?? $item['delivery_price']
                ?? ($item['delivery']['fee'] ?? null)
                ?? ($item['delivery']['price'] ?? null);

            $item['delivery_fee'] = filled($fee) ? (float) $fee : 0.00;
            return $item;
        }, $rawItems);

        if (!empty($normalizedItems)) {
            $request->merge(['items' => $normalizedItems]);
        }

        // 3. Validate request
        $validated = $request->validate([
            'vendor_id'                  => 'required|exists:vendors,id',
            'fulfillment_type'           => 'nullable|in:warehouse,self_pickup,direct',
            'delivery_preference'        => 'nullable|in:deliver,self_pickup',
            'destination_mode'           => 'required|in:single,per_item',
            'items'                      => 'required|array|min:1',
            'items.*.description'        => 'required|string|max:500',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.delivery_fee'       => 'nullable|numeric|min:0|max:9999999.99',
            'items.*.price'              => 'nullable|numeric|min:0|max:9999999.99',
            'items.*.fee'                => 'nullable|numeric|min:0|max:9999999.99',
            'items.*.delivery_method'    => 'nullable|in:direct,bus_handoff',
            'items.*.forward_to_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'items.*.delivery.recipient_name' => 'required_if:destination_mode,per_item|nullable|string|max:255',
            'items.*.delivery.recipient_phone'=> ['required_if:destination_mode,per_item', 'nullable', 'string', 'max:20', $ghanaPhoneRule],
            'items.*.delivery.region_id'      => 'nullable|integer',
            'items.*.delivery.district_id'    => 'nullable|integer',
            'items.*.delivery.town'           => 'nullable|string|max:255',
            'items.*.delivery.landmark'       => 'nullable|string|max:255',
            'items.*.delivery.instructions'   => 'nullable|string|max:1000',
            'delivery.recipient_name'         => 'required_if:destination_mode,single|nullable|string|max:255',
            'delivery.recipient_phone'        => ['required_if:destination_mode,single', 'nullable', 'string', 'max:20', $ghanaPhoneRule],
            'delivery.region_id'              => 'nullable|integer',
            'delivery.district_id'            => 'nullable|integer',
            'delivery.town'                   => 'required_if:destination_mode,single|nullable|string|max:255',
            'delivery.landmark'               => 'nullable|string|max:255',
            'delivery.instructions'           => 'nullable|string|max:1000',
            'pickup_fee_amount'               => 'nullable|numeric|min:0|max:9999999.99',
            'item_photos'                     => 'nullable|array',
            'item_photos.*'                   => 'nullable|array',
            'item_photos.*.*'                 => 'file|image|max:12288',
        ]);

        $validated['warehouse_id']       = $warehouse->id;
        $validated['source']             = 'warehouse_walkin';
        $validated['created_by_user_id'] = $user->id;
        $validated['item_photos']        = $request->file('item_photos', []);

        // 4. Force delivery_fee persistence into $validated['items'] array
        $validated['items'] = collect($validated['items'])->map(function (array $item, int $index) use ($warehouse, $normalizedItems) {
            $fallbackFee = $normalizedItems[$index]['delivery_fee'] ?? 0.00;
            
            $fee = $item['delivery_fee'] 
                ?? $item['price'] 
                ?? $item['fee'] 
                ?? $fallbackFee;

            $item['delivery_fee'] = (float) $fee;

            if (($item['delivery_method'] ?? 'direct') === 'bus_handoff') {
                $item['forward_to_warehouse_id'] = null;
            }

            if (!empty($item['forward_to_warehouse_id']) && (int) $item['forward_to_warehouse_id'] === (int) $warehouse->id) {
                $item['forward_to_warehouse_id'] = null;
            }

            return $item;
        })->all();

        $recipientSignatures = collect($validated['items'])
            ->map(function (array $item): string {
                $delivery = $item['delivery'] ?? [];
                return preg_replace('/\D+/', '', (string) ($delivery['recipient_phone'] ?? ''));
            })
            ->filter()
            ->unique()
            ->values();

        if ($recipientSignatures->count() <= 1) {
            $validated['destination_mode'] = 'single';
            $validated['delivery'] = $validated['items'][0]['delivery'] ?? [];
        } else {
            $validated['destination_mode'] = 'per_item';
            unset($validated['delivery']);
        }

        try {
            $result = $service->createWalkinShipment($validated);
            $shipment = $result['shipment']->load(['items.warehouseReceiptItems.labels', 'items.warehouseReceiptItems.photos']);

            return response()->json([
                'success'  => true,
                'message'  => 'Walk-in shipment created successfully.',
                'shipment' => [
                    'id' => $shipment->id,
                    'shipment_number' => $shipment->shipment_number,
                ],
                'charge' => $result['pickup_fee_charge'] ? [
                    'id' => $result['pickup_fee_charge']->id,
                    'amount' => $result['pickup_fee_charge']->amount,
                    'currency' => $result['pickup_fee_charge']->currency,
                    'status' => $result['pickup_fee_charge']->status,
                ] : null,
                'packages' => $shipment->items->map(fn (ShipmentItem $item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'delivery_fee' => $item->delivery_fee,
                    'tracking_code' => $item->tracking_code,
                    'delivery_method' => $item->delivery_method,
                    'print_url' => route('warehouse.walkin.items.print-label', $item),
                    'photo_count' => (int) ($item->warehouseReceiptItems->first()?->photos?->count() ?? 0),
                    'barcode_print_count' => (int) ($item->warehouseReceiptItems->first()?->barcode_print_count ?? 0),
                ]),
            ]);
        } catch (Throwable $e) {
            Log::error('Warehouse walk-in shipment creation failed.', [
                'route' => $request->route()?->getName(),
                'admin_id' => $user->id,
                'warehouse_id' => $warehouse->id,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'pickup_fee_amount' => $validated['pickup_fee_amount'] ?? null,
                'item_count' => count($validated['items'] ?? []),
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while creating shipment. ' . $e->getMessage(),
            ], 500);
        }
    }

    public function printLabel(Request $request, ShipmentItem $shipmentItem, WalkinShipmentService $service): JsonResponse
    {
        $validated = $request->validate([
            'label_count' => 'nullable|integer|min:1|max:500',
        ]);

        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);

        $result = $service->printWalkinItemLabel(
            $shipmentItem,
            $warehouse,
            $user,
            (int) ($validated['label_count'] ?? 1)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function printLabels(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $validated = $request->validate([
            'packages' => 'required|array|min:1|max:100',
            'packages.*.shipment_item_id' => 'required|integer|exists:shipment_items,id',
            'packages.*.label_count' => 'required|integer|min:1|max:500',
        ]);

        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);

        $result = $service->printWalkinItemLabels($validated['packages'], $warehouse, $user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function vendorLookup(Request $request, WalkinShipmentService $service): JsonResponse
    {
        $request->validate([
            'phone' => [
                'required',
                'string',
                'min:9',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!PhoneHelper::hasValidPrefix((string) $value)) {
                        $fail('Please enter a valid Ghana phone number.');
                    }
                },
            ],
        ]);

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
            'phone'         => [
                'required',
                'string',
                'min:9',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!PhoneHelper::hasValidPrefix((string) $value)) {
                        $fail('Please enter a valid Ghana phone number.');
                    }
                },
            ],
            'email'         => 'nullable|email',
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

    public function destroy($id): JsonResponse
    {
        try {
            $user = Auth::guard('admin')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $shipment = Shipment::find($id);

            if (!$shipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipment not found.',
                ], 404);
            }

            if (method_exists($shipment, 'items')) {
                $shipment->items()->delete();
            }

            $shipment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Walk-in shipment deleted successfully.',
            ], 200);
        } catch (Throwable $e) {
            Log::error('Walkin shipment deletion failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete shipment: ' . $e->getMessage(),
            ], 500);
        }
    }
}