<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Services\ChargesService;
use App\Services\Warehouse\WarehousePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Warehouse-scoped charge management. Mirrors the admin controller but:
 *   - Uses the `warehouse.charges.manage` permission
 *   - Enforces that the shipment has a pickup assignment (current or
 *     received) AT the caller's warehouse, so a warehouse user can't
 *     touch charges on shipments belonging to a different warehouse.
 */
class WarehouseShipmentChargesController extends Controller
{
    public function __construct(
        private ChargesService $charges,
        private WarehousePortalService $portalService,
    ) {}

    public function index(Shipment $shipment): JsonResponse
    {
        $this->authorizeForShipment('warehouse.charges.view', $shipment);

        return response()->json([
            'data'    => $this->serialiseCharges($shipment->charges()->get()),
            'summary' => $this->charges->summariseShipment($shipment),
            'defaults' => [
                'pickup_fee' => $this->charges->getDefaultPickupFee(),
            ],
        ]);
    }

    public function store(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorizeForShipment('warehouse.charges.manage', $shipment);

        $validated = $request->validate([
            'shipment_item_id' => [
                'nullable',
                Rule::exists('shipment_items', 'id')->where(fn ($q) => $q->where('shipment_id', $shipment->id)),
            ],
            'charge_type' => ['required', Rule::in(ShipmentCharge::TYPES)],
            'payer_type'  => ['required', Rule::in(ShipmentCharge::PAYERS)],
            'due_stage'   => ['required', Rule::in(ShipmentCharge::DUE_STAGES)],
            'amount'      => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'status'      => ['nullable', Rule::in([ShipmentCharge::STATUS_DRAFT, ShipmentCharge::STATUS_PENDING, ShipmentCharge::STATUS_PAID])],
            'notes'       => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validated['charge_type'] === ShipmentCharge::TYPE_DELIVERY_FEE && empty($validated['shipment_item_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery fees must be assigned to a package.',
            ], 422);
        }

        $charge = $this->charges->addCharge($shipment, $validated, Auth::guard('admin')->user());

        return response()->json([
            'success' => true,
            'message' => 'Charge added.',
            'charge'  => $this->serialiseCharge($charge),
            'summary' => $this->charges->summariseShipment($shipment),
        ], 201);
    }

    public function update(Request $request, Shipment $shipment, ShipmentCharge $charge): JsonResponse
    {
        $this->authorizeForShipment('warehouse.charges.manage', $shipment);
        $this->assertChargeBelongsTo($shipment, $charge);

        $validated = $request->validate([
            'amount'      => ['sometimes', 'numeric', 'min:0', 'max:9999999.99'],
            'notes'       => ['sometimes', 'nullable', 'string', 'max:1000'],
            'due_stage'   => ['sometimes', Rule::in(ShipmentCharge::DUE_STAGES)],
            'payer_type'  => ['sometimes', Rule::in(ShipmentCharge::PAYERS)],
            'charge_type' => ['sometimes', Rule::in(ShipmentCharge::TYPES)],
        ]);

        $result = $this->charges->updateCharge($charge, $validated, Auth::guard('admin')->user());

        return response()->json(
            array_merge($result, ['summary' => $this->charges->summariseShipment($shipment)]),
            $result['success'] ? 200 : 422,
        );
    }

    public function markPaid(Request $request, Shipment $shipment, ShipmentCharge $charge): JsonResponse
    {
        $this->authorizeForShipment('warehouse.charges.manage', $shipment);
        $this->assertChargeBelongsTo($shipment, $charge);

        $validated = $request->validate([
            'payment_method'    => ['required', 'string', 'max:32'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->charges->markPaid(
            $charge,
            $validated['payment_method'],
            $validated['payment_reference'] ?? null,
            Auth::guard('admin')->user(),
        );

        return response()->json(
            array_merge($result, ['summary' => $this->charges->summariseShipment($shipment)]),
            $result['success'] ? 200 : 422,
        );
    }

    public function waive(Request $request, Shipment $shipment, ShipmentCharge $charge): JsonResponse
    {
        $this->authorizeForShipment('warehouse.charges.manage', $shipment);
        $this->assertChargeBelongsTo($shipment, $charge);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->charges->waive($charge, $validated['reason'] ?? null, Auth::guard('admin')->user());

        return response()->json(
            array_merge($result, ['summary' => $this->charges->summariseShipment($shipment)]),
            $result['success'] ? 200 : 422,
        );
    }

    public function cancel(Shipment $shipment, ShipmentCharge $charge): JsonResponse
    {
        $this->authorizeForShipment('warehouse.charges.manage', $shipment);
        $this->assertChargeBelongsTo($shipment, $charge);

        $result = $this->charges->cancel($charge, Auth::guard('admin')->user());

        return response()->json(
            array_merge($result, ['summary' => $this->charges->summariseShipment($shipment)]),
            $result['success'] ? 200 : 422,
        );
    }

    // ------------------------------------------------------------------

    private function authorizeForShipment(string $permission, Shipment $shipment): void
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin?->hasPermission($permission)) {
            abort(403, 'Unauthorized.');
        }

        // Super admin bypasses warehouse scoping.
        if (method_exists($admin, 'isSuperAdmin') && $admin->isSuperAdmin()) {
            return;
        }

        $warehouse = $this->portalService->resolveWarehouse($admin);
        $warehouseId = (int) ($warehouse?->id ?? 0);

        $hasAssignment = $shipment->pickupAssignments()
            ->where(function ($q) use ($warehouseId) {
                $q->where('target_warehouse_id', $warehouseId)
                    ->orWhere('received_warehouse_id', $warehouseId);
            })->exists();

        if (!$hasAssignment) {
            abort(403, 'This shipment is not at your warehouse.');
        }
    }

    private function assertChargeBelongsTo(Shipment $shipment, ShipmentCharge $charge): void
    {
        if ((int) $charge->shipment_id !== (int) $shipment->id) {
            abort(404, 'Charge not found on this shipment.');
        }
    }

    private function serialiseCharges($charges): array
    {
        return $charges->map(fn (ShipmentCharge $c) => $this->serialiseCharge($c))->values()->all();
    }

    private function serialiseCharge(ShipmentCharge $charge): array
    {
        return [
            'id'                => $charge->id,
            'shipment_item_id'  => $charge->shipment_item_id,
            'charge_type'       => $charge->charge_type,
            'payer_type'        => $charge->payer_type,
            'direction'         => $charge->direction,
            'due_stage'         => $charge->due_stage,
            'amount'            => (float) $charge->amount,
            'currency'          => $charge->currency,
            'status'            => $charge->status,
            'paid_at'           => $charge->paid_at?->toIso8601String(),
            'payment_method'    => $charge->payment_method,
            'payment_reference' => $charge->payment_reference,
            'notes'             => $charge->notes,
            'waive_reason'      => $charge->waive_reason,
            'created_at'        => $charge->created_at?->toIso8601String(),
        ];
    }
}
