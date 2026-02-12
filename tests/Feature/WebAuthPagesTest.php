<?php

test('landing page contains vendor and driver login options', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Vendor Login');
    $response->assertSee('Driver Login');
});

test('web auth pages are accessible', function () {
    $this->get('/vendor/login')->assertOk();
    $this->get('/vendor/home')->assertOk();
    $this->get('/vendor/profile')->assertOk();
    $this->get('/vendor/shipments')->assertOk();
    $this->get('/vendor/shipments/create')->assertOk();
    $this->get('/vendor/shipments/1')->assertOk();
    $this->get('/vendor/shipments/1/edit')->assertOk();
    $this->get('/vendor/invoices')->assertOk();
    $this->get('/vendor/invoices/1')->assertOk();
    $this->get('/driver/login')->assertOk();
    $this->get('/driver/home')->assertOk();
    $this->get('/driver/profile')->assertOk();
    $this->get('/driver/pickups')->assertOk();
    $this->get('/driver/pickups/1')->assertOk();
});

test('existing api tester and admin login routes remain accessible', function () {
    $this->get('/api-tester')->assertOk();
    $this->get('/admin/login')->assertOk();
});
