<?php

use App\Models\PickupVehicleType;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function vpvaVendor(): Vendor
{
    return Vendor::create([
        'name' => 'Vehicle Fleet Vendor',
        'business_name' => 'Fleet Test Logistics',
        'phone' => '+2332'.random_int(10000000, 99999999),
        'email' => 'fleet-'.Str::lower(Str::random(8)).'@example.test',
        'is_active' => true,
    ]);
}

function vpvaTypes(): Illuminate\Support\Collection
{
    return collect(
        test()->getJson('/api/v1/vendor/pickup-vehicle-types')->assertOk()->json('data.vehicle_types')
    );
}

it('ships locked vehicle types flagged unavailable instead of hiding them', function () {
    Sanctum::actingAs(vpvaVendor());

    $types = vpvaTypes();

    // Van and Truck ship locked: that is the state the app used to hardcode.
    $van = $types->firstWhere('slug', 'van');
    expect($van)->not->toBeNull();
    expect($van['is_active'])->toBeFalse();
    expect($van['is_available'])->toBeFalse();

    $truck = $types->firstWhere('slug', 'truck');
    expect($truck['is_active'])->toBeFalse();

    // A selectable type is still offered normally.
    expect($types->firstWhere('slug', 'motorbike')['is_active'])->toBeTrue();

    // Vendors see what they can pick first.
    expect($types->first()['is_active'])->toBeTrue();
});

it('unlocks a vehicle type the moment an admin activates it', function () {
    $van = PickupVehicleType::query()->where('slug', 'van')->firstOrFail();
    expect($van->is_active)->toBeFalse();

    // Exactly what the Vehicle Fleet panel's toggle does.
    $van->update(['is_active' => true]);

    Sanctum::actingAs(vpvaVendor());

    $types = vpvaTypes();

    expect($types->firstWhere('slug', 'van')['is_active'])->toBeTrue();
    expect($types->firstWhere('slug', 'van')['is_available'])->toBeTrue();
});

it('locks a vehicle type again when an admin deactivates it', function () {
    $motorbike = PickupVehicleType::query()->where('slug', 'motorbike')->firstOrFail();
    $motorbike->update(['is_active' => false]);

    Sanctum::actingAs(vpvaVendor());

    $types = vpvaTypes();

    $motorbike = $types->firstWhere('slug', 'motorbike');
    expect($motorbike)->not->toBeNull();
    expect($motorbike['is_active'])->toBeFalse();
});

it('can still return selectable options only', function () {
    Sanctum::actingAs(vpvaVendor());

    $types = collect(
        test()->getJson('/api/v1/vendor/pickup-vehicle-types?active_only=1')
            ->assertOk()
            ->json('data.vehicle_types')
    );

    expect($types)->not->toBeEmpty();
    expect($types->pluck('slug')->all())->not->toContain('van');
    expect($types->every(fn (array $type) => $type['is_active'] === true))->toBeTrue();
});
