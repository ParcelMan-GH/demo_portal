@extends('warehouse.layouts.app')

@php
    $currentAdmin = Auth::guard('admin')->user();
    $isSelf = $currentAdmin?->id === $admin->id;
    $roleNames = $admin->roles->pluck('name')->values();
    $primaryRole = $roleNames->first() ?: 'No role assigned';
    $photoUrl = $admin->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($admin->photo_path) : null;
    $activityTotal = array_sum($tabCounts ?? []);
    $packageTotal = ($tabCounts['incoming-packages'] ?? 0) + ($tabCounts['warehouse-packages'] ?? 0);
    $phone = $admin->phone ?: '-';
    $email = $admin->email ?: '-';

    $userPayload = [
        'id' => $admin->id,
        'name' => $admin->name,
        'email' => $admin->email,
        'phone' => $admin->phone,
        'phone_input' => \App\Helpers\PhoneHelper::toLocal((string) $admin->phone) ?: $admin->phone,
        'avatar' => strtoupper(substr($admin->name, 0, 1)),
        'photo_url' => $photoUrl,
        'roles' => $admin->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values(),
        'warehouse' => $admin->warehouse ? ['id' => $admin->warehouse->id, 'name' => $admin->warehouse->name, 'code' => $admin->warehouse->code] : null,
        'is_active' => (bool) $admin->is_active,
        'is_self' => $isSelf,
        'creator' => $admin->creator?->name ?? 'System',
        'created_at' => $admin->created_at?->format('Y-m-d H:i:s'),
        'last_login_at' => $admin->last_login_at?->format('Y-m-d H:i:s'),
    ];

    $tabs = [
        ['key' => 'overview', 'label' => 'Overview', 'endpoint' => route('warehouse.users.overview-data', $admin), 'count' => $activityTotal],
        ['key' => 'orders', 'label' => 'Orders', 'endpoint' => route('warehouse.users.orders-data', $admin), 'count' => $tabCounts['orders'] ?? 0],
        ['key' => 'incoming-packages', 'label' => 'Incoming Packages', 'endpoint' => route('warehouse.users.incoming-packages-data', $admin), 'count' => $tabCounts['incoming-packages'] ?? 0],
        ['key' => 'warehouse-packages', 'label' => 'Warehouse Packages', 'endpoint' => route('warehouse.users.warehouse-packages-data', $admin), 'count' => $tabCounts['warehouse-packages'] ?? 0],
        ['key' => 'sorting', 'label' => 'Sorting', 'endpoint' => route('warehouse.users.sorting-data', $admin), 'count' => $tabCounts['sorting'] ?? 0],
        ['key' => 'recipient-desk', 'label' => 'Recipient Desk', 'endpoint' => route('warehouse.users.recipient-desk-data', $admin), 'count' => $tabCounts['recipient-desk'] ?? 0],
        ['key' => 'recipient-payments', 'label' => 'Recipient Payments', 'endpoint' => route('warehouse.users.recipient-payments-data', $admin), 'count' => $tabCounts['recipient-payments'] ?? 0],
        ['key' => 'transport-manifests', 'label' => 'Transport Manifests', 'endpoint' => route('warehouse.users.transport-manifests-data', $admin), 'count' => $tabCounts['transport-manifests'] ?? 0],
        ['key' => 'incoming-manifests', 'label' => 'Incoming Manifests', 'endpoint' => route('warehouse.users.incoming-manifests-data', $admin), 'count' => $tabCounts['incoming-manifests'] ?? 0],
        ['key' => 'delivery-runs', 'label' => 'Delivery Runs', 'endpoint' => route('warehouse.users.delivery-runs-data', $admin), 'count' => $tabCounts['delivery-runs'] ?? 0],
        ['key' => 'pending-confirmations', 'label' => 'Pending Confirmations', 'endpoint' => route('warehouse.users.pending-confirmations-data', $admin), 'count' => $tabCounts['pending-confirmations'] ?? 0],
        ['key' => 'team-actions', 'label' => 'Team Actions', 'endpoint' => route('warehouse.users.team-actions-data', $admin), 'count' => $tabCounts['team-actions'] ?? 0],
        ['key' => 'hq-controls', 'label' => 'HQ Controls', 'endpoint' => route('warehouse.users.hq-controls-data', $admin), 'count' => $tabCounts['hq-controls'] ?? 0],
        ['key' => 'security-log', 'label' => 'Security Log', 'endpoint' => route('warehouse.users.security-log-data', $admin), 'count' => $tabCounts['security-log'] ?? 0],
    ];

    $config = [
        'user' => $userPayload,
        'tabs' => $tabs,
        'updateEndpoint' => route('warehouse.users.update', $admin),
        'toggleActiveEndpoint' => route('warehouse.users.toggle-active', $admin),
        'impersonateEndpoint' => route('warehouse.users.impersonate', $admin),
        'indexUrl' => route('warehouse.users.index'),
        'csrfToken' => csrf_token(),
        'canManage' => (bool) $canManage,
        'canImpersonate' => (bool) $canImpersonate,
    ];
