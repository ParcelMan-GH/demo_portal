<?php

use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\Shipment;
use App\Models\WarehouseReceiptItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Symfony\Component\Finder\Finder;

function assertOperationalRelationPathExists(string $modelClass, string $path): void
{
    $model = new $modelClass;

    foreach (explode('.', $path) as $segment) {
        $relationName = explode(':', $segment, 2)[0];
        $currentClass = $model::class;

        expect(method_exists($model, $relationName))
            ->toBeTrue("Missing relation [{$relationName}] on [{$currentClass}] while checking [{$modelClass}::{$path}].");

        $relation = $model->{$relationName}();

        expect($relation)
            ->toBeInstanceOf(Relation::class, "[{$currentClass}::{$relationName}] must return an Eloquent relation.");

        $related = $relation->getRelated();

        expect($related)
            ->toBeInstanceOf(Model::class, "[{$currentClass}::{$relationName}] must resolve to an Eloquent model.");

        $model = $related;
    }
}

test('high risk admin and warehouse operational relation paths exist', function () {
    $paths = [
        WarehouseReceiptItem::class => [
            'receipt.pickupAssignment.driver',
            'receipt.warehouse',
            'receivedBy',
            'photos.createdBy',
            'labels.custodyEvents.driver',
            'labels.custodyEvents.scannedBy',
            'shipmentItem.shipment.vendor',
            'shipmentItem.images',
            'shipmentItem.charges.recordedByAdmin',
            'shipmentItem.deliveryRunItems.run.assignedDriver',
            'shipmentItem.deliveryRunItems.run.createdBy',
            'shipmentItem.deliveryRunItems.stop.region',
            'shipmentItem.deliveryRunItems.stop.district',
            'shipmentItem.deliveryRunItems.stop.confirmedBy',
            'shipmentItem.deliveryRunItems.stop.verificationAttempts.driver',
            'shipmentItem.deliveryRunItems.expectedDeliverySetByDriver',
            'shipmentItem.deliveryRunItems.expectedDeliverySetByUser',
            'shipmentItem.deliveryRunItems.delayEvents.reason',
            'shipmentItem.deliveryRunItems.delayEvents.actorDriver',
            'shipmentItem.deliveryRunItems.delayEvents.actorUser',
            'shipmentItem.deliveryRunItems.busHandoffConfirmation.reason',
            'shipmentItem.deliveryRunItems.busHandoffConfirmation.handoffDriver',
            'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByDriver',
            'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByAdmin',
            'sortBatchItems.sortBatch.originWarehouse',
            'sortBatchItems.sortBatch.destinationWarehouse',
            'sortBatchItems.sortBatch.createdBy',
            'sortBatchItems.sortBatch.deliveryRun.assignedDriver',
            'sortBatchItems.recipientPaymentTask.assignedTo',
            'sortBatchItems.recipientPaymentTask.paymentGroupRecord.paidBy',
            'sortBatchItems.recipientPaymentTask.paymentGroupRecord.sessionEntries.recordedBy',
            'sortBatchItems.recipientPaymentTask.paymentWallet',
            'sortBatchItems.recipientPaymentTask.shipmentCharge.recordedByAdmin',
        ],
        DeliveryRun::class => [
            'warehouse',
            'assignedDriver',
            'createdBy',
            'stops.region',
            'stops.district',
            'stops.confirmedBy',
            'stops.verificationAttempts.driver',
            'items.shipmentItem.shipment.vendor',
            'items.expectedDeliverySetByDriver',
            'items.expectedDeliverySetByUser',
            'items.delayEvents.reason',
            'items.delayEvents.actorDriver',
            'items.delayEvents.actorUser',
            'items.busHandoffConfirmation.reason',
            'items.busHandoffConfirmation.handoffDriver',
            'items.busHandoffConfirmation.confirmedByDriver',
            'items.busHandoffConfirmation.confirmedByAdmin',
        ],
        DeliveryRunItem::class => [
            'run.assignedDriver',
            'run.createdBy',
            'stop.region',
            'stop.district',
            'stop.confirmedBy',
            'shipmentItem.shipment.vendor',
            'expectedDeliverySetByDriver',
            'expectedDeliverySetByUser',
            'delayEvents.reason',
            'delayEvents.actorDriver',
            'delayEvents.actorUser',
            'busHandoffConfirmation.reason',
            'busHandoffConfirmation.handoffDriver',
            'busHandoffConfirmation.confirmedByDriver',
            'busHandoffConfirmation.confirmedByAdmin',
        ],
        Shipment::class => [
            'vendor',
            'createdByUser',
            'items.deliveryRunItems.expectedDeliverySetByDriver',
            'items.deliveryRunItems.expectedDeliverySetByUser',
            'items.deliveryRunItems.delayEvents.actorDriver',
            'items.deliveryRunItems.delayEvents.actorUser',
            'items.deliveryRunItems.busHandoffConfirmation.handoffDriver',
            'items.deliveryRunItems.busHandoffConfirmation.confirmedByDriver',
            'items.deliveryRunItems.busHandoffConfirmation.confirmedByAdmin',
            'charges.recordedByAdmin',
            'collection',
        ],
    ];

    foreach ($paths as $modelClass => $relationPaths) {
        foreach ($relationPaths as $path) {
            assertOperationalRelationPathExists($modelClass, $path);
        }
    }
});

test('stale rider relation names are not referenced in operational code', function () {
    $forbidden = array_map(static fn (array $parts) => implode('', $parts), [
        ['expectedDeliverySetBy', 'Rider'],
        ['actor', 'Rider'],
        ['handoff', 'Rider'],
        ['confirmedBy', 'Rider'],
    ]);

    $finder = Finder::create()
        ->files()
        ->in([app_path(), resource_path(), base_path('routes'), base_path('tests')])
        ->name('*.php')
        ->name('*.blade.php')
        ->name('*.js');

    foreach ($finder as $file) {
        $contents = $file->getContents();

        foreach ($forbidden as $needle) {
            expect($contents)
                ->not->toContain($needle, "Forbidden stale relation [{$needle}] found in [{$file->getRealPath()}].");
        }
    }
});
