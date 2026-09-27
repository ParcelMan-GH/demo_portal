@extends('admin.layouts.app')

@section('title', 'Vehicle Fleet')
@section('breadcrumb-parent', 'Operations')
@section('breadcrumb-current', 'Vehicle Fleet')

@section('content')
<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Vehicle Fleet</h1>
            <p class="mt-1 text-sm font-medium text-slate-500">
                Control which vehicle types vendors can request for a pickup. Locking a type disables it in the
                vendor app straight away; unlocking it makes it selectable again.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold text-slate-700">
                {{ $summary['total'] }} types
            </span>
            <span class="inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">
                {{ $summary['available'] }} available
            </span>
            <span class="inline-flex items-center gap-2 rounded-xl bg-amber-50 px-3 py-2 text-sm font-bold text-amber-700 ring-1 ring-amber-100">
                {{ $summary['locked'] }} locked
            </span>
        </div>
    </div>

    {{-- Vehicle type management: same UI and endpoints as Admin -> Settings --}}
    @include('admin.settings.tabs.pickup-vehicles', ['tabData' => ['vehicleTypes' => $vehicleTypes]])
</div>

<script>
    window.settingsConfig = window.settingsConfig || {};
    window.settingsConfig.csrfToken = @json(csrf_token());
    window.settingsConfig.pickupVehiclesStoreEndpoint = @json(route('admin.settings.pickup-vehicles.store'));
    window.settingsConfig.pickupVehiclesUpdateEndpoint = @json(route('admin.settings.pickup-vehicles.update', ['pickupVehicleType' => '__ID__']));
    window.settingsConfig.pickupVehiclesToggleEndpoint = @json(route('admin.settings.pickup-vehicles.toggle', ['pickupVehicleType' => '__ID__']));
    window.settingsConfig.pickupVehiclesDeleteEndpoint = @json(route('admin.settings.pickup-vehicles.delete', ['pickupVehicleType' => '__ID__']));
</script>
@endsection
