@extends('admin.layouts.app')

@section('title', 'Warehouse - ' . $warehouse->name)
@section('breadcrumb-parent', 'Warehouses')
@section('breadcrumb-current', $warehouse->name)

@php
$warehouseConfig = [
    'warehouse' => $warehouse,
    'updateEndpoint' => route('admin.warehouses.update', $warehouse),
    'toggleActiveEndpoint' => route('admin.warehouses.toggle-active', $warehouse),
    'capabilitiesEndpoint' => route('admin.warehouses.capabilities.index', $warehouse),
    'capabilitiesUpdateEndpoint' => route('admin.warehouses.capabilities.update', $warehouse),
    'capabilityWarehouses' => $capabilityWarehouses->map(fn($item) => [
        'id' => $item->id,
        'name' => $item->name,
        'code' => $item->code,
        'is_hq' => (bool) $item->is_hq,
    ])->values(),
    'canManageCapabilities' => $canManageCapabilities,
    'isHqWarehouse' => (bool) ($warehouse->is_hq && $warehouse->can_administer_system),
    'canManage' => $canManage,
];

$warehouseUsersTableConfig = [
    'endpoint' => route('admin.warehouses.users.data', $warehouse),
    'exportEndpoint' => route('admin.warehouses.users.export', $warehouse),
    'createEndpoint' => route('admin.admins.store'),
    'warehouseId' => $warehouse->id,
    'csrfToken' => csrf_token(),
    'canCreateUsers' => $canCreateUsers,
    'roles' => $userRoles->map(fn($role) => ['id' => $role->id, 'name' => $role->name])->values(),
];

$receivedItemsFlat = $receivedItems->map(fn($c) => [
    'id'               => $c->id,
    '_primary'         => $c->shipmentItem?->description ?? 'Package',
    '_secondary'       => ($c->shipmentItem?->shipment?->shipment_number ?? '-') . ' / ' . ($c->pickupAssignment?->driver?->name ?? 'No rider'),
    'confirmed_at'     => $c->confirmed_at?->format('d M Y, h:i A') ?? '-',
    'shipment_number'  => $c->shipmentItem?->shipment?->shipment_number ?? '-',
    'item_description' => $c->shipmentItem?->description ?? '-',
    'qty'              => ($c->confirmed_quantity ?? 0) . ' / ' . ($c->expected_quantity ?? 0),
    'driver'           => $c->pickupAssignment?->driver?->name ?? '-',
    'notes'            => $c->notes ?: '-',
    'view_url'         => $c->shipmentItem?->shipment?->id ? route('admin.shipments.show', $c->shipmentItem->shipment->id) : '#',
])->values()->all();

$statusBadge = function ($status) {
    $sv = is_object($status) ? $status->value : (string) $status;
    return match ($sv) {
        'assigned' => 'bg-amber-100 text-amber-700',
        'en_route', 'en_route_to_warehouse', 'loading', 'out_for_delivery' => 'bg-blue-100 text-blue-700',
        'arrived_warehouse', 'arrived' => 'bg-violet-100 text-violet-700',
        'received', 'completed', 'delivered', 'sealed' => 'bg-emerald-100 text-emerald-700',
        'cancelled' => 'bg-red-100 text-red-700',
        'draft', 'open' => 'bg-slate-100 text-slate-700',
        default => 'bg-slate-100 text-slate-700',
    };
};
$statusLabel = fn($status) => is_object($status) && method_exists($status, 'label') ? $status->label() : ucwords(str_replace('_', ' ', (string) $status));

