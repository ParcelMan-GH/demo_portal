<?php

use Illuminate\Support\Facades\Artisan;

test('warehouse dispatch backfill command is registered', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('warehouse:dispatch-backfill');
});

