<?php

use App\Models\District;
use App\Models\Region;
use App\Models\ShipmentItem;

/*
 * The delivery-location label is built in memory (no database): it reads only
 * the parcel's own columns plus the region/district names, so each case can be
 * assembled from a bare model. These are the four shapes the SMS fallback has
 * to cover — dropdown, coordinates, gh_post and unknown — plus the two
 * "nothing usable" fallbacks.
 */

function labelledItem(array $attributes, ?string $regionName = null, ?string $districtName = null): ShipmentItem
{
    $item = new ShipmentItem($attributes);

    if ($regionName !== null) {
        $item->setRelation('deliveryRegion', new Region(['name' => $regionName]));
    }

    if ($districtName !== null) {
        $item->setRelation('deliveryDistrict', new District(['name' => $districtName]));
    }

    return $item;
}

test('dropdown address reads town, district and region', function () {
    $item = labelledItem([
        'delivery_region_id' => 1,
        'delivery_district_id' => 2,
        'delivery_town' => 'Lapaz',
    ], regionName: 'Greater Accra', districtName: 'Accra Metropolitan');

    expect($item->delivery_location_type)->toBe('dropdown')
        ->and($item->deliveryLocationLabel())->toBe('Lapaz, Accra Metropolitan, Greater Accra');
});

test('dropdown address drops a town that repeats the district', function () {
    $item = labelledItem([
        'delivery_region_id' => 1,
        'delivery_district_id' => 2,
        'delivery_town' => 'Kumasi',
    ], regionName: 'Ashanti', districtName: 'Kumasi');

    expect($item->deliveryLocationLabel())->toBe('Kumasi, Ashanti');
});

test('coordinates address prefers a town or landmark over the numbers', function () {
    $item = labelledItem([
        'delivery_latitude' => 5.556,
        'delivery_longitude' => -0.196,
        'delivery_landmark' => 'Blue Kiosk',
    ]);

    expect($item->delivery_location_type)->toBe('coordinates')
        ->and($item->deliveryLocationLabel())->toBe('Blue Kiosk');
});

test('coordinates address falls back to the pin numbers', function () {
    $item = labelledItem([
        'delivery_latitude' => 5.556,
        'delivery_longitude' => -0.196,
    ]);

    expect($item->deliveryLocationLabel())->toBe('GPS 5.55600, -0.19600');
});

test('gh_post address reads the code and the town', function () {
    $item = labelledItem([
        'delivery_gh_post_address' => 'GA-183-4634',
        'delivery_town' => 'Lapaz',
    ]);

    expect($item->delivery_location_type)->toBe('gh_post')
        ->and($item->deliveryLocationLabel())->toBe('GA-183-4634, Lapaz');
});

test('unknown address still reads whatever text exists', function () {
    $item = labelledItem([
        'delivery_landmark' => 'Blue Kiosk',
    ]);

    expect($item->delivery_location_type)->toBe('unknown')
        ->and($item->deliveryLocationLabel())->toBe('Blue Kiosk');
});

test('an address with nothing usable never returns an empty label', function () {
    $item = labelledItem([]);

    expect($item->deliveryLocationLabel())->toBe('the delivery address on file');
});
