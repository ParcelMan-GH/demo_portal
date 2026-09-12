@extends('warehouse.layouts.app')

@php
    $currentAdmin = Auth::guard('admin')->user();
    $isSelf = $currentAdmin?->id === $admin->id;
    $roleNames = $admin->roles->pluck('name')->values();
    $primaryRole = $roleNames->first() ?: 'No role assigned';
    $photoUrl = $admin->photo_path ? app(\App\Services\StorageService::class)->getUrl($admin->photo_path) : null;
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

    $config = [
        'user' => $userPayload,
        'updateEndpoint' => route('warehouse.users.update', $admin),
        'toggleActiveEndpoint' => route('warehouse.users.toggle-active', $admin),
        'impersonateEndpoint' => route('warehouse.users.impersonate', $admin),
        'indexUrl' => route('warehouse.users.index'),
        'csrfToken' => csrf_token(),
        'canManage' => (bool) $canManage,
        'canImpersonate' => (bool) $canImpersonate,
    ];
@endphp

@section('title', $admin->name . ' - Worker Profile')

@section('content')
<div x-data="userShow()" x-init="init()" data-user-show-config='@json($config)' class="space-y-6">
    
    <!-- Clean Light Hero Profile Card -->
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-orange-50/60 via-white to-slate-50/50 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between mb-6">
            <a href="{{ route('warehouse.users.index') }}" class="inline-flex w-fit items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to All Workers
            </a>

            <!-- Action Controls -->
            <div class="flex flex-wrap items-center gap-2.5 sm:justify-end">
                <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-black {{ $admin->is_active ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' : 'bg-rose-100 text-rose-800 ring-1 ring-rose-200' }}">
                    <span class="h-2.5 w-2.5 rounded-full {{ $admin->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    {{ $admin->is_active ? 'Active Account' : 'Inactive Account' }}
                </span>

                @if($canManage)
                    <button type="button" @click="openEditModal()" class="inline-flex items-center gap-2 rounded-2xl border border-orange-200 bg-orange-50 px-4 py-2 text-xs font-black text-orange-800 transition hover:bg-orange-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                        </svg>
                        Edit Profile
                    </button>
                    @unless($isSelf)
                        <button type="button" @click="openStatusModal()" class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-black text-amber-800 transition hover:bg-amber-100">
                            {{ $admin->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    @endunless
                @endif

                @if($canImpersonate)
                    <button type="button" @click="openImpersonationModal()" class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2 text-xs font-black text-sky-800 transition hover:bg-sky-100">
                        Login as User
                    </button>
                @endif
            </div>
        </div>

        <!-- Worker Information Overview -->
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-3xl bg-orange-600 text-4xl font-black text-white shadow-xl shadow-orange-600/20 ring-4 ring-white">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="" class="h-full w-full object-cover">
                @else
                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <h1 class="break-words text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ $admin->name }}</h1>
                
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm font-bold text-slate-700">
                    <span>{{ $phone }}</span>
                    <span class="text-slate-300">•</span>
                    <span>{{ $email }}</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-slate-950 font-black">{{ $admin->warehouse?->name ?? 'No warehouse assigned' }}</span>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-extrabold text-slate-500">
                    <span class="text-orange-700 font-black">{{ $primaryRole }}</span>
                    <span>•</span>
                    <span>Last login: {{ $admin->last_login_at?->format('M j, Y, h:i A') ?? 'Never' }}</span>
                    <span>•</span>
                    <span>Account created: {{ $admin->created_at?->format('M j, Y') }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Summary Metrics Grid -->
    <section class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h13.5A2.25 2.25 0 0121 5.25Z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-950">{{ number_format($activityTotal) }}</p>
                    <p class="text-xs font-extrabold text-slate-500">Total Activity Records</p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25m0-9L3 7.5m9 5.25v9M3 7.5v9l9 5.25"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-950">{{ number_format($tabCounts['orders'] ?? 0) }}</p>
                    <p class="text-xs font-extrabold text-slate-500">{{ number_format($packageTotal) }} Packages Handled</p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5A2.25 2.25 0 0019.5 19.5v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25Z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-950">{{ number_format($tabCounts['security-log'] ?? 0) }}</p>
                    <p class="text-xs font-extrabold text-slate-500">Security Events Logged</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Activity Overview Table -->
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-6">
            <h2 class="text-lg font-black text-slate-950">Recent Worker Activity</h2>
            <p class="text-xs font-semibold text-slate-500 mt-0.5">Most recent actions performed by {{ $admin->name }} across all system modules.</p>
        </div>

        <div class="divide-y divide-slate-100">
            <template x-if="overviewLoading">
                <div class="p-8 text-center text-xs font-bold text-slate-500">Loading activity logs...</div>
            </template>
            <template x-if="!overviewLoading && (!overview || !overview.recent || overview.recent.length === 0)">
                <div class="p-8 text-center text-xs font-bold text-slate-500">No recent activity recorded for this user.</div>
            </template>
            <template x-for="row in (overview?.recent || [])" :key="row.id">
                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between transition hover:bg-slate-50/60">
                    <div class="min-w-0">
                        <p class="text-sm font-black text-slate-950" x-text="row.action"></p>
                        <p class="mt-1 text-xs font-semibold text-slate-500" x-text="`${row.module} · Ref: ${row.reference} · ${formatDate(row.date)}`"></p>
                    </div>
                    <span class="w-fit rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-slate-600" x-text="row.status"></span>
                </div>
            </template>
        </div>
    </section>

    @include('warehouse.users.partials.user-modal')

    <!-- Status Change Modal -->
    <template x-teleport="body">
        <div x-show="showStatusModal" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm" @click.self="closeStatusModal()">
            <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                <h3 class="text-xl font-black text-slate-950" x-text="user.is_active ? 'Deactivate user?' : 'Activate user?'"></h3>
                <p class="mt-2 text-xs font-semibold text-slate-500">This changes whether the account can access the back office portal.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="closeStatusModal()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" @click="submitStatusToggle()" :disabled="statusSubmitting" class="rounded-xl border border-orange-600 bg-orange-600 px-4 py-2.5 text-xs font-black text-white disabled:opacity-50" x-text="user.is_active ? 'Deactivate' : 'Activate'"></button>
                </div>
            </div>
        </div>
    </template>

    <!-- Impersonation Modal -->
    <template x-teleport="body">
        <div x-show="showImpersonationModal" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm" @click.self="closeImpersonationModal()">
            <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
                <h3 class="text-xl font-black text-slate-950">Login as this user?</h3>
                <p class="mt-2 text-xs font-semibold text-slate-500">You will switch into this account in the same browser session. Actions are audited.</p>
                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="font-black text-slate-950" x-text="user.name"></p>
                    <p class="mt-1 text-xs font-semibold text-slate-500" x-text="`${user.phone || '-'} · ${user.warehouse?.name || 'No warehouse'}`"></p>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="closeImpersonationModal()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" @click="startImpersonation()" :disabled="impersonationSubmitting" class="rounded-xl border border-orange-600 bg-orange-600 px-4 py-2.5 text-xs font-black text-white disabled:opacity-50">Login as user</button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection