<?php

use Illuminate\Support\Facades\Artisan;

test('warehouse receipts backfill command is registered', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('warehouse:receipts-backfill');
});
