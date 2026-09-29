<?php

namespace App\Services\Admin;

use App\Models\DeliveryRun;
use App\Models\Shipment;
use App\Models\ShipmentPayment;
use App\Models\TransportManifest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * What actually happened, newest first.
 *
 * The admin dashboard's feed used to be the ten most recent Shipment rows and
 * nothing else. That is real data, but it is only one of the things that moves:
 * a batch being dispatched, a transporter departing, a load arriving at a hub and
 * a payment being taken are all activity, and none of them appeared. An admin
 * watching the dashboard could not see the transport operation at all.
 *
 * Every event here is derived from a timestamp the system already writes — there
 * is no separate event log to fall out of step with the data, and nothing is
 * synthesised. If a timestamp is null the event simply did not happen and is not
 * listed.
 *
 * The shape matches WarehouseDashboardService::recentActivity() so both
 * dashboards render the same way.
 */
class ActivityFeedService
{
    /**
     * @return array<int, array{label: string, detail: string, actor: ?string, at: Carbon, url: ?string, tone: string, kind: string}>
     */
    public function recent(int $limit = 12): array
    {
        $events = collect()
            ->merge($this->transportEvents($limit))
            ->merge($this->shipmentEvents($limit))
            ->merge($this->deliveryRunEvents($limit))
            ->merge($this->paymentEvents($limit))
            ->filter(fn ($event) => $event['at'] instanceof Carbon)
            ->sortByDesc(fn ($event) => $event['at']->getTimestamp())
            ->take($limit)
            ->values();

        return $events->all();
    }

    /**
     * The transport operation: batch raised, transporter assigned, departed,
     * arrived, received.
     *
     * One manifest contributes as many events as it has timestamps — a batch that
     * has been raised and dispatched is two events, not one row labelled with its
     * latest state, because both things happened and an admin wants to see both.
     */
    private function transportEvents(int $limit): array
    {
        $manifests = TransportManifest::query()
            ->with(['assignedDriver:id,name', 'originWarehouse:id,name', 'destinationWarehouse:id,name'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        $url = $this->routeOrNull('admin.transport-manifests.index');
        $events = [];

        foreach ($manifests as $manifest) {
            $number = $manifest->manifest_number;
            $driver = $manifest->assignedDriver?->name;
            $destination = $manifest->destinationWarehouse?->name;
            $origin = $manifest->originWarehouse?->name;

            $steps = [
                ['received_at', 'Batch received', $destination ? "at {$destination}" : null, 'emerald'],
                ['arrived_at', 'Batch arrived', $destination ? "at {$destination}" : null, 'emerald'],
                ['dispatched_at', 'Transporter departed', $origin && $destination ? "{$origin} → {$destination}" : null, 'amber'],
                ['assigned_at', 'Transporter assigned', $driver, 'blue'],
                ['created_at', 'Batch raised for transport', $destination ? "→ {$destination}" : null, 'slate'],
            ];

            foreach ($steps as [$column, $label, $detail, $tone]) {
                $at = $manifest->{$column};

                if (! $at) {
                    continue;
                }

                $events[] = [
                    'label' => $label,
                    'detail' => trim($number.($detail ? ' · '.$detail : '')),
                    'actor' => $column === 'assigned_at' ? null : $driver,
                    'at' => $at,
                    'url' => $url,
                    'tone' => $tone,
                    'kind' => 'transport',
                ];
            }
        }

        return $events;
    }

    private function shipmentEvents(int $limit): array
    {
        $shipments = Shipment::query()
            ->with('vendor:id,name,business_name')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'shipment_number', 'status', 'vendor_id', 'created_at', 'updated_at']);

        $events = [];

        foreach ($shipments as $shipment) {
            $status = $shipment->status instanceof \BackedEnum
                ? $shipment->status->value
                : (string) $shipment->status;

            $events[] = [
                'label' => 'Shipment '.str_replace('_', ' ', strtolower($status)),
                'detail' => (string) $shipment->shipment_number,
                'actor' => $shipment->vendor?->business_name ?: $shipment->vendor?->name,
                'at' => $shipment->updated_at ?? $shipment->created_at,
                'url' => $this->routeOrNull('admin.shipments.index'),
                'tone' => match (strtolower($status)) {
                    'delivered' => 'emerald',
                    'in_transit' => 'amber',
                    'at_warehouse' => 'violet',
                    'submitted' => 'blue',
                    default => 'slate',
                },
                'kind' => 'shipment',
            ];
        }

        return $events;
    }

    private function deliveryRunEvents(int $limit): array
    {
        $runs = DeliveryRun::query()
            ->with('assignedDriver:id,name')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        $url = $this->routeOrNull('admin.delivery-runs.index');
        $events = [];

        foreach ($runs as $run) {
            $driver = $run->assignedDriver?->name;

            $steps = [
                ['completed_at', 'Run completed', 'emerald'],
                ['dispatched_at', 'Rider left the hub', 'amber'],
                ['created_at', 'Run created', 'slate'],
            ];

            foreach ($steps as [$column, $label, $tone]) {
                $at = $run->{$column};

                if (! $at) {
                    continue;
                }

                $events[] = [
                    'label' => $label,
                    'detail' => trim((string) $run->run_number.($driver ? ' · '.$driver : '')),
                    'actor' => null,
                    'at' => $at,
                    'url' => $url,
                    'tone' => $tone,
                    'kind' => 'delivery',
                ];
            }
        }

        return $events;
    }

    private function paymentEvents(int $limit): array
    {
        $payments = ShipmentPayment::query()
            ->with('shipment:id,shipment_number')
            ->orderByDesc('payment_date')
            ->limit($limit)
            ->get();

        return $payments->map(fn ($payment) => [
            'label' => 'Payment recorded',
            'detail' => 'GHS '.number_format((float) $payment->amount, 2)
                .($payment->shipment?->shipment_number ? ' · '.$payment->shipment->shipment_number : ''),
            'actor' => $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : null,
            'at' => $payment->payment_date,
            'url' => $this->routeOrNull('admin.shipments.index'),
            'tone' => 'violet',
            'kind' => 'payment',
        ])->all();
    }

    /**
     * A link when the screen exists, nothing when it does not.
     *
     * A dashboard is not worth a 500 because one of the screens it points at was
     * renamed or never built.
     */
    private function routeOrNull(string $name, array $parameters = []): ?string
    {
        return Route::has($name) ? route($name, $parameters) : null;
    }
}
