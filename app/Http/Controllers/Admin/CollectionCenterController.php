<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentCollection;
use App\Models\ShipmentItemTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CollectionCenterController extends Controller
{
    public function index(): View
    {
        $readyCount = ShipmentCollection::where('status', ShipmentCollection::STATUS_READY)->count();

        $collectedTodayCount = ShipmentCollection::where('status', ShipmentCollection::STATUS_COLLECTED)
            ->whereDate('collected_at', today())
            ->count();

        $totalCollectedCount = ShipmentCollection::where('status', ShipmentCollection::STATUS_COLLECTED)->count();

        return view('admin.collection-center.index', compact(
            'readyCount',
            'collectedTodayCount',
            'totalCollectedCount'
        ));
    }

    public function data(Request $request): JsonResponse
    {
        $query = ShipmentCollection::with(['shipment.vendor', 'shipment.items', 'shipment.warehouse', 'handedOverBy']);

        $status = $request->get('status', 'ready');
        if ($status && in_array($status, [ShipmentCollection::STATUS_READY, ShipmentCollection::STATUS_COLLECTED])) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->whereHas('shipment', function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                  ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $page    = max((int) $request->get('page', 1), 1);
        $total   = $query->count();
        $items   = $query->latest('ready_at')->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $items->map(function (ShipmentCollection $c) {
                $shipment = $c->shipment;
                return [
                    'id'                 => $c->id,
                    'shipment_id'        => $shipment?->id,
                    'shipment_number'    => $shipment?->shipment_number,
                    'vendor_name'        => $shipment?->vendor?->name,
                    'warehouse_name'     => $c->warehouse?->name ?? $shipment?->warehouse?->name,
                    'recipient_name'     => $shipment?->delivery_recipient_name
                                            ?: $shipment?->items?->first()?->delivery_recipient_name,
                    'recipient_phone'    => $shipment?->delivery_recipient_phone
                                            ?: $shipment?->items?->first()?->delivery_recipient_phone,
                    'items_count'        => $shipment?->items?->count() ?? 0,
                    'status'             => $c->status,
                    'ready_at'           => $c->ready_at?->format('d M Y, H:i'),
                    'collected_at'       => $c->collected_at?->format('d M Y, H:i'),
                    'collected_by_name'  => $c->collected_by_name,
                    'collected_by_phone' => $c->collected_by_phone,
                    'handed_over_by'     => $c->handedOverBy?->name,
                    'notes'              => $c->notes,
                ];
            }),
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max((int) ceil($total / $perPage), 1),
                'from'         => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to'           => min($page * $perPage, $total),
            ],
        ]);
    }

    public function handover(Request $request, Shipment $shipment): JsonResponse
    {
        $collection = $shipment->collection;

        if (!$collection) {
            return response()->json(['success' => false, 'message' => 'No collection record found for this shipment.'], 404);
        }

        if ($collection->isCollected()) {
            return response()->json(['success' => false, 'message' => 'This shipment has already been collected.'], 400);
        }

        $validated = $request->validate([
            'collected_by_name'      => 'required|string|max:255',
            'collected_by_phone'     => 'required|string|max:20',
            'collected_by_id_type'   => 'nullable|string|max:50',
            'collected_by_id_number' => 'nullable|string|max:100',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        $admin = Auth::guard('admin')->user();

        DB::transaction(function () use ($shipment, $collection, $admin, $validated) {
            $now = now();

            // Append admin identity to notes for audit trail
            $adminNote = "Handed over by admin: {$admin->name} (#{$admin->id})";
            $notes = $validated['notes']
                ? $validated['notes'] . ' | ' . $adminNote
                : $adminNote;

            $collection->update([
                'status'                 => ShipmentCollection::STATUS_COLLECTED,
                'collected_by_name'      => $validated['collected_by_name'],
                'collected_by_phone'     => $validated['collected_by_phone'],
                'collected_by_id_type'   => $validated['collected_by_id_type'] ?? null,
                'collected_by_id_number' => $validated['collected_by_id_number'] ?? null,
                'collected_at'           => $now,
                // handed_over_by_user_id is left null — admins are not in the users table
                'notes'                  => $notes,
            ]);

            // Mark shipment and items as delivered
            $shipment->update(['status' => ShipmentStatus::DELIVERED]);
            $shipment->items()->update(['status' => ItemStatus::DELIVERED]);

            // Tracking entries
            foreach ($shipment->items as $item) {
                ShipmentItemTracking::create([
                    'shipment_item_id' => $item->id,
                    'status'           => 'delivered',
                    'location'         => $collection->warehouse?->name,
                    'notes'            => "Collected by {$validated['collected_by_name']} at warehouse. Processed by admin {$admin->name}.",
                    'created_by'       => "admin:{$admin->id}",
                    'created_at'       => $now,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Shipment handed over successfully.',
        ]);
    }
}