$receivedPickupsFlat = $receivedPickups->map(fn($p) => [
    'id' => $p->id,
    '_primary' => $p->shipment?->shipment_number ?? 'Pickup',
    '_secondary' => ($p->driver?->name ?? 'No rider') . ($p->driver?->phone ? ' / ' . $p->driver->phone : ''),
    'shipment_number' => $p->shipment?->shipment_number ?? '-',
    'driver' => trim(($p->driver?->name ?? '-') . ($p->driver?->phone ? ' / ' . $p->driver->phone : '')),
    'status_label' => $statusLabel($p->status),
    'status_badge_class' => $statusBadge($p->status),
    'arrived_warehouse_at' => $p->arrived_warehouse_at?->format('d M Y, h:i A') ?? '-',
    'received_at' => $p->received_at?->format('d M Y, h:i A') ?? '-',
    'notes' => $p->receive_notes ?: '-',
    'view_url' => $p->shipment?->id ? route('admin.shipments.show', $p->shipment->id) : '#',
])->values()->all();

$pendingReceiptsFlat = $pendingReceipts->map(fn($p) => [
    'id' => $p->id,
    '_primary' => $p->shipment?->shipment_number ?? 'Receipt',
    '_secondary' => ($p->driver?->name ?? 'No rider') . ($p->driver?->phone ? ' / ' . $p->driver->phone : ''),
    'shipment_number' => $p->shipment?->shipment_number ?? '-',
    'driver' => trim(($p->driver?->name ?? '-') . ($p->driver?->phone ? ' / ' . $p->driver->phone : '')),
    'status_label' => $statusLabel($p->status),
    'status_badge_class' => $statusBadge($p->status),
    'assigned_at' => $p->assigned_at?->format('d M Y, h:i A') ?? '-',
    'arrived_warehouse_at' => $p->arrived_warehouse_at?->format('d M Y, h:i A') ?? '-',
    'view_url' => $p->shipment?->id ? route('admin.shipments.show', $p->shipment->id) : '#',
])->values()->all();

$sortBatchesFlat = $sortBatchesOrigin->map(fn($b) => [
    'id' => $b->id,
    '_primary' => $b->batch_number,
    '_secondary' => 'To ' . ($b->destinationWarehouse?->name ?? 'Local delivery'),
    'batch_number' => $b->batch_number,
    'direction' => 'Outgoing',
    'direction_class' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
    'other_warehouse' => $b->destinationWarehouse?->name ?? 'Local delivery',
    'dispatch_mode' => $b->dispatch_mode === 'transfer' ? 'Transfer' : 'Local Delivery',
    'status' => ucfirst((string) $b->status),
    'status_badge_class' => $statusBadge($b->status),
    'items' => $b->active_items_count ?? 0,
    'sealed_at' => $b->sealed_at?->format('d M Y, h:i A') ?? '-',
    'created_at' => $b->created_at?->format('d M Y, h:i A') ?? '-',
    'view_url' => route('admin.sort-batches.show', $b->id),
])->toBase()->merge($sortBatchesDest->map(fn($b) => [
    'id' => $b->id,
    '_primary' => $b->batch_number,
    '_secondary' => 'From ' . ($b->originWarehouse?->name ?? 'Unknown warehouse'),
    'batch_number' => $b->batch_number,
    'direction' => 'Incoming',
    'direction_class' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
    'other_warehouse' => $b->originWarehouse?->name ?? '-',
    'dispatch_mode' => $b->dispatch_mode === 'transfer' ? 'Transfer' : 'Local Delivery',
    'status' => ucfirst((string) $b->status),
    'status_badge_class' => $statusBadge($b->status),
    'items' => $b->active_items_count ?? 0,
    'sealed_at' => $b->sealed_at?->format('d M Y, h:i A') ?? '-',
    'created_at' => $b->created_at?->format('d M Y, h:i A') ?? '-',
    'view_url' => route('admin.sort-batches.show', $b->id),
])->toBase())->sortByDesc('created_at')->values()->all();

