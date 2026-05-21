<?php

namespace App\Services;

use App\Enums\ShipmentDestinationMode;
use App\Helpers\PhoneHelper;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShipmentPhoneGroupingService
{
    public function syncFromTaggedPhotos(Shipment $shipment): ?ShipmentDestinationMode
    {
        return DB::transaction(function () use ($shipment) {
            $shipment = Shipment::query()
                ->with(['items.images', 'items.deliveryRegion', 'items.deliveryDistrict', 'deliveryRegion', 'deliveryDistrict'])
                ->lockForUpdate()
                ->find($shipment->id);

            if (! $shipment) {
                return null;
            }

            $hasTaggedPhoto = $shipment->items
                ->flatMap(fn (ShipmentItem $item) => $item->images)
                ->contains(fn (ShipmentItemImage $image) => filled($this->normalizePhone($image->recipient_phone)));

            if (! $hasTaggedPhoto) {
                return null;
            }

            $this->splitItemsWithMultipleTaggedPhones($shipment);

            $shipment->load(['items.images', 'items.deliveryRegion', 'items.deliveryDistrict', 'deliveryRegion', 'deliveryDistrict']);

            return $this->syncDestinationMode($shipment);
        });
    }

    private function splitItemsWithMultipleTaggedPhones(Shipment $shipment): void
    {
        $shipment->items->each(function (ShipmentItem $item) {
            $taggedGroups = $item->images
                ->mapToGroups(function (ShipmentItemImage $image) {
                    $phone = $this->normalizePhone($image->recipient_phone);

                    return $phone ? [$phone => $image] : [];
                });

            if ($taggedGroups->count() <= 1) {
                $phone = $taggedGroups->keys()->first();
                if ($phone && $this->normalizePhone($item->delivery_recipient_phone) !== $phone) {
                    $item->update(['delivery_recipient_phone' => $phone]);
                }

                return;
            }

            $groups = $taggedGroups->sortKeys();
            $firstPhone = $groups->keys()->first();
            $untaggedCount = $item->images
                ->filter(fn (ShipmentItemImage $image) => ! filled($this->normalizePhone($image->recipient_phone)))
                ->count();

            $item->update([
                'delivery_recipient_phone' => $firstPhone,
                'quantity' => max(1, $groups->get($firstPhone)->count() + $untaggedCount),
            ]);

            $groups->skip(1)->each(function (Collection $images, string $phone) use ($item) {
                $clone = ShipmentItem::create($this->cloneItemAttributes($item, [
                    'delivery_recipient_phone' => $phone,
                    'quantity' => max(1, $images->count()),
                ]));

                ShipmentItemImage::query()
                    ->whereIn('id', $images->pluck('id'))
                    ->update(['shipment_item_id' => $clone->id]);
            });
        });
    }

    private function syncDestinationMode(Shipment $shipment): ?ShipmentDestinationMode
    {
        $phones = $shipment->items
            ->flatMap(function (ShipmentItem $item) {
                $itemPhone = $this->normalizePhone($item->delivery_recipient_phone);
                $photoPhones = $item->images
                    ->map(fn (ShipmentItemImage $image) => $this->normalizePhone($image->recipient_phone))
                    ->filter();

                return $itemPhone ? $photoPhones->push($itemPhone) : $photoPhones;
            })
            ->filter()
            ->unique()
            ->values();

        if ($phones->count() > 1) {
            $this->seedPackageDeliveryFromShipment($shipment);
            $shipment->update(array_merge(
                ['destination_mode' => ShipmentDestinationMode::PER_ITEM],
                $this->emptyShipmentDeliveryAttributes()
            ));

            return ShipmentDestinationMode::PER_ITEM;
        }

        if ($phones->count() === 1) {
            $seed = $shipment->destination_mode === ShipmentDestinationMode::PER_ITEM
                ? $this->shipmentDeliverySeedFromItems($shipment)
                : [];

            $shipment->update(array_merge($seed, [
                'destination_mode' => ShipmentDestinationMode::SINGLE,
                'delivery_recipient_phone' => $phones->first(),
            ]));

            return ShipmentDestinationMode::SINGLE;
        }

        return null;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        return PhoneHelper::format($phone) ?: trim((string) $phone);
    }

    private function cloneItemAttributes(ShipmentItem $item, array $overrides = []): array
    {
        return array_merge([
            'shipment_id' => $item->shipment_id,
            'description' => $item->description,
            'quantity' => max(1, (int) $item->quantity),
            'delivery_recipient_name' => $item->delivery_recipient_name,
            'delivery_recipient_phone' => $item->delivery_recipient_phone,
            'delivery_region_id' => $item->delivery_region_id,
            'delivery_district_id' => $item->delivery_district_id,
            'delivery_town' => $item->delivery_town,
            'delivery_latitude' => $item->delivery_latitude,
            'delivery_longitude' => $item->delivery_longitude,
            'delivery_gh_post_address' => $item->delivery_gh_post_address,
            'delivery_landmark' => $item->delivery_landmark,
            'delivery_instructions' => $item->delivery_instructions,
            'fulfillment_type' => $item->fulfillment_type?->value ?? $item->getRawOriginal('fulfillment_type'),
            'delivery_preference' => $item->delivery_preference,
            'delivery_method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
            'status' => $item->status?->value ?? $item->getRawOriginal('status') ?? 'pending',
        ], $overrides);
    }

    private function seedPackageDeliveryFromShipment(Shipment $shipment): void
    {
        if (! $this->hasCoreDeliveryDetails($shipment)) {
            return;
        }

        $attributes = $this->shipmentDeliveryAttributes($shipment);

        $shipment->items()->get()->each(function (ShipmentItem $item) use ($attributes) {
            $updates = [];

            foreach ($attributes as $key => $value) {
                if (($item->{$key} === null || $item->{$key} === '') && $value !== null && $value !== '') {
                    $updates[$key] = $value;
                }
            }

            if ($updates !== []) {
                $item->update($updates);
            }
        });
    }

    private function shipmentDeliverySeedFromItems(Shipment $shipment): array
    {
        $sourceItem = $shipment->items()->get()->first(fn (ShipmentItem $item) => $this->hasCoreDeliveryDetails($item));

        return $sourceItem ? $this->itemDeliveryAttributes($sourceItem) : [];
    }

    private function hasCoreDeliveryDetails(Shipment|ShipmentItem $model): bool
    {
        foreach ($this->coreDeliveryFieldKeys() as $key) {
            if ($model->{$key} !== null && $model->{$key} !== '') {
                return true;
            }
        }

        return false;
    }

    private function deliveryFieldKeys(): array
    {
        return [
            'delivery_recipient_name',
            'delivery_recipient_phone',
            'delivery_region_id',
            'delivery_district_id',
            'delivery_town',
            'delivery_latitude',
            'delivery_longitude',
            'delivery_gh_post_address',
            'delivery_landmark',
            'delivery_instructions',
        ];
    }

    private function coreDeliveryFieldKeys(): array
    {
        return [
            'delivery_recipient_name',
            'delivery_recipient_phone',
            'delivery_region_id',
            'delivery_district_id',
            'delivery_town',
            'delivery_latitude',
            'delivery_longitude',
            'delivery_gh_post_address',
            'delivery_landmark',
        ];
    }

    private function emptyShipmentDeliveryAttributes(): array
    {
        return array_fill_keys($this->deliveryFieldKeys(), null);
    }

    private function shipmentDeliveryAttributes(Shipment $shipment): array
    {
        return [
            'delivery_recipient_name' => $shipment->delivery_recipient_name,
            'delivery_recipient_phone' => $shipment->delivery_recipient_phone,
            'delivery_region_id' => $shipment->delivery_region_id,
            'delivery_district_id' => $shipment->delivery_district_id,
            'delivery_town' => $shipment->delivery_town,
            'delivery_latitude' => $shipment->delivery_latitude,
            'delivery_longitude' => $shipment->delivery_longitude,
            'delivery_gh_post_address' => $shipment->delivery_gh_post_address,
            'delivery_landmark' => $shipment->delivery_landmark,
            'delivery_instructions' => $shipment->delivery_instructions,
            'delivery_preference' => $shipment->delivery_preference,
            'fulfillment_type' => $shipment->fulfillment_type?->value ?? $shipment->getRawOriginal('fulfillment_type'),
        ];
    }

    private function itemDeliveryAttributes(ShipmentItem $item): array
    {
        return [
            'delivery_recipient_name' => $item->delivery_recipient_name,
            'delivery_recipient_phone' => $item->delivery_recipient_phone,
            'delivery_region_id' => $item->delivery_region_id,
            'delivery_district_id' => $item->delivery_district_id,
            'delivery_town' => $item->delivery_town,
            'delivery_latitude' => $item->delivery_latitude,
            'delivery_longitude' => $item->delivery_longitude,
            'delivery_gh_post_address' => $item->delivery_gh_post_address,
            'delivery_landmark' => $item->delivery_landmark,
            'delivery_instructions' => $item->delivery_instructions,
            'delivery_preference' => $item->delivery_preference,
            'fulfillment_type' => $item->fulfillment_type?->value ?? $item->getRawOriginal('fulfillment_type'),
        ];
    }
}
