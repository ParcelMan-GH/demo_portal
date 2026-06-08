<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // The legal pages read contact details via PlatformSetting::getValue().
    if (!Schema::hasTable('platform_settings')) {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });
    }
});

test('privacy policy page is accessible', function () {
    $response = $this->get('/privacy-policy');

    $response->assertOk();
    $response->assertSee('Privacy Policy');
    $response->assertSee('Parcelman');
});

test('terms of service page is accessible', function () {
    $response = $this->get('/terms-of-service');

    $response->assertOk();
    $response->assertSee('Terms of Service');
    $response->assertSee('Parcelman');
});

test('legal routes are named', function () {
    expect(route('web.privacy'))->toContain('/privacy-policy')
        ->and(route('web.terms'))->toContain('/terms-of-service');
});