$manifestsFlat = $manifestsOutgoing->map(fn($m) => [
    'id' => $m->id,
    '_primary' => $m->manifest_number,
    '_secondary' => 'To ' . ($m->destinationWarehouse?->name ?? 'No destination'),
    'manifest_number' => $m->manifest_number,
    'direction' => 'Outgoing',
    'direction_class' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
    'other_warehouse' => $m->destinationWarehouse?->name ?? '-',
    'driver' => $m->assignedDriver?->name ?? '-',
    'status' => ucwords(str_replace('_', ' ', (string) $m->status)),
    'status_badge_class' => $statusBadge($m->status),
    'items' => $m->items_count ?? 0,
    'dispatched_at' => $m->dispatched_at?->format('d M Y, h:i A') ?? '-',
    'arrived_at' => $m->arrived_at?->format('d M Y, h:i A') ?? '-',
    'created_at' => $m->created_at?->format('d M Y, h:i A') ?? '-',
    'view_url' => route('admin.transport-manifests.show', $m->id),
])->toBase()->merge($manifestsIncoming->map(fn($m) => [
    'id' => $m->id,
    '_primary' => $m->manifest_number,
    '_secondary' => 'From ' . ($m->originWarehouse?->name ?? 'Unknown warehouse'),
    'manifest_number' => $m->manifest_number,
    'direction' => 'Incoming',
    'direction_class' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
    'other_warehouse' => $m->originWarehouse?->name ?? '-',
    'driver' => $m->assignedDriver?->name ?? '-',
    'status' => ucwords(str_replace('_', ' ', (string) $m->status)),
    'status_badge_class' => $statusBadge($m->status),
    'items' => $m->items_count ?? 0,
    'dispatched_at' => $m->dispatched_at?->format('d M Y, h:i A') ?? '-',
    'arrived_at' => $m->arrived_at?->format('d M Y, h:i A') ?? '-',
    'created_at' => $m->created_at?->format('d M Y, h:i A') ?? '-',
    'view_url' => route('admin.transport-manifests.show', $m->id),
])->toBase())->sortByDesc('created_at')->values()->all();

$deliveryRunsFlat = $deliveryRuns->map(fn($r) => [
    'id' => $r->id,
    '_primary' => $r->run_number,
    '_secondary' => $r->assignedDriver?->name ?? 'No rider assigned',
    'run_number' => $r->run_number,
    'driver' => $r->assignedDriver?->name ?? '-',
    'status' => ucwords(str_replace('_', ' ', (string) $r->status)),
    'status_badge_class' => $statusBadge($r->status),
    'stops' => $r->stops_count ?? 0,
    'dispatched_at' => $r->dispatched_at?->format('d M Y, h:i A') ?? '-',
    'completed_at' => $r->completed_at?->format('d M Y, h:i A') ?? '-',
    'created_at' => $r->created_at?->format('d M Y, h:i A') ?? '-',
    'view_url' => route('admin.delivery-runs.show', $r->id),
])->values()->all();
@endphp