@endphp

@section('title', $admin->name . ' - User Activity')

@section('content')
<div x-data="userShow()" x-init="init()" data-user-show-config='@json($config)' class="space-y-6">
    <section class="overflow-hidden rounded-[2rem] bg-slate-950 text-white shadow-xl shadow-slate-900/10">
        <div class="relative isolate p-5 sm:p-8 lg:p-10">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_85%_0%,rgba(255,91,0,0.28),transparent_34%),linear-gradient(135deg,#020617_0%,#0f172a_58%,#351b1b_100%)]"></div>

            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('warehouse.users.index') }}" class="inline-flex w-fit items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-black text-white transition hover:bg-white/15">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back
                </a>

                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <span class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm font-black {{ $admin->is_active ? 'border-emerald-400/30 bg-emerald-400/15 text-emerald-100' : 'border-rose-400/30 bg-rose-400/15 text-rose-100' }}">
                        <span class="h-2.5 w-2.5 rounded-full {{ $admin->is_active ? 'bg-emerald-300' : 'bg-rose-300' }}"></span>
                        {{ $admin->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($canManage)
                        <button type="button" @@click="openEditModal()" class="inline-flex items-center gap-2 rounded-2xl border border-orange-400/35 bg-orange-500/10 px-4 py-2 text-sm font-black text-orange-100 transition hover:bg-orange-500/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                            </svg>
                            Edit
                        </button>
                        @unless($isSelf)
                            <button type="button" @@click="openStatusModal()" class="inline-flex items-center gap-2 rounded-2xl border border-amber-400/35 bg-amber-500/10 px-4 py-2 text-sm font-black text-amber-100 transition hover:bg-amber-500/20">
                                {{ $admin->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        @endunless
                    @endif
                    @if($canImpersonate)
                        <button type="button" @@click="openImpersonationModal()" class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-black text-white transition hover:bg-white/15">
                            Login as
                        </button>
                    @endif
                </div>
            </div>

            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_minmax(460px,0.95fr)] xl:items-center">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                    <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-[1.75rem] bg-orange-600 text-5xl font-black text-white shadow-2xl shadow-orange-950/40 sm:h-32 sm:w-32">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr($admin->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-[0.35em] text-orange-100/90">User Activity</p>
                        <h1 class="mt-3 break-words text-4xl font-black tracking-tight sm:text-5xl">{{ $admin->name }}</h1>
                        <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-base font-bold text-slate-300">
                            <span>{{ $phone }}</span>
                            <span class="text-slate-500">/</span>
                            <span>{{ $email }}</span>
                            <span class="text-slate-500">/</span>
                            <span>{{ $admin->warehouse?->name ?? 'No warehouse' }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-black text-slate-400">
                            <span>{{ $primaryRole }}</span>
                            <span class="text-slate-600">/</span>
                            <span>Last login {{ $admin->last_login_at?->format('M j, Y, h:i A') ?? 'Never' }}</span>
                            <span class="text-slate-600">/</span>
                            <span>Created {{ $admin->created_at?->format('M j, Y, h:i A') }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-orange-100">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25A2.25 2.25 0 0 1 5.25 3h13.5A2.25 2.25 0 0 1 21 5.25Z"/>
                            </svg>
                        </div>
                        <p class="text-3xl font-black">{{ number_format($activityTotal) }}</p>
                        <p class="mt-1 text-sm font-black text-slate-400">Activity records</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-orange-100">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25m0-9L3 7.5m9 5.25v9M3 7.5v9l9 5.25"/>
                            </svg>
                        </div>
                        <p class="text-3xl font-black">{{ number_format($tabCounts['orders'] ?? 0) }}</p>
                        <p class="mt-1 text-sm font-black text-slate-400">{{ number_format($packageTotal) }} packages</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-emerald-100">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5A2.25 2.25 0 0 0 19.5 19.5v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                        </div>
                        <p class="text-3xl font-black">{{ number_format($tabCounts['security-log'] ?? 0) }}</p>
                        <p class="mt-1 text-sm font-black text-slate-400">Security events</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
        <p class="sr-only">User activity tabs</p>
        <div class="flex flex-wrap items-center gap-2">
            @foreach($tabs as $tab)
                <button type="button"
                        @@click="setActiveTab('{{ $tab['key'] }}')"
                        class="group inline-flex w-auto shrink-0 items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                        :class="activeTab === '{{ $tab['key'] }}' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                    <span>{{ $tab['label'] }}</span>
                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-black"
                          :class="activeTab === '{{ $tab['key'] }}' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'">{{ number_format($tab['count']) }}</span>
                </button>
            @endforeach
        </div>
    </section>

    <section class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="min-w-0 bg-white">
            <template x-if="activeTab === 'overview'">
                <div class="space-y-5 p-5">
                    <div class="grid gap-4 md:grid-cols-4">
                        <template x-for="card in overviewCards()" :key="card.label">
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <p class="text-xs font-black uppercase tracking-wide text-slate-500" x-text="card.label"></p>
                                <p class="mt-3 text-3xl font-black text-slate-950" x-text="card.value"></p>
                                <p class="mt-1 text-sm font-semibold text-slate-500" x-text="card.note"></p>
                            </div>
                        </template>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-100 p-5">
                            <h2 class="text-xl font-black text-slate-950">Recent activity</h2>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-if="overviewLoading">
                                <div class="p-8 text-center text-sm font-bold text-slate-500">Loading activity...</div>
                            </template>
                            <template x-if="!overviewLoading && overview.recent.length === 0">
                                <div class="p-8 text-center text-sm font-bold text-slate-500">No activity recorded yet.</div>
                            </template>
                            <template x-for="row in overview.recent" :key="row.id">
                                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-sm font-black text-slate-950" x-text="row.action"></p>
                                        <p class="mt-1 text-sm font-semibold text-slate-500" x-text="`${row.module} · ${row.reference} · ${formatDate(row.date)}`"></p>
                                    </div>
                                    <span class="w-fit rounded-full border border-slate-200 px-3 py-1 text-xs font-black uppercase tracking-wide text-slate-500" x-text="row.status"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="activeTab !== 'overview'">
                <div>
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 xl:flex-row xl:items-end xl:justify-between">
                        <div class="relative w-full xl:max-w-md">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                            <input type="search"
                                   x-model.debounce.350ms="currentState().search"
                                   @@input="loadCurrentTab(1)"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                   :placeholder="`Search ${currentTab().label.toLowerCase()}...`">
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                            </svg>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <button type="button"
                                    @@click="currentState().filtersOpen = !currentState().filtersOpen"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                    :class="currentState().filtersOpen ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                                <span x-text="currentState().filtersOpen ? 'Hide Filters' : 'Filters'"></span>
                            </button>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                    View
                                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                                    <template x-for="col in currentState().columns" :key="col.key">
                                        <button type="button" @@click="toggleColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                            <span x-text="col.label"></span>
                                            <svg x-show="currentState().visibleColumns[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                                    Export
                                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                                </button>
                                <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                                    <button type="button" @@click="exportCurrent('csv'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="currentState().filtersOpen" x-transition class="border-b border-slate-100 px-5 pb-4" style="display:none">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="grid gap-4 md:grid-cols-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Activity Date</label>
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25m10.5-2.25v2.25M3 18.75V8.25A2.25 2.25 0 0 1 5.25 6h13.5A2.25 2.25 0 0 1 21 8.25v10.5A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75Z"/>
                                    </svg>
                                    <input type="text" x-ref="dateRangeInput" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Select date range">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                                <input type="text" x-model="currentState().status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Any status">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Action</label>
                                <input type="text" x-model="currentState().action" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Action contains">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Rows</label>
                                <select x-model.number="currentState().perPage" @@change="loadCurrentTab(1)" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="10">10 rows</option>
                                    <option value="25">25 rows</option>
                                    <option value="50">50 rows</option>
                                    <option value="100">100 rows</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="currentState().filtersOpen = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                            <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                            <button type="button" @@click="loadCurrentTab(1)" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                        </div>
                        </div>
                    </div>

                    <div class="relative overflow-hidden bg-white">
                        <div x-show="currentState().loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[1120px] table-auto divide-y divide-slate-200/60 text-xs">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th x-show="currentState().visibleColumns.date" class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Date</th>
                                        <th x-show="currentState().visibleColumns.module" class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Module</th>
                                        <th x-show="currentState().visibleColumns.reference" class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Reference</th>
                                        <th x-show="currentState().visibleColumns.action" class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Action</th>
                                        <th x-show="currentState().visibleColumns.status" class="whitespace-nowrap px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Status</th>
                                        <th x-show="currentState().visibleColumns.details" class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Details</th>
                                        <th x-show="currentState().visibleColumns.warehouse" class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Warehouse</th>
                                        <th class="whitespace-nowrap px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-if="currentState().loading">
                                        <tr><td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-xs text-slate-500">Loading records...</td></tr>
                                    </template>
                                    <template x-if="!currentState().loading && currentState().rows.length === 0">
                                        <tr><td :colspan="visibleColumnCount()" class="px-4 py-8 text-center text-xs text-slate-500">No records found for this tab.</td></tr>
                                    </template>
                                    <template x-for="row in currentState().rows" :key="row.id">
                                        <tr class="hover:bg-slate-50/70">
                                            <td x-show="currentState().visibleColumns.date" class="whitespace-nowrap px-4 py-3 text-xs text-slate-600" x-text="formatDate(row.date)"></td>
                                            <td x-show="currentState().visibleColumns.module" class="whitespace-nowrap px-4 py-3">
                                                <span class="inline-flex items-center rounded-full bg-orange-50 px-2 py-0.5 text-[10px] font-semibold text-orange-700" x-text="row.module"></span>
                                            </td>
                                            <td x-show="currentState().visibleColumns.reference" class="whitespace-nowrap px-4 py-3 text-xs font-semibold text-slate-900" x-text="row.reference"></td>
                                            <td x-show="currentState().visibleColumns.action" class="whitespace-nowrap px-4 py-3 text-xs text-slate-600" x-text="row.action"></td>
                                            <td x-show="currentState().visibleColumns.status" class="whitespace-nowrap px-4 py-3 text-center">
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="statusClass(row.status)" x-text="row.status"></span>
                                            </td>
                                            <td x-show="currentState().visibleColumns.details" class="min-w-[280px] px-4 py-3 text-xs text-slate-600" x-text="row.details"></td>
                                            <td x-show="currentState().visibleColumns.warehouse" class="whitespace-nowrap px-4 py-3 text-xs text-slate-600" x-text="row.warehouse"></td>
                                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                                <template x-if="row.view_url">
                                                    <a :href="row.view_url" class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[11px] font-semibold text-orange-700 transition-colors hover:bg-orange-100">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/></svg>
                                                        View
                                                    </a>
                                                </template>
                                                <template x-if="!row.view_url">
                                                    <span class="text-slate-300">-</span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="text-xs font-semibold text-slate-600" x-text="paginationLabel()"></div>
                                <div class="flex items-center gap-1">
                                    <button type="button" @@click="previousPage()" :disabled="currentState().meta.current_page <= 1 || currentState().loading" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
                                    </button>
                                    <div class="px-2 text-xs font-black text-slate-700" x-text="`Page ${currentState().meta.current_page || 1} / ${currentState().meta.last_page || 1}`"></div>
                                    <button type="button" @@click="nextPage()" :disabled="currentState().meta.current_page >= currentState().meta.last_page || currentState().loading" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </section>

    @include('warehouse.users.partials.user-modal')

    <template x-teleport="body">
        <div x-show="showStatusModal" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm" @@click.self="closeStatusModal()">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                <h3 class="text-2xl font-black text-slate-950" x-text="user.is_active ? 'Deactivate user?' : 'Activate user?'"></h3>
                <p class="mt-2 text-sm font-semibold text-slate-500">This changes whether the account can access the back office.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @@click="closeStatusModal()" class="rounded-2xl border-2 border-slate-200 px-5 py-3 text-sm font-black text-slate-700">Cancel</button>
                    <button type="button" @@click="submitStatusToggle()" :disabled="statusSubmitting" class="rounded-2xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white disabled:opacity-50" x-text="user.is_active ? 'Deactivate' : 'Activate'"></button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="showImpersonationModal" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm" @@click.self="closeImpersonationModal()">
            <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
                <h3 class="text-2xl font-black text-slate-950">Login as this user?</h3>
                <p class="mt-2 text-sm font-semibold text-slate-500">You will switch into this account in the same browser session. Actions are audited.</p>
                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="font-black text-slate-950" x-text="user.name"></p>
                    <p class="mt-1 text-sm font-semibold text-slate-500" x-text="`${user.phone || '-'} · ${user.warehouse?.name || 'No warehouse'}`"></p>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @@click="closeImpersonationModal()" class="rounded-2xl border-2 border-slate-200 px-5 py-3 text-sm font-black text-slate-700">Cancel</button>
                    <button type="button" @@click="startImpersonation()" :disabled="impersonationSubmitting" class="rounded-2xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white disabled:opacity-50">Login as user</button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
