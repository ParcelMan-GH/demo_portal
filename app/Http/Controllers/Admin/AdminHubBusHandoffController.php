<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HubBusHandoff;
use App\Services\BackOfficeAccess;
use App\Services\HubBusHandoffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The back-office view of parcels handed to intercity bus drivers.
 *
 * Before this existed the driver and vehicle details only existed inside a
 * tracking entry's JSON, so nobody could actually look at a handover. This is
 * the screen the admin sees, photo included.
 */
class AdminHubBusHandoffController extends Controller
{
    /**
     * The module whose warehouse scoping applies. A hub is a `warehouses` row.
     */
    private const MODULE = 'warehouse';

    public function __construct(
        private HubBusHandoffService $handoffs,
        private BackOfficeAccess $access,
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::guard('admin')->user();

        $filters = $request->validate([
            'hub_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'sms' => ['nullable', 'string', 'in:sent,failed'],
        ]);

        $query = HubBusHandoff::query()
            ->with(['shipmentItem.shipment', 'hub', 'handedOffBy'])
            ->latest('created_at');

        $this->access->applyWarehouseScope($query, $user, self::MODULE, 'hub_id');

        if (! empty($filters['hub_id'])) {
            $query->where('hub_id', (int) $filters['hub_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (($filters['sms'] ?? null) === 'sent') {
            $query->whereNotNull('sms_sent_at');
        } elseif (($filters['sms'] ?? null) === 'failed') {
            $query->whereNull('sms_sent_at')->whereNotNull('sms_failed_at');
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('driver_name', 'like', "%{$search}%")
                    ->orWhere('driver_phone', 'like', "%{$search}%")
                    ->orWhere('vehicle_plate', 'like', "%{$search}%")
                    ->orWhere('bus_company', 'like', "%{$search}%")
                    ->orWhereHas('shipmentItem', fn ($itemQuery) => $itemQuery
                        ->where('tracking_code', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                    );
            });
        }

        $summaryQuery = clone $query;

        $handoffs = $query->paginate(25)->withQueryString();

        return view('admin.hub-handoffs.index', [
            'handoffs' => $handoffs->through(
                fn (HubBusHandoff $handoff) => $this->handoffs->payload($handoff, includePhotoUrl: false)
            ),
            'hubs' => $this->access->warehousesFor($user, self::MODULE),
            'filters' => $filters,
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'today' => (clone $summaryQuery)->whereDate('created_at', today())->count(),
                'sms_failed' => (clone $summaryQuery)
                    ->whereNull('sms_sent_at')
                    ->whereNotNull('sms_failed_at')
                    ->count(),
            ],
        ]);
    }

    public function show(HubBusHandoff $handoff): View
    {
        $user = Auth::guard('admin')->user();

        if ($handoff->hub_id) {
            $this->access->assertCanUseWarehouse($user, (int) $handoff->hub_id, self::MODULE);
        }

        $handoff->load(['shipmentItem.shipment', 'hub', 'handedOffBy', 'batch']);

        return view('admin.hub-handoffs.show', [
            'handoff' => $this->handoffs->payload($handoff),
            'model' => $handoff,
        ]);
    }
}