@section('content')
<div x-data="warehouseShow()" data-warehouse-show-config="{{ json_encode($warehouseConfig) }}" data-warehouse-users-config="{{ json_encode($warehouseUsersTableConfig) }}" class="space-y-6">
    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
            <div class="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.25),transparent_58%)]"></div>
            <div class="absolute inset-y-0 left-0 w-1/2 bg-[radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.95),transparent_70%)]"></div>
        </div>
        <div class="relative p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <a href="{{ route('admin.warehouses.index') }}" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 text-sm font-black text-slate-100 transition hover:bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
                <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
                    <span class="inline-flex h-9 items-center rounded-full px-3 text-xs font-black {{ $warehouse->is_active ? 'bg-emerald-500/15 text-emerald-100 ring-1 ring-emerald-400/30' : 'bg-slate-500/15 text-slate-200 ring-1 ring-slate-400/30' }}">
                        <span class="mr-2 h-2 w-2 rounded-full {{ $warehouse->is_active ? 'bg-emerald-300' : 'bg-slate-300' }}"></span>{{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($canManage)
                        <button type="button" @@click="openEditModal()" class="inline-flex h-9 items-center gap-2 rounded-full border border-orange-400/45 bg-orange-500/15 px-3 text-xs font-black text-orange-100 transition hover:bg-orange-500/25">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit
                        </button>
                        <button type="button" @@click="showToggleModal = true" class="inline-flex h-9 items-center rounded-full border px-3 text-xs font-black transition" :class="warehouse.is_active ? 'border-amber-400/45 bg-amber-500/15 text-amber-100 hover:bg-amber-500/25' : 'border-emerald-400/45 bg-emerald-500/15 text-emerald-100 hover:bg-emerald-500/25'">
                            <span x-text="warehouse.is_active ? 'Deactivate' : 'Activate'"></span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="relative mt-6 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 lg:max-w-[680px]">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-950/25">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 21h18M4 21V8l8-5 8 5v13M9 21v-6h6v6M8 10h.01M12 10h.01M16 10h.01"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-200">Warehouse Workspace</p>
                            <h1 class="mt-1 max-w-4xl break-words text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl">{{ $warehouse->name }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-300">
                                @if($warehouse->code)<span class="font-mono">{{ $warehouse->code }}</span><span class="text-slate-600">/</span>@endif
                                <span>{{ $warehouse->district->name ?? 'No district' }}</span>
                                <span class="text-slate-600">/</span>
                                <span>{{ $warehouse->region->name ?? 'No region' }}</span>
                                <span class="text-slate-600">/</span>
                                <span>Created {{ $warehouse->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 lg:ml-auto lg:w-[600px] lg:shrink-0 xl:grid-cols-4">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3"><p class="text-lg font-black text-white">{{ number_format($warehouseStats['users_count']) }}</p><p class="text-xs font-bold text-slate-400">Users</p></div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3"><p class="text-lg font-black text-white">{{ number_format($warehouseStats['pending_receipts']) }}</p><p class="text-xs font-bold text-slate-400">Pending</p></div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3"><p class="text-lg font-black text-white">{{ number_format($warehouseStats['received_pickups']) }}</p><p class="text-xs font-bold text-slate-400">Pickups</p></div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3"><p class="text-lg font-black text-white">{{ number_format($warehouseStats['total_received_items']) }}</p><p class="text-xs font-bold text-slate-400">Items</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            @foreach([
                ['details', 'Details'], ['users', 'Users'], ['received_items', 'Received Items'], ['received_pickups', 'Received Pickups'], ['pending_receipts', 'Pending Receipts'], ['sort_batches', 'Sort Batches'], ['manifests', 'Transport Manifests'], ['delivery_runs', 'Delivery Runs']
            ] as [$tab, $label])
                <button type="button" @@click="activeTab = '{{ $tab }}'" class="inline-flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-black transition" :class="activeTab === '{{ $tab }}' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">{{ $label }}</button>
            @endforeach
            @if($canManageCapabilities)
                <button type="button" @@click="activeTab = 'capabilities'; loadCapabilities()" class="inline-flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-black transition" :class="activeTab === 'capabilities' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">Capabilities</button>
            @endif
        </div>
    </section>

    <div x-show="activeTab === 'details'" x-cloak class="grid gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
            <div class="border-b border-slate-200/60 px-5 py-4"><h2 class="text-lg font-extrabold text-slate-900">Warehouse Details</h2><p class="mt-0.5 text-sm font-medium text-slate-500">Location, contact, and operating details.</p></div>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div><p class="text-xs font-black uppercase tracking-wide text-slate-400">Code</p><p class="mt-1 font-mono text-sm font-black text-slate-900">{{ $warehouse->code ?: '—' }}</p></div>
                <div><p class="text-xs font-black uppercase tracking-wide text-slate-400">Capacity</p><p class="mt-1 text-sm font-black text-slate-900">{{ $warehouse->capacity ? number_format($warehouse->capacity) . ' m³' : '—' }}</p></div>
                <div><p class="text-xs font-black uppercase tracking-wide text-slate-400">Phone</p><p class="mt-1 text-sm font-bold text-slate-800">{{ $warehouse->contact_phone ?: '—' }}</p></div>
                <div><p class="text-xs font-black uppercase tracking-wide text-slate-400">Email</p><p class="mt-1 break-words text-sm font-bold text-slate-800">{{ $warehouse->contact_email ?: '—' }}</p></div>
                <div class="sm:col-span-2"><p class="text-xs font-black uppercase tracking-wide text-slate-400">Address</p><p class="mt-1 text-sm font-bold leading-relaxed text-slate-800">{{ $warehouse->address ?: 'No address provided.' }}</p></div>
            </div>
        </section>
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
            <div class="border-b border-slate-200/60 px-5 py-4"><h2 class="text-lg font-extrabold text-slate-900">Operational Summary</h2><p class="mt-0.5 text-sm font-medium text-slate-500">Quick counts for this warehouse.</p></div>
            <div class="grid grid-cols-2 gap-3 p-5">
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-2xl font-black text-slate-900">{{ $receivedItems->count() }}</p><p class="text-xs font-bold text-slate-500">Received item rows</p></div>
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-2xl font-black text-slate-900">{{ $sortBatchesOrigin->count() + $sortBatchesDest->count() }}</p><p class="text-xs font-bold text-slate-500">Sort batches</p></div>
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-2xl font-black text-slate-900">{{ $manifestsOutgoing->count() + $manifestsIncoming->count() }}</p><p class="text-xs font-bold text-slate-500">Manifests</p></div>
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-2xl font-black text-slate-900">{{ $deliveryRuns->count() }}</p><p class="text-xs font-bold text-slate-500">Delivery runs</p></div>
            </div>
        </section>
    </div>

    <div x-show="activeTab === 'users'" x-cloak x-data="warehouseUsersTable()" x-init="init()">
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
            <div class="border-b border-slate-200/60 px-5 py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div><h2 class="text-lg font-extrabold text-slate-900">Warehouse Users</h2><p class="mt-0.5 text-sm font-medium text-slate-500">Manage staff assigned to this warehouse.</p></div>
                </div>
            </div>
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <div class="w-full xl:max-w-md"><label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg><input type="text" x-model="search" @@input.debounce.500ms="meta.current_page = 1; loadData()" placeholder="Search users, email, phone..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"></div></div>
                    <div class="flex flex-wrap items-center gap-3 xl:justify-end">@include('admin.warehouses._inventory_view_btn') @include('admin.warehouses._inventory_export_btn')</div>
                </div>
            </div>
            <div class="relative overflow-hidden bg-white">
                <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/70 backdrop-blur-[1px]" style="display:none"></div>
                <div class="hidden overflow-x-auto lg:block"><table class="w-full min-w-[920px] divide-y divide-slate-200/60 text-xs"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">User</th><th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Role</th><th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Contact</th><th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Status</th><th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Last Login</th><th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Action</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white"><template x-if="users.length === 0 && !loading"><tr><td colspan="6" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No warehouse users found.</td></tr></template><template x-for="user in users" :key="user.id"><tr class="hover:bg-slate-50/70"><td class="px-4 py-3"><p class="text-sm font-black text-slate-900" x-text="user.name"></p><p class="mt-1 font-mono text-[11px] font-semibold text-slate-500" x-text="'ID ' + user.id"></p></td><td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-700" x-text="user.roles && user.roles.length ? user.roles[0].name : 'No role'"></span></td><td class="px-4 py-3"><p class="font-bold text-slate-800" x-text="user.email || '-'"></p><p class="mt-1 text-[11px] font-semibold text-slate-500" x-text="user.phone || '-'"></p></td><td class="px-4 py-3 text-center"><span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black" :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="user.is_active ? 'Active' : 'Inactive'"></span></td><td class="px-4 py-3 text-xs font-semibold text-slate-600" x-text="user.last_login_at || '-'"></td><td class="px-4 py-3 text-right"><a :href="user.view_url" class="inline-flex rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 hover:bg-orange-100">Open</a></td></tr></template></tbody></table></div>
                <div class="divide-y divide-slate-100 lg:hidden"><template x-for="user in users" :key="user.id"><article class="p-4"><div class="flex items-start justify-between gap-3"><div><p class="text-sm font-black text-slate-900" x-text="user.name"></p><p class="mt-1 text-xs font-semibold text-slate-500" x-text="user.email || '-' "></p></div><span class="rounded-full px-2.5 py-1 text-[10px] font-black" :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="user.is_active ? 'Active' : 'Inactive'"></span></div><div class="mt-3 grid grid-cols-2 gap-3 text-xs"><div><p class="font-black uppercase tracking-wide text-slate-400">Role</p><p class="font-bold text-slate-800" x-text="user.roles && user.roles.length ? user.roles[0].name : 'No role'"></p></div><div><p class="font-black uppercase tracking-wide text-slate-400">Phone</p><p class="font-bold text-slate-800" x-text="user.phone || '-'"></p></div></div><a :href="user.view_url" class="mt-4 inline-flex rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">Open</a></article></template></div>
                <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div class="text-xs font-semibold text-slate-600">Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span></div><div class="flex items-center gap-1"><button @@click="previousPage()" :disabled="meta.current_page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button><div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span></div><button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button></div></div></div>
            </div>
        </section>
    </div>

    <div x-show="activeTab === 'received_items'" x-cloak x-data="warehouseReceivedItemsTable({{ Js::from($receivedItemsFlat) }})" x-init="init()">@include('admin.warehouses._detail_client_table', ['title' => 'Received Items', 'subtitle' => 'Packages confirmed into this warehouse.', 'noun' => 'items', 'emptyTitle' => 'No received items found.'])</div>
    <div x-show="activeTab === 'received_pickups'" x-cloak x-data="warehouseReceivedPickupsTable({{ Js::from($receivedPickupsFlat) }})" x-init="init()">@include('admin.warehouses._detail_client_table', ['title' => 'Received Pickups', 'subtitle' => 'Pickup assignments received at this warehouse.', 'noun' => 'pickups', 'emptyTitle' => 'No received pickups found.'])</div>
    <div x-show="activeTab === 'pending_receipts'" x-cloak x-data="warehousePendingReceiptsTable({{ Js::from($pendingReceiptsFlat) }})" x-init="init()">@include('admin.warehouses._detail_client_table', ['title' => 'Pending Receipts', 'subtitle' => 'Inbound pickups still waiting to be received.', 'noun' => 'receipts', 'emptyTitle' => 'No pending receipts found.'])</div>
    <div x-show="activeTab === 'sort_batches'" x-cloak x-data="warehouseSortBatchesTable({{ Js::from($sortBatchesFlat) }})" x-init="init()">@include('admin.warehouses._detail_client_table', ['title' => 'Sort Batches', 'subtitle' => 'Incoming and outgoing sort batches linked to this warehouse.', 'noun' => 'batches', 'emptyTitle' => 'No sort batches found.'])</div>
    <div x-show="activeTab === 'manifests'" x-cloak x-data="warehouseManifestsTable({{ Js::from($manifestsFlat) }})" x-init="init()">@include('admin.warehouses._detail_client_table', ['title' => 'Transport Manifests', 'subtitle' => 'Inter-warehouse transfers involving this warehouse.', 'noun' => 'manifests', 'emptyTitle' => 'No transport manifests found.'])</div>
    <div x-show="activeTab === 'delivery_runs'" x-cloak x-data="warehouseDeliveryRunsTable({{ Js::from($deliveryRunsFlat) }})" x-init="init()">@include('admin.warehouses._detail_client_table', ['title' => 'Delivery Runs', 'subtitle' => 'Local delivery activity dispatched from this warehouse.', 'noun' => 'runs', 'emptyTitle' => 'No delivery runs found.'])</div>

    @if($canManageCapabilities)
    <div x-show="activeTab === 'capabilities'" x-cloak>
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
            <div class="border-b border-slate-200/60 px-5 py-4"><div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"><div><h2 class="text-lg font-extrabold text-slate-900">Warehouse Capabilities</h2><p class="mt-0.5 text-sm font-medium text-slate-500">Control which modules this warehouse can use and the scope it can operate in.</p></div><button type="button" @@click="saveCapabilities()" x-show="!config.isHqWarehouse" :disabled="savingCapabilities || loadingCapabilities" class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:opacity-60">Save Capabilities</button></div></div>
            <template x-if="config.isHqWarehouse"><div class="m-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4"><p class="text-sm font-extrabold text-emerald-800">HQ warehouse has full access.</p><p class="mt-1 text-sm font-medium text-emerald-700">Capabilities are not editable here.</p></div></template>
            <template x-if="!config.isHqWarehouse"><div class="divide-y divide-slate-100"><div x-show="loadingCapabilities" class="px-5 py-8 text-center text-sm font-bold text-slate-500">Loading capabilities...</div><template x-for="module in capabilityModules" :key="module.key"><div class="px-5 py-4"><div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_320px] lg:items-center"><label class="flex min-w-0 cursor-pointer items-start gap-3"><input type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500" :checked="isCapabilityEnabled(module.key)" @@change="toggleCapability(module.key, $event.target.checked)"><span><span class="block text-sm font-extrabold text-slate-900" x-text="module.label"></span><span class="mt-1 block text-xs font-semibold text-slate-500" x-text="capabilityDescription(module.key)"></span></span></label><div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wide text-slate-500">Scope</label><select class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-bold text-slate-800 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:bg-slate-50 disabled:text-slate-400" :disabled="!isCapabilityEnabled(module.key)" x-model="capabilityForm[module.key].scope"><option value="own">Own warehouse</option><option value="selected">Selected warehouses</option><option value="global">All warehouses</option></select></div><div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wide text-slate-500">Allowed Warehouses</label><select multiple class="min-h-[48px] w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-800 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:bg-slate-50 disabled:text-slate-400" :disabled="!isCapabilityEnabled(module.key) || capabilityForm[module.key].scope !== 'selected'" x-model="capabilityForm[module.key].allowed_warehouse_ids"><template x-for="option in config.capabilityWarehouses" :key="option.id"><option :value="String(option.id)" x-text="option.name"></option></template></select></div></div></div></template></div></template>
        </section>
    </div>
    @endif

    <!-- Edit Modal -->
    <div
        x-show="showEditModal"
        x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto"
        @@keydown.escape.window="showEditModal = false"
    >
        <!-- Backdrop -->
        <div x-show="showEditModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="showEditModal = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showEditModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @@click.stop
                class="relative w-full max-w-lg bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50"
            >
                <!-- Header with Gradient -->
                <div class="relative bg-gradient-to-r from-slate-50 to-slate-100/50 px-6 py-5 border-b border-slate-200/50 rounded-t-2xl">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center shadow-lg shadow-teal-900/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Edit Warehouse</h3>
                                <p class="text-sm text-slate-500 mt-1">Update warehouse information and settings</p>
                            </div>
                        </div>
                        <button @@click="showEditModal = false" class="flex-shrink-0 rounded-xl p-2 text-slate-400 hover:bg-white hover:text-slate-700 transition-all shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <form @@submit.prevent="saveWarehouse()">
                    <div class="space-y-5 px-6 py-6 max-h-[calc(100vh-240px)] overflow-y-auto">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Warehouse Name <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    x-model="form.name"
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                    placeholder="Main Warehouse"
                                    required
                                >
                            </div>
                            <template x-if="errors.name">
                                <p class="mt-1.5 text-xs text-rose-600" x-text="errors.name[0]"></p>
                            </template>
                        </div>

                        <!-- Code -->
                        <div class="grid grid-cols-1 gap-5">
                            <!-- Code -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Code <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="form.code"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                        placeholder="WH-001"
                                    >
                                </div>
                                <template x-if="errors.code">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.code[0]"></p>
                                </template>
                            </div>
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Address <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute top-2.5 left-0 pl-3 flex items-start pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <textarea
                                    x-model="form.address"
                                    rows="2"
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all resize-none"
                                    placeholder="Full address of warehouse location"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Contact Phone & Email Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Contact Phone -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Contact Phone <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        x-model="form.contact_phone"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                        placeholder="+233 24 123 4567"
                                    >
                                </div>
                                <template x-if="errors.contact_phone">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.contact_phone[0]"></p>
                                </template>
                            </div>

                            <!-- Contact Email -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Contact Email <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="email"
                                        x-model="form.contact_email"
                                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                        placeholder="warehouse@example.com"
                                    >
                                </div>
                                <template x-if="errors.contact_email">
                                    <p class="mt-1.5 text-xs text-rose-600" x-text="errors.contact_email[0]"></p>
                                </template>
                            </div>
                        </div>

                        <!-- Capacity -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Capacity (m&sup3;) <span class="text-slate-400 text-xs font-normal">(Optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <input
                                    type="number"
                                    x-model="form.capacity"
                                    min="0"
                                    class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-slate-500/20 focus:border-slate-500 text-sm text-slate-900 placeholder-slate-400 transition-all"
                                    placeholder="500"
                                >
                            </div>
                        </div>

                        <!-- Status Toggle -->
                        <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800">Warehouse Status</h4>
                                        <p class="text-xs text-slate-500" x-text="form.is_active ? 'Warehouse is operational' : 'Warehouse is inactive'"></p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @@click="form.is_active = !form.is_active"
                                    :class="form.is_active ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : 'bg-slate-300'"
                                    class="relative inline-flex h-7 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 shadow-sm"
                                >
                                    <span
                                        :class="form.is_active ? 'translate-x-7' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-300 ease-in-out"
                                    ></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 border-t border-slate-200/50 bg-gradient-to-r from-slate-50/50 to-slate-100/30 px-6 py-5 rounded-b-2xl">
                        <button
                            type="button"
                            @@click="showEditModal = false"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all shadow-sm"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-slate-700 to-slate-900 hover:from-slate-800 hover:to-slate-950 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-slate-900/25 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
                        >
                            <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toggle Status Confirmation Modal -->
    <div
        x-show="showToggleModal"
        x-cloak
        class="fixed inset-0 z-[100] overflow-y-auto"
        @@keydown.escape.window="showToggleModal = false"
    >
        <!-- Backdrop -->
        <div x-show="showToggleModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
             @@click="showToggleModal = false"></div>

        <!-- Modal Content -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showToggleModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @@click.stop
                class="relative w-full max-w-md bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50 overflow-hidden"
            >
                <!-- Header -->
                <div class="p-6 text-center">
                    <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-4"
                         :class="warehouse.is_active ? 'bg-amber-100' : 'bg-emerald-100'">
                        <svg x-show="warehouse.is_active" class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <svg x-show="!warehouse.is_active" x-cloak class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900" x-text="warehouse.is_active ? 'Deactivate Warehouse?' : 'Activate Warehouse?'"></h3>
                    <p class="mt-2 text-sm text-slate-600">
                        <span x-show="warehouse.is_active">
                            Are you sure you want to deactivate <strong x-text="warehouse.name"></strong>? The warehouse will be marked as non-operational.
                        </span>
                        <span x-show="!warehouse.is_active" x-cloak>
                            Are you sure you want to activate <strong x-text="warehouse.name"></strong>? The warehouse will be marked as operational again.
                        </span>
                    </p>
                </div>

                <!-- Footer -->
                <div class="flex items-center gap-3 border-t border-slate-200/50 bg-slate-50/50 px-6 py-4">
                    <button
                        type="button"
                        @@click="showToggleModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @@click="toggleActive()"
                        :disabled="toggling"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl transition-all disabled:opacity-50"
                        :class="warehouse.is_active
                            ? 'bg-amber-500 hover:bg-amber-600 text-white'
                            : 'bg-emerald-500 hover:bg-emerald-600 text-white'"
                    >
                        <svg x-show="toggling" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="toggling ? 'Processing...' : (warehouse.is_active ? 'Yes, Deactivate' : 'Yes, Activate')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
