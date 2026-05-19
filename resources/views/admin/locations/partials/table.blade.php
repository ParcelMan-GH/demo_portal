@php
    $minWidth = $tab === 'towns' ? 'min-w-[900px]' : 'min-w-[720px]';
@endphp
<table class="{{ $minWidth }} w-full table-fixed divide-y divide-slate-200/50 text-xs">
    <thead class="bg-slate-50/50">
        <tr>
            @if($tab === 'regions')
                <th x-show="regionVisible.name" @click="sortR('name')" class="w-[220px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Region</th>
                <th x-show="regionVisible.code" @click="sortR('code')" class="w-[140px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Code</th>
                <th x-show="regionVisible.districts" @click="sortR('districts_count')" class="w-[120px] cursor-pointer px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Districts</th>
                <th x-show="regionVisible.towns" @click="sortR('locations_count')" class="w-[120px] cursor-pointer px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Towns</th>
                <th x-show="regionVisible.status" class="w-[120px] px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="w-[220px] px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
            @elseif($tab === 'districts')
                <th x-show="districtVisible.name" @click="sortD('name')" class="w-[220px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">District</th>
                <th x-show="districtVisible.code" @click="sortD('code')" class="w-[140px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Code</th>
                <th x-show="districtVisible.region" @click="sortD('region_name')" class="w-[220px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Region</th>
                <th x-show="districtVisible.towns" @click="sortD('locations_count')" class="w-[120px] cursor-pointer px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Towns</th>
                <th x-show="districtVisible.status" class="w-[120px] px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="w-[220px] px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
            @else
                <th x-show="townVisible.name" @click="sortT('name')" class="w-[220px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Town / City</th>
                <th x-show="townVisible.type" class="w-[120px] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Type</th>
                <th x-show="townVisible.district" @click="sortT('district_name')" class="w-[220px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">District</th>
                <th x-show="townVisible.region" @click="sortT('region_name')" class="w-[220px] cursor-pointer px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Region</th>
                <th x-show="townVisible.status" class="w-[120px] px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="w-[220px] px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
            @endif
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100/50 bg-transparent">
        <template x-if="tableConfig('{{ $tab }}').rows().length === 0 && !{{ ['regions' => 'loadingRegions', 'districts' => 'loadingDistricts', 'towns' => 'loadingTowns'][$tab] }}">
            <tr><td colspan="6" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No records match the current filters.</td></tr>
        </template>
        <template x-for="item in tableConfig('{{ $tab }}').rows()" :key="item.id">
            <tr class="hover:bg-slate-50/70">
                @if($tab === 'regions')
                    <td x-show="regionVisible.name" class="px-4 py-3 align-top font-bold text-slate-900" x-text="item.name"></td>
                    <td x-show="regionVisible.code" class="px-4 py-3 align-top font-mono font-semibold text-slate-600" x-text="item.code"></td>
                    <td x-show="regionVisible.districts" class="px-4 py-3 text-center align-top"><button @click="filterDistricts(item)" class="font-bold text-orange-700 hover:underline" x-text="item.districts_count || 0"></button></td>
                    <td x-show="regionVisible.towns" class="px-4 py-3 text-center align-top font-bold text-slate-900" x-text="item.locations_count || 0"></td>
                    <td x-show="regionVisible.status" class="px-4 py-3 text-center align-top">@include('admin.locations.partials.status')</td>
                    <td class="px-4 py-3 text-right align-top">@include('admin.locations.partials.actions', ['type' => 'regions'])</td>
                @elseif($tab === 'districts')
                    <td x-show="districtVisible.name" class="px-4 py-3 align-top font-bold text-slate-900" x-text="item.name"></td>
                    <td x-show="districtVisible.code" class="px-4 py-3 align-top font-mono font-semibold text-slate-600" x-text="item.code"></td>
                    <td x-show="districtVisible.region" class="px-4 py-3 align-top font-semibold text-slate-700" x-text="item.region_name"></td>
                    <td x-show="districtVisible.towns" class="px-4 py-3 text-center align-top"><button @click="filterTowns(item)" class="font-bold text-orange-700 hover:underline" x-text="item.locations_count || 0"></button></td>
                    <td x-show="districtVisible.status" class="px-4 py-3 text-center align-top">@include('admin.locations.partials.status')</td>
                    <td class="px-4 py-3 text-right align-top">@include('admin.locations.partials.actions', ['type' => 'districts'])</td>
                @else
                    <td x-show="townVisible.name" class="px-4 py-3 align-top font-bold text-slate-900" x-text="item.name"></td>
                    <td x-show="townVisible.type" class="px-4 py-3 align-top"><span class="capitalize font-semibold text-slate-700" x-text="item.type"></span></td>
                    <td x-show="townVisible.district" class="px-4 py-3 align-top font-semibold text-slate-700" x-text="item.district_name"></td>
                    <td x-show="townVisible.region" class="px-4 py-3 align-top font-semibold text-slate-700" x-text="item.region_name"></td>
                    <td x-show="townVisible.status" class="px-4 py-3 text-center align-top">@include('admin.locations.partials.status')</td>
                    <td class="px-4 py-3 text-right align-top">@include('admin.locations.partials.actions', ['type' => 'towns'])</td>
                @endif
            </tr>
        </template>
    </tbody>
</table>
