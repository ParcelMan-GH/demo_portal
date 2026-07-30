@extends('web.layouts.portal')

@section('title', 'Parcelman Ghana | Parcel pickup and delivery for vendors')
@section('meta_description', 'Parcelman Ghana helps vendors, shops, and social sellers request parcel pickups, track delivery progress, and manage parcel history from a mobile app.')
@section('meta_keywords', 'Parcelman Ghana, parcel delivery Ghana, vendor delivery Ghana, pickup request app Ghana, delivery app for Ghanaian vendors, parcel pickup Ghana')

@section('content')
@php
    $parcelmanFaqs = [
        ['Can I use Parcelman for my shop or social store?', 'Yes. Parcelman is designed for online vendors, retail shops, and businesses that send customer orders.'],
        ['Do I need the mobile app?', 'Yes. Parcelman is app-led so vendors can request pickup, track parcels and manage delivery records from their phone.'],
        ['What details do I add to a pickup request?', 'You can add pickup location, destination, parcel notes, photos, and delivery preferences so the request is easier to review.'],
        ['Can I choose the kind of vehicle I need?', 'The app supports vehicle request options such as motorbike, aboboyaa, van, and truck when a parcel needs a specific pickup type.'],
        ['Can customers receive delivery updates?', 'Parcelman helps keep delivery progress clearer so vendors can answer customers with more confidence.'],
        ['Do you support payment on delivery or same-day delivery?', 'Those options should be confirmed during onboarding because availability can depend on the service area and current operations.'],
    ];
    $hasPlatformSettings = \Illuminate\Support\Facades\Schema::hasTable('platform_settings');
    $platformPhone = $hasPlatformSettings ? trim((string) \App\Models\PlatformSetting::getValue('platform_phone', '+233 55 406 4189')) : '+233 55 406 4189';
    $platformEmail = $hasPlatformSettings ? trim((string) \App\Models\PlatformSetting::getValue('platform_email', 'support@parcelmanexpress.com')) : 'support@parcelmanexpress.com';
    $platformAddress = $hasPlatformSettings ? trim((string) \App\Models\PlatformSetting::getValue('platform_address', 'Circle Same Building as IPMC, Accra')) : 'Circle Same Building as IPMC, Accra';
@endphp

<main class="pm-page" x-data="{ mobileOpen: false }">
    <x-landing.header />
    <x-landing.hero />
    <x-landing.problems />
    <x-landing.solutions />
    <x-landing.how-it-works />
    <x-landing.app-banner />
    
    <x-landing.contact 
        :phone="$platformPhone" 
        :email="$platformEmail" 
        :address="$platformAddress" 
    />

    <x-landing.faq :faqs="$parcelmanFaqs" />
    <x-landing.newsletter />
    
    <x-landing.footer 
        :phone="$platformPhone" 
        :email="$platformEmail" 
    />
</main>
@endsection