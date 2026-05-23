<?php

namespace App\Console\Commands;

use App\Enums\ItemStatus;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentStatus;
use App\Events\PickupAssignmentStatusChanged;
use App\Events\ShipmentStatusChanged;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\PickupItemConfirmation;
use App\Models\Shipment;
use App\Models\ShipmentItemTracking;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Simulates 10 submitted shipments having been picked up by a driver and
 * now sitting "pending receipt" at a warehouse. Produces the full paper
 * trail the real pickup flow leaves:
 *   - PickupAssignment with all five state-transition timestamps
 *   - PickupItemConfirmation per shipment item (expected = confirmed)
 *   - ShipmentItemTracking per item (status = picked_up)
 *   - Shipment + ShipmentItem statuses advanced to picked_up
 *
 * No vendor-accept chain is simulated — the receiving screen
 * keys off PickupAssignment.target_warehouse_id and received_at IS NULL.
 */
class SimulateDriverPickups extends Command
{
    protected $signature = 'shipments:simulate-pickup
                            {--vendor-phone=0542796510}
                            {--count=10}
                            {--driver-id=1}
                            {--warehouse-id=}
                            {--selection=newest : newest|oldest}
                            {--window-minutes=120 : how far back the assigned_at starts}';

    protected $description = 'Fake 10 shipments having been picked up by a driver so the warehouse receiving flow has work to test.';

    public function handle(): int
    {
        $vendorPhone = (string) $this->option('vendor-phone');
        $count = (int) $this->option('count');
        $driverId = (int) $this->option('driver-id');
        $warehouseId = $this->option('warehouse-id');
        $selection = (string) $this->option('selection');
        $windowMinutes = (int) $this->option('window-minutes');

        $vendor = Vendor::where('phone', 'like', '%' . ltrim($vendorPhone, '+0') . '%')->first();
        if (!$vendor) {
            $this->error("No vendor found for phone {$vendorPhone}.");
            return self::FAILURE;
        }

        $driver = Driver::find($driverId);
        if (!$driver) {
            $this->error("No driver #{$driverId}.");
            return self::FAILURE;
        }

        $warehouse = $warehouseId
            ? Warehouse::find((int) $warehouseId)
            : Warehouse::where('is_active', true)->orderBy('id')->first();
        if (!$warehouse) {
            $this->error('No warehouse available. Seed warehouses first.');
            return self::FAILURE;
        }

        // Pick 10 shipments that are currently submitted and have no pickup assignment yet.
        $query = Shipment::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', ShipmentStatus::SUBMITTED)
            ->whereDoesntHave('pickupAssignment')
            ->with('items');

        $shipments = ($selection === 'oldest'
            ? $query->orderBy('id', 'asc')
            : $query->orderBy('id', 'desc'))
            ->limit($count)
            ->get();

        if ($shipments->isEmpty()) {
            $this->error('No eligible submitted shipments without an existing pickup assignment.');
            return self::FAILURE;
        }

        if ($shipments->count() < $count) {
            $this->warn("Only {$shipments->count()} eligible shipment(s) found (requested {$count}). Continuing with what's available.");
        }

        $this->info(sprintf(
            'Simulating pickup for %d shipment(s) → driver #%d (%s), warehouse #%d (%s).',
            $shipments->count(),
            $driver->id,
            $driver->name,
            $warehouse->id,
            $warehouse->name,
        ));

        // Silence status-change notifications.
        Event::fake([ShipmentStatusChanged::class, PickupAssignmentStatusChanged::class]);

        $bar = $this->output->createProgressBar($shipments->count());
        $bar->start();

        $createdAssignmentIds = [];
        $adminId = DB::table('users')->orderBy('id')->value('id'); // any user is fine for assigned_by

        foreach ($shipments as $shipment) {
            DB::transaction(function () use ($shipment, $driver, $warehouse, $adminId, $windowMinutes, &$createdAssignmentIds) {
                // Build timestamps in order, within the last `$windowMinutes`.
                $now = now();
                // assigned → en_route → arrived → picking_up → picked_up, spread across the window
                $assignedAt   = $now->copy()->subMinutes(random_int((int) ($windowMinutes * 0.8), $windowMinutes));
                $enRouteAt    = $assignedAt->copy()->addMinutes(random_int(2, 6));
                $arrivedAt    = $enRouteAt->copy()->addMinutes(random_int(20, 45));
                $pickingUpAt  = $arrivedAt->copy()->addMinutes(random_int(3, 8));
                $pickedUpAt   = $pickingUpAt->copy()->addMinutes(random_int(5, 15));
                $completedAt  = $pickedUpAt;

                $pickupLocation = $shipment->pickup_town
                    ?: $shipment->pickup_gh_post_address
                    ?: 'Vendor location';

                $assignment = PickupAssignment::create([
                    'shipment_id'           => $shipment->id,
                    'driver_id'             => $driver->id,
                    'target_warehouse_id'   => $warehouse->id,
                    'status'                => PickupAssignmentStatus::COMPLETED->value,
                    'assigned_by'           => $adminId,
                    'assigned_at'           => $assignedAt,
                    'en_route_at'           => $enRouteAt,
                    'arrived_at'            => $arrivedAt,
                    'picked_up_at'          => $pickedUpAt,
                    'completed_at'          => $completedAt,
                    'received_at'           => null,
                    'received_warehouse_id' => null,
                    'pickup_latitude'       => $shipment->pickup_latitude ?? 5.5600,
                    'pickup_longitude'      => $shipment->pickup_longitude ?? -0.2050,
                    'notes'                 => 'Simulated pickup for testing warehouse receiving.',
                    'created_at'            => $assignedAt,
                    'updated_at'            => $completedAt,
                ]);

                foreach ($shipment->items as $item) {
                    PickupItemConfirmation::create([
                        'pickup_assignment_id' => $assignment->id,
                        'shipment_item_id'     => $item->id,
                        'expected_quantity'    => (int) $item->quantity,
                        'confirmed_quantity'   => (int) $item->quantity,
                        'notes'                => null,
                        'confirmed_at'         => $pickingUpAt,
                        'created_at'           => $pickingUpAt,
                        'updated_at'           => $pickingUpAt,
                    ]);

                    // Mirror PickupAssignmentService's exact tracking behaviour.
                    ShipmentItemTracking::create([
                        'shipment_item_id' => $item->id,
                        'status'           => ItemStatus::PICKED_UP->value,
                        'location'         => $pickupLocation,
                        'notes'            => "Driver confirmed pickup quantity {$item->quantity}/{$item->quantity}.",
                        'created_by'       => "driver:{$driver->id}",
                        'created_at'       => $pickedUpAt,
                    ]);

                    $item->update(['status' => ItemStatus::PICKED_UP]);
                }

                $shipment->update(['status' => ShipmentStatus::PICKED_UP]);

                $createdAssignmentIds[] = $assignment->id;
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Done. Created PickupAssignment IDs: ' . implode(', ', $createdAssignmentIds));
        $this->info("Visit /warehouse/receipts/pending while logged in as a user scoped to warehouse #{$warehouse->id} to see them.");

        return self::SUCCESS;
    }
}
