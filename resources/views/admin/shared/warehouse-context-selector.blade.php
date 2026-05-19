@php
    $warehouses = $backOfficeWarehouses ?? collect();
    $selectedWarehouse = $backOfficeSelectedWarehouse ?? null;
    $currentWarehouse = $backOfficeCurrentWarehouse ?? null;
    $isHq = (bool) ($backOfficeIsHq ?? false);
    $canSwitch = (bool) ($backOfficeCanSwitchWarehouse ?? false);
    $scopeLabel = $backOfficeScopeLabel ?? ($currentWarehouse?->name ?? 'Warehouse');
@endphp

@if($canSwitch)
    <form action="{{ route('admin.context.warehouse.update') }}" method="POST" class="hidden sm:block">
        @csrf
        <label class="sr-only" for="backoffice-warehouse-scope">Warehouse scope</label>
        <select
            id="backoffice-warehouse-scope"
            name="warehouse_id"
            onchange="this.form.submit()"
            class="h-9 max-w-[220px] rounded-xl border border-slate-200/80 bg-white/90 px-3 pr-8 text-[12px] font-semibold text-slate-700 shadow-sm outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/15"
        >
            @if($isHq)
                <option value="all" @selected(!$selectedWarehouse)>All warehouses</option>
            @endif
            @foreach($warehouses as $warehouseOption)
                <option value="{{ $warehouseOption->id }}" @selected($selectedWarehouse?->id === $warehouseOption->id || (!$isHq && $currentWarehouse?->id === $warehouseOption->id))>
                    {{ $warehouseOption->name }}
                </option>
            @endforeach
        </select>
    </form>
@else
    <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-xl border bg-slate-50/80 border-slate-200/60">
        <div class="w-2 h-2 rounded-full bg-orange-500"></div>
        <span class="max-w-[180px] truncate text-[11px] font-semibold text-slate-600">{{ $scopeLabel }}</span>
    </div>
@endif
