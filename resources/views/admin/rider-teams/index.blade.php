@extends('admin.layouts.app')

@section('title', 'Rider Teams')
@section('breadcrumb-parent', 'Delivery')
@section('breadcrumb-current', 'Rider Teams')

@section('content')
@php
    $config = [
        'teamsUrl' => route('admin.rider-teams.data'),
        'storeTeamUrl' => route('admin.rider-teams.store'),
        'updateTeamUrl' => route('admin.rider-teams.update', ['team' => '__TEAM__']),
        'membersUrl' => route('admin.rider-teams.members', ['team' => '__TEAM__']),
        'lookupMemberUrl' => route('admin.rider-teams.members.lookup', ['team' => '__TEAM__']),
        'addMemberUrl' => route('admin.rider-teams.members.store', ['team' => '__TEAM__']),
        'makeLeaderUrl' => route('admin.rider-teams.members.leader.store', ['team' => '__TEAM__', 'driver' => '__DRIVER__']),
        'removeLeaderUrl' => route('admin.rider-teams.members.leader.destroy', ['team' => '__TEAM__', 'driver' => '__DRIVER__']),
        'removeMemberUrl' => route('admin.rider-teams.members.destroy', ['team' => '__TEAM__', 'driver' => '__DRIVER__']),
        'handoversUrl' => route('admin.rider-teams.handovers.data'),
        'storeHandoverUrl' => route('admin.rider-teams.handovers.store'),
        'showHandoverUrl' => route('admin.rider-teams.handovers.show', ['handover' => '__HANDOVER__']),
        'assignLabelsUrl' => route('admin.rider-teams.handovers.labels.store', ['handover' => '__HANDOVER__']),
        'recallUrl' => route('admin.rider-teams.handovers.recall', ['handover' => '__HANDOVER__']),
        'csrfToken' => csrf_token(),
    ];
@endphp

<div
    class="space-y-5"
    x-data="riderTeamsPage(@js($config), @js($warehouses), @js($drivers))"
    x-init="init()"
>
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <button type="button" @@click="activeTab = 'teams'; teamStatusFilter = ''; teamSearch = ''" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m6-6a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm6 2a3 3 0 1 1-5.196-2.052"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Teams</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="teams.length"></p>
            </div>
        </button>
        <button type="button" @@click="activeTab = 'teams'; teamStatusFilter = 'active'" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Active Teams</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="teams.filter(t => t.is_active).length"></p>
            </div>
        </button>
        <button type="button" @@click="activeTab = 'handovers'; handoverStatusFilter = ''" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Handovers</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="handovers.length"></p>
            </div>
        </button>
        <button type="button" @@click="activeTab = 'handovers'; handoverStatusFilter = 'received'" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">With Receiver</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="handovers.reduce((sum, h) => sum + (h.counts?.with_receiver || 0), 0)"></p>
            </div>
        </button>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m6-6a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm6 2a3 3 0 1 1-5.196-2.052"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900">Rider Teams</h2>
                            <p class="truncate text-sm text-slate-500">Manage team custody before riders claim packages for delivery.</p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    <button type="button" @@click="activeTab = 'teams'" class="inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-bold transition" :class="activeTab === 'teams' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'border border-slate-200/70 bg-white text-slate-700 shadow-sm hover:bg-slate-50'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m6-6a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
                        Teams
                    </button>
                    <button type="button" @@click="activeTab = 'handovers'" class="inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-bold transition" :class="activeTab === 'handovers' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'border border-slate-200/70 bg-white text-slate-700 shadow-sm hover:bg-slate-50'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                        Handovers
                    </button>
                    <button type="button" x-show="activeTab === 'teams'" @@click="openTeamModal()" class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Team
                    </button>
                    <button type="button" x-show="activeTab === 'handovers'" @@click="openHandoverModal()" class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Handover
                    </button>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'teams'" x-cloak>
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <div class="w-full xl:max-w-md">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/></svg>
                            <input type="text" x-model.debounce.300ms="teamSearch" @@input="teamPage = 1" placeholder="Search team or warehouse..."
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                        <button type="button" @@click="showTeamFilters = !showTeamFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="showTeamFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="showTeamFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                        <button type="button" @@click="loadTeams()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M20 9A8 8 0 0 0 6.67 4M4 15a8 8 0 0 0 13.33 5"/></svg>
                            Refresh
                        </button>
                    </div>
                </div>

                <div x-show="showTeamFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                            <select x-model="teamStatusFilter" @@change="teamPage = 1" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                <option value="">All teams</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Warehouse</label>
                            <select x-model="teamWarehouseFilter" @@change="teamPage = 1" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                <option value="">All accessible warehouses</option>
                                <template x-for="warehouse in warehouses" :key="warehouse.id">
                                    <option :value="warehouse.id" x-text="`${warehouse.name} (${warehouse.code})`"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                        <button type="button" @@click="showTeamFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                        <button type="button" @@click="clearTeamFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden">
                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-[980px] w-full table-fixed divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="w-[26%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Team</th>
                                <th class="w-[22%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Warehouse</th>
                                <th class="w-[12%] px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Members</th>
                                <th class="w-[12%] px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Leaders</th>
                                <th class="w-[12%] px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/70">
                            <template x-if="paginatedTeams().length === 0">
                                <tr><td colspan="6" class="px-4 py-12 text-center text-sm font-semibold text-slate-400">No rider teams match the current filters.</td></tr>
                            </template>
                            <template x-for="team in paginatedTeams()" :key="team.id">
                                <tr class="transition hover:bg-orange-50/20">
                                    <td class="px-4 py-4">
                                        <p class="truncate text-sm font-extrabold text-slate-950" x-text="team.name"></p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500" x-text="`${team.handovers_count || 0} handover records`"></p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="truncate text-sm font-bold text-slate-800" x-text="team.warehouse?.name || 'No warehouse selected'"></p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500" x-text="team.warehouse?.code || '-'"></p>
                                    </td>
                                    <td class="px-4 py-4 text-center"><span class="text-sm font-black text-slate-900" x-text="team.members_count || 0"></span></td>
                                    <td class="px-4 py-4 text-center"><span class="text-sm font-black text-slate-900" x-text="team.leaders_count || 0"></span></td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black" :class="team.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'" x-text="team.is_active ? 'Active' : 'Inactive'"></span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-nowrap items-center justify-end gap-2">
                                            <button type="button" @@click="openMembersModal(team)" class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1"/></svg>
                                                Members
                                            </button>
                                            <button type="button" @@click="openTeamModal(team)" class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl bg-orange-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-orange-700">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 lg:hidden">
                    <template x-if="paginatedTeams().length === 0">
                        <div class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No rider teams match the current filters.</div>
                    </template>
                    <template x-for="team in paginatedTeams()" :key="team.id">
                        <div class="px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-base font-extrabold text-slate-950" x-text="team.name"></p>
                                    <p class="mt-1 text-sm font-semibold text-slate-500" x-text="team.warehouse?.name || 'No warehouse selected'"></p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black" :class="team.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'" x-text="team.is_active ? 'Active' : 'Inactive'"></span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2">
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Members</p><p class="text-lg font-black" x-text="team.members_count || 0"></p></div>
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Leaders</p><p class="text-lg font-black" x-text="team.leaders_count || 0"></p></div>
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase text-slate-400">Handovers</p><p class="text-lg font-black" x-text="team.handovers_count || 0"></p></div>
                            </div>
                            <div class="mt-3 flex flex-nowrap justify-end gap-2">
                                <button type="button" @@click="openMembersModal(team)" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700">Members</button>
                                <button type="button" @@click="openTeamModal(team)" class="rounded-xl bg-orange-600 px-3 py-2 text-xs font-bold text-white">Edit</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-semibold text-slate-600" x-text="teamFooterText()"></p>
                    <div class="flex items-center gap-3">
                        <button type="button" @@click="teamPage = Math.max(1, teamPage - 1)" :disabled="teamPage <= 1" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Prev</button>
                        <p class="text-sm font-bold text-slate-600" x-text="`Page ${teamPage} of ${teamTotalPages()}`"></p>
                        <button type="button" @@click="teamPage = Math.min(teamTotalPages(), teamPage + 1)" :disabled="teamPage >= teamTotalPages()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'handovers'" x-cloak>
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <div class="w-full xl:max-w-md">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/></svg>
                            <input type="text" x-model.debounce.300ms="handoverSearch" @@input="handoverPage = 1" placeholder="Search handover, team, receiver..."
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                        <button type="button" @@click="showHandoverFilters = !showHandoverFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="showHandoverFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                            <span x-text="showHandoverFilters ? 'Hide Filters' : 'Filters'"></span>
                        </button>
                        <button type="button" @@click="loadHandovers()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M20 9A8 8 0 0 0 6.67 4M4 15a8 8 0 0 0 13.33 5"/></svg>
                            Refresh
                        </button>
                    </div>
                </div>

                <div x-show="showHandoverFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                            <select x-model="handoverStatusFilter" @@change="handoverPage = 1" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                <option value="">All statuses</option>
                                <option value="assigned">Assigned</option>
                                <option value="partially_received">Partially received</option>
                                <option value="received">Received</option>
                                <option value="partially_distributed">Partially distributed</option>
                                <option value="distributed">Distributed</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Team</label>
                            <select x-model="handoverTeamFilter" @@change="handoverPage = 1" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                <option value="">All teams</option>
                                <template x-for="team in teams" :key="team.id">
                                    <option :value="team.id" x-text="team.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Custody</label>
                            <select x-model="handoverCustodyFilter" @@change="handoverPage = 1" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                <option value="">Any custody state</option>
                                <option value="with_receiver">Still with receiver</option>
                                <option value="distributed">Distributed to members</option>
                                <option value="delivered">Has delivered packages</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                        <button type="button" @@click="showHandoverFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                        <button type="button" @@click="clearHandoverFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden">
                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-[1120px] w-full table-fixed divide-y divide-slate-200/50 text-xs">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="w-[20%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Handover</th>
                                <th class="w-[18%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Team</th>
                                <th class="w-[18%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Receiver</th>
                                <th class="w-[22%] px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Movement</th>
                                <th class="w-[12%] px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                <th class="px-4 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/70">
                            <template x-if="paginatedHandovers().length === 0">
                                <tr><td colspan="6" class="px-4 py-12 text-center text-sm font-semibold text-slate-400">No handovers match the current filters.</td></tr>
                            </template>
                            <template x-for="handover in paginatedHandovers()" :key="handover.id">
                                <tr class="transition hover:bg-orange-50/20">
                                    <td class="px-4 py-4">
                                        <p class="truncate font-mono text-sm font-black text-slate-950" x-text="handover.handover_number"></p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500" x-text="handover.created_at"></p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="truncate text-sm font-extrabold text-slate-900" x-text="handover.team?.name || '-'"></p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500" x-text="handover.warehouse?.name || '-'"></p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="truncate text-sm font-bold text-slate-800" x-text="handover.receiver?.name || '-'"></p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500" x-text="handover.receiver?.phone || '-'"></p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="grid grid-cols-4 gap-1">
                                            <div><p class="text-[9px] font-black uppercase text-slate-400">Assigned</p><p class="font-black text-slate-900" x-text="handover.counts.assigned"></p></div>
                                            <div><p class="text-[9px] font-black uppercase text-slate-400">Received</p><p class="font-black text-slate-900" x-text="handover.counts.received"></p></div>
                                            <div><p class="text-[9px] font-black uppercase text-slate-400">With Receiver</p><p class="font-black text-slate-900" x-text="handover.counts.with_receiver || 0"></p></div>
                                            <div><p class="text-[9px] font-black uppercase text-slate-400">Delivered</p><p class="font-black text-slate-900" x-text="handover.counts.delivered"></p></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase text-slate-700" x-text="formatStatus(handover.status)"></span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-nowrap items-center justify-end gap-2">
                                            <button type="button" @@click="openHandoverDetails(handover)" class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl bg-orange-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-orange-700">
                                                Open
                                            </button>
                                            <a :href="handover.print_url" target="_blank" class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">Print</a>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 lg:hidden">
                    <template x-if="paginatedHandovers().length === 0">
                        <div class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No handovers match the current filters.</div>
                    </template>
                    <template x-for="handover in paginatedHandovers()" :key="handover.id">
                        <div class="px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-base font-black text-slate-950" x-text="handover.handover_number"></p>
                                    <p class="mt-1 text-sm font-semibold text-slate-500" x-text="handover.team?.name || '-'"></p>
                                </div>
                                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase text-slate-700" x-text="formatStatus(handover.status)"></span>
                            </div>
                            <div class="mt-3 grid grid-cols-4 gap-2">
                                <div class="rounded-xl bg-slate-50 p-2"><p class="text-[9px] font-bold uppercase text-slate-400">Assigned</p><p class="text-base font-black" x-text="handover.counts.assigned"></p></div>
                                <div class="rounded-xl bg-slate-50 p-2"><p class="text-[9px] font-bold uppercase text-slate-400">Received</p><p class="text-base font-black" x-text="handover.counts.received"></p></div>
                                <div class="rounded-xl bg-slate-50 p-2"><p class="text-[9px] font-bold uppercase text-slate-400">Receiver</p><p class="text-base font-black" x-text="handover.counts.with_receiver || 0"></p></div>
                                <div class="rounded-xl bg-slate-50 p-2"><p class="text-[9px] font-bold uppercase text-slate-400">Delivered</p><p class="text-base font-black" x-text="handover.counts.delivered"></p></div>
                            </div>
                            <div class="mt-3 flex flex-nowrap justify-end gap-2">
                                <button type="button" @@click="openHandoverDetails(handover)" class="rounded-xl bg-orange-600 px-3 py-2 text-xs font-bold text-white">Open</button>
                                <a :href="handover.print_url" target="_blank" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700">Print</a>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-semibold text-slate-600" x-text="handoverFooterText()"></p>
                    <div class="flex items-center gap-3">
                        <button type="button" @@click="handoverPage = Math.max(1, handoverPage - 1)" :disabled="handoverPage <= 1" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Prev</button>
                        <p class="text-sm font-bold text-slate-600" x-text="`Page ${handoverPage} of ${handoverTotalPages()}`"></p>
                        <button type="button" @@click="handoverPage = Math.min(handoverTotalPages(), handoverPage + 1)" :disabled="handoverPage >= handoverTotalPages()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="teamModal.open"
             x-cloak
             class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
             style="display:none"
             @@click.self="teamModal.open=false"
             @@keydown.escape.window="teamModal.open=false">
            <div x-show="teamModal.open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @@click.stop
                 class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start gap-4 border-b border-slate-200 px-6 py-5">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m6-6a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-2xl font-black text-slate-950" x-text="teamModal.id ? 'Edit Rider Team' : 'New Rider Team'"></h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Set up a rider team.</p>
                    </div>
                    <button type="button" @@click="teamModal.open=false" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-6">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Team Name</label>
                        <input x-model="teamModal.name" type="text" placeholder="Accra Team A" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Warehouse</label>
                        <select x-model="teamModal.warehouse_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Select warehouse</option>
                            <template x-for="warehouse in warehouses" :key="warehouse.id">
                                <option :value="warehouse.id" x-text="`${warehouse.name} (${warehouse.code})`"></option>
                            </template>
                        </select>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 shadow-sm ring-1 ring-orange-100">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-black text-slate-800">Active team</h4>
                                    <p class="mt-0.5 text-xs font-semibold text-slate-500" x-text="teamModal.is_active ? 'Available for new handovers' : 'Hidden from new handovers'"></p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @@click="teamModal.is_active = !teamModal.is_active"
                                :class="teamModal.is_active ? 'bg-orange-600' : 'bg-slate-300'"
                                class="relative inline-flex h-7 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent shadow-sm transition-all duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-orange-100 focus:ring-offset-2"
                                role="switch"
                                :aria-checked="teamModal.is_active ? 'true' : 'false'"
                            >
                                <span
                                    :class="teamModal.is_active ? 'translate-x-7' : 'translate-x-0'"
                                    class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-300 ease-in-out"
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50/60 px-6 py-4">
                    <button type="button" @@click="teamModal.open=false" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700">Cancel</button>
                    <button type="button" @@click="saveTeam()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20">Save Team</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="membersModal.open"
             x-cloak
             class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
             style="display:none"
             @@click.self="membersModal.open=false"
             @@keydown.escape.window="membersModal.open=false">
            <div x-show="membersModal.open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @@click.stop="if (!$event.target.closest('[data-member-rider-picker]')) memberDropdownOpen = false"
                 class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start gap-4 border-b border-slate-200 px-6 py-5">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-2xl font-black text-slate-950" x-text="membersModal.team?.name || 'Team Members'"></h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Manage riders and team leaders.</p>
                    </div>
                    <button type="button" @@click="membersModal.open=false" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-6">
                    <div class="rounded-2xl border border-orange-100 bg-orange-50/40 p-4">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Add Rider</label>
                        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                            <div class="relative" data-member-rider-picker @@click.stop @@click.outside="memberDropdownOpen = false">
                                <div class="relative">
                                    <input
                                        x-ref="memberSearchInput"
                                        type="search"
                                        x-model="memberSearch"
                                        @@focus="memberDropdownOpen = true"
                                        @@input="memberDropdownOpen = true; selectedMemberDriverId = ''"
                                        placeholder="Search rider name or phone..."
                                        class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-3 pr-10 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                        :class="memberDropdownOpen ? 'rounded-b-none border-orange-400 ring-4 ring-orange-100' : ''"
                                    >
                                    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 transition-transform" :class="memberDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                                    </svg>
                                </div>
                                <div
                                    x-show="memberDropdownOpen"
                                    x-cloak
                                    x-transition.opacity.duration.100ms
                                    class="absolute left-0 right-0 z-40 -mt-0.5 overflow-hidden rounded-b-xl border-2 border-t-0 border-orange-400 bg-white shadow-xl shadow-orange-900/10"
                                    style="display:none"
                                >
                                    <div class="max-h-72 overflow-y-auto border-t border-orange-100">
                                        <template x-for="rider in filteredRiders()" :key="rider.id">
                                            <button
                                                type="button"
                                                @@click="selectMemberRider(rider)"
                                                class="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-3 py-3 text-left last:border-b-0 hover:bg-orange-50"
                                                :class="String(selectedMemberDriverId) === String(rider.id) ? 'bg-orange-50' : ''"
                                            >
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-black text-slate-900" x-text="rider.name"></span>
                                                    <span class="mt-0.5 block truncate text-xs font-semibold text-slate-500" x-text="[rider.phone, rider.vehicle_type, rider.vehicle_number].filter(Boolean).join(' / ')"></span>
                                                </span>
                                                <span x-show="isTeamMember(rider.id)" class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-500">Added</span>
                                            </button>
                                        </template>
                                        <div x-show="filteredRiders().length === 0" class="px-3 py-6 text-center text-sm font-semibold text-slate-400">
                                            No matching riders.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" @@click="addMember()" :disabled="!selectedMemberDriverId || isTeamMember(selectedMemberDriverId)" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">Add</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto overflow-y-hidden rounded-2xl border border-slate-200">
                        <div class="grid min-w-[680px] grid-cols-[minmax(0,1fr)_120px_220px] bg-slate-50 px-4 py-3 text-[10px] font-black uppercase tracking-wider text-slate-500">
                            <div>Rider</div>
                            <div>Role</div>
                            <div class="text-right">Action</div>
                        </div>
                        <template x-if="members.length === 0">
                            <div class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No riders have been added to this team.</div>
                        </template>
                        <template x-for="member in members" :key="member.id">
                            <div class="grid min-w-[680px] grid-cols-[minmax(0,1fr)_120px_220px] items-center gap-3 border-t border-slate-100 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-900" x-text="member.driver?.name || '-'"></p>
                                    <p class="truncate text-xs font-semibold text-slate-500" x-text="member.driver?.phone || '-'"></p>
                                </div>
                                <div>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase" :class="member.role === 'leader' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-600'" x-text="member.role === 'leader' ? 'Leader' : 'Member'"></span>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" x-show="member.role !== 'leader'" @@click="makeLeader(member)" class="rounded-xl border border-orange-200 px-3 py-2 text-xs font-black text-orange-700 hover:bg-orange-50">Make Leader</button>
                                    <button type="button" x-show="member.role === 'leader'" @@click="removeLeader(member)" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50">Remove Leader</button>
                                    <button type="button" @@click="removeMember(member)" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-black text-rose-600 hover:bg-rose-50">Remove</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="handoverModal.open"
             x-cloak
             class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
             style="display:none"
             @@click.self="handoverModal.open=false"
             @@keydown.escape.window="handoverModal.open=false">
            <div x-show="handoverModal.open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @@click.stop
                 class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start gap-4 border-b border-slate-200 px-6 py-5">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-2xl font-black text-slate-950">New Handover</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Assign package custody to a team member.</p>
                    </div>
                    <button type="button" @@click="handoverModal.open=false" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Team</label>
                        <select x-model="handoverModal.rider_team_id" @@change="loadHandoverTeamMembers()" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">Choose team</option>
                            <template x-for="team in teams.filter(t => t.is_active)" :key="team.id">
                                <option :value="team.id" x-text="team.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Handover Receiver</label>
                        <select x-model="handoverModal.receiver_driver_id" :disabled="!handoverModal.rider_team_id || handoverMembers.length === 0" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100 disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="" x-text="handoverModal.rider_team_id ? 'Choose receiver' : 'Choose team first'"></option>
                            <template x-for="member in handoverMembers" :key="member.driver?.id">
                                <option :value="member.driver.id" x-text="`${member.driver.name} / ${member.driver.phone}${member.role === 'leader' ? ' / Leader' : ''}`"></option>
                            </template>
                        </select>
                        <p x-show="handoverModal.rider_team_id && handoverMembers.length === 0" class="mt-2 text-xs font-bold text-amber-700">Add riders to this team before creating a handover.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Package Labels</label>
                        <textarea x-model="handoverModal.barcode_text" rows="7" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 font-mono text-sm font-bold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="TRKXXXX-001&#10;TRKXXXX-002"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Notes</label>
                        <textarea x-model="handoverModal.notes" rows="2" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Optional handover notes"></textarea>
                    </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50/60 px-6 py-4">
                    <button type="button" @@click="handoverModal.open=false" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700">Cancel</button>
                    <button type="button" @@click="saveHandover()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20">Create Handover</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="detailsModal.open"
             x-cloak
             class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
             style="display:none"
             @@click.self="detailsModal.open=false"
             @@keydown.escape.window="detailsModal.open=false">
            <div x-show="detailsModal.open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @@click.stop
                 class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                    <div class="min-w-0">
                        <h2 class="truncate font-mono text-xl font-black text-slate-950" x-text="detailsModal.handover?.handover_number"></h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500" x-text="`${detailsModal.handover?.team?.name || '-'} / ${detailsModal.handover?.receiver?.name || '-'}`"></p>
                    </div>
                    <button type="button" @@click="detailsModal.open=false" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-6">
                        <template x-for="metric in ['assigned','received','distributed','claimed','delivered','failed']" :key="metric">
                            <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                                <p class="text-[10px] font-black uppercase text-slate-400" x-text="metric"></p>
                                <p class="mt-1 text-xl font-black text-slate-950" x-text="detailsModal.handover?.counts?.[metric] || 0"></p>
                            </div>
                        </template>
                    </div>
                    <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="w-full min-w-[820px] divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-slate-500">Label</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-slate-500">Package</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-slate-500">Allocated Rider</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-wider text-slate-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-if="(detailsModal.handover?.items || []).length === 0">
                                    <tr><td colspan="4" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No labels attached to this handover.</td></tr>
                                </template>
                                <template x-for="item in detailsModal.handover?.items || []" :key="item.id">
                                    <tr>
                                        <td class="px-4 py-3 font-mono font-black text-slate-900" x-text="item.barcode"></td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-900" x-text="item.package?.description || '-'"></p>
                                            <p class="text-xs font-semibold text-slate-500" x-text="item.package?.tracking_code || ''"></p>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-700" x-text="item.allocated_to?.name || '-'"></td>
                                        <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black uppercase text-slate-700" x-text="formatStatus(item.status)"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function riderTeamsPage(config, warehouses, drivers) {
    return {
        config,
        warehouses,
        drivers,
        activeTab: 'teams',
        teams: [],
        handovers: [],
        members: [],
        teamSearch: '',
        teamStatusFilter: '',
        teamWarehouseFilter: '',
        showTeamFilters: false,
        teamPage: 1,
        teamPerPage: 10,
        handoverSearch: '',
        handoverStatusFilter: '',
        handoverTeamFilter: '',
        handoverCustodyFilter: '',
        showHandoverFilters: false,
        handoverPage: 1,
        handoverPerPage: 10,
        memberSearch: '',
        memberDropdownOpen: false,
        selectedMemberDriverId: '',
        teamModal: { open: false, id: null, name: '', warehouse_id: '', is_active: true },
        membersModal: { open: false, team: null },
        handoverMembers: [],
        handoverModal: { open: false, rider_team_id: '', receiver_driver_id: '', barcode_text: '', notes: '' },
        detailsModal: { open: false, handover: null },

        init() {
            this.loadTeams();
            this.loadHandovers();
        },

        url(template, values) {
            let out = template;
            Object.entries(values).forEach(([key, value]) => out = out.replace(key, value));
            return out;
        },

        async request(url, options = {}) {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken,
                },
                ...options,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                throw new Error(data.message || 'Request failed');
            }
            return data;
        },

        async loadTeams() {
            try {
                const data = await this.request(this.config.teamsUrl);
                this.teams = data.data.teams || [];
                this.teamPage = Math.min(this.teamPage, this.teamTotalPages());
            } catch (error) {
                alert(error.message || 'Could not load rider teams.');
            }
        },

        async loadHandovers() {
            try {
                const data = await this.request(this.config.handoversUrl);
                this.handovers = data.data.handovers || [];
                this.handoverPage = Math.min(this.handoverPage, this.handoverTotalPages());
            } catch (error) {
                alert(error.message || 'Could not load handovers.');
            }
        },

        openTeamModal(team = null) {
            this.teamModal = {
                open: true,
                id: team?.id || null,
                name: team?.name || '',
                warehouse_id: team?.warehouse?.id || (this.warehouses.length === 1 ? this.warehouses[0].id : ''),
                is_active: team?.is_active ?? true,
            };
        },

        async saveTeam() {
            if (!this.teamModal.name.trim()) {
                alert('Enter the rider team name.');
                return;
            }

            if (!this.teamModal.warehouse_id) {
                alert('Select the warehouse this rider team belongs to.');
                return;
            }

            try {
                const id = this.teamModal.id;
                const url = id ? this.url(this.config.updateTeamUrl, {'__TEAM__': id}) : this.config.storeTeamUrl;
                await this.request(url, { method: id ? 'PUT' : 'POST', body: JSON.stringify(this.teamModal) });
                this.teamModal.open = false;
                await this.loadTeams();
            } catch (error) {
                alert(error.message || 'Could not save rider team.');
            }
        },

        async openMembersModal(team) {
            this.membersModal = { open: true, team };
            this.memberSearch = '';
            this.selectedMemberDriverId = '';
            this.memberDropdownOpen = false;
            await this.loadMembers(team);
        },

        async loadMembers(team) {
            try {
                const data = await this.request(this.url(this.config.membersUrl, {'__TEAM__': team.id}));
                this.members = data.data.members || [];
            } catch (error) {
                alert(error.message || 'Could not load team members.');
            }
        },

        async addMember() {
            if (!this.membersModal.team) return;
            if (!this.selectedMemberDriverId || this.isTeamMember(this.selectedMemberDriverId)) return;
            try {
                await this.request(this.url(this.config.addMemberUrl, {'__TEAM__': this.membersModal.team.id}), {
                    method: 'POST',
                    body: JSON.stringify({ driver_id: this.selectedMemberDriverId }),
                });
                this.memberSearch = '';
                this.selectedMemberDriverId = '';
                this.memberDropdownOpen = false;
                await this.loadMembers(this.membersModal.team);
                await this.loadTeams();
            } catch (error) {
                alert(error.message || 'Could not add rider.');
            }
        },

        async removeMember(member) {
            if (!this.membersModal.team || !member.driver?.id || !confirm('Remove this rider from the team?')) return;
            try {
                await this.request(this.url(this.config.removeMemberUrl, {
                    '__TEAM__': this.membersModal.team.id,
                    '__DRIVER__': member.driver.id,
                }), { method: 'DELETE' });
                await this.loadMembers(this.membersModal.team);
                await this.loadTeams();
            } catch (error) {
                alert(error.message || 'Could not remove rider.');
            }
        },

        async makeLeader(member) {
            if (!this.membersModal.team || !member.driver?.id) return;
            try {
                await this.request(this.url(this.config.makeLeaderUrl, {
                    '__TEAM__': this.membersModal.team.id,
                    '__DRIVER__': member.driver.id,
                }), { method: 'POST' });
                await this.loadMembers(this.membersModal.team);
                await this.loadTeams();
            } catch (error) {
                alert(error.message || 'Could not mark rider as leader.');
            }
        },

        async removeLeader(member) {
            if (!this.membersModal.team || !member.driver?.id) return;
            try {
                await this.request(this.url(this.config.removeLeaderUrl, {
                    '__TEAM__': this.membersModal.team.id,
                    '__DRIVER__': member.driver.id,
                }), { method: 'DELETE' });
                await this.loadMembers(this.membersModal.team);
                await this.loadTeams();
            } catch (error) {
                alert(error.message || 'Could not remove leader role.');
            }
        },

        filteredRiders() {
            const term = this.memberSearch.trim().toLowerCase();
            return this.drivers
                .filter((rider) => {
                    if (!term) return true;
                    return [rider.name, rider.phone, rider.vehicle_type, rider.vehicle_number]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase()
                        .includes(term);
                })
                .slice(0, 40);
        },

        selectMemberRider(rider) {
            this.selectedMemberDriverId = rider.id;
            this.memberSearch = [rider.name, rider.phone].filter(Boolean).join(' / ');
            this.memberDropdownOpen = false;
        },

        isTeamMember(driverId) {
            return this.members.some((member) => String(member.driver?.id || '') === String(driverId || ''));
        },

        openHandoverModal() {
            this.handoverMembers = [];
            this.handoverModal = { open: true, rider_team_id: '', receiver_driver_id: '', barcode_text: '', notes: '' };
        },

        async loadHandoverTeamMembers() {
            this.handoverModal.receiver_driver_id = '';
            this.handoverMembers = [];
            const team = this.teams.find((item) => String(item.id) === String(this.handoverModal.rider_team_id));
            if (!team) return;

            try {
                const data = await this.request(this.url(this.config.membersUrl, {'__TEAM__': team.id}));
                this.handoverMembers = data.data.members || [];
                if (this.handoverMembers.length === 1) {
                    this.handoverModal.receiver_driver_id = this.handoverMembers[0].driver?.id || '';
                }
            } catch (error) {
                alert(error.message || 'Could not load team members.');
            }
        },

        async saveHandover() {
            if (!this.handoverModal.rider_team_id) {
                alert('Select the rider team.');
                return;
            }

            if (!this.handoverModal.receiver_driver_id) {
                alert('Select the handover receiver.');
                return;
            }

            try {
                await this.request(this.config.storeHandoverUrl, { method: 'POST', body: JSON.stringify(this.handoverModal) });
                this.handoverModal.open = false;
                await this.loadHandovers();
                this.activeTab = 'handovers';
            } catch (error) {
                alert(error.message || 'Could not create handover.');
            }
        },

        async openHandoverDetails(handover) {
            try {
                const data = await this.request(this.url(this.config.showHandoverUrl, {'__HANDOVER__': handover.id}));
                this.detailsModal.handover = data.data.handover;
                this.detailsModal.open = true;
            } catch (error) {
                alert(error.message || 'Could not open handover.');
            }
        },

        filteredTeams() {
            const term = this.teamSearch.trim().toLowerCase();
            return this.teams.filter((team) => {
                const haystack = [team.name, team.warehouse?.name, team.warehouse?.code].filter(Boolean).join(' ').toLowerCase();
                if (term && !haystack.includes(term)) return false;
                if (this.teamStatusFilter === 'active' && !team.is_active) return false;
                if (this.teamStatusFilter === 'inactive' && team.is_active) return false;
                if (this.teamWarehouseFilter && String(team.warehouse?.id || '') !== String(this.teamWarehouseFilter)) return false;
                return true;
            });
        },

        paginatedTeams() {
            const start = (this.teamPage - 1) * this.teamPerPage;
            return this.filteredTeams().slice(start, start + this.teamPerPage);
        },

        teamTotalPages() {
            return Math.max(1, Math.ceil(this.filteredTeams().length / this.teamPerPage));
        },

        teamFooterText() {
            const total = this.filteredTeams().length;
            if (!total) return 'Showing 0 of 0';
            const start = (this.teamPage - 1) * this.teamPerPage + 1;
            const end = Math.min(total, start + this.teamPerPage - 1);
            return `Showing ${start} to ${end} of ${total}`;
        },

        clearTeamFilters() {
            this.teamSearch = '';
            this.teamStatusFilter = '';
            this.teamWarehouseFilter = '';
            this.teamPage = 1;
        },

        filteredHandovers() {
            const term = this.handoverSearch.trim().toLowerCase();
            return this.handovers.filter((handover) => {
                const haystack = [handover.handover_number, handover.team?.name, handover.receiver?.name, handover.receiver?.phone].filter(Boolean).join(' ').toLowerCase();
                if (term && !haystack.includes(term)) return false;
                if (this.handoverStatusFilter && handover.status !== this.handoverStatusFilter) return false;
                if (this.handoverTeamFilter && String(handover.team?.id || '') !== String(this.handoverTeamFilter)) return false;
                if (this.handoverCustodyFilter === 'with_receiver' && Number(handover.counts?.with_receiver || 0) <= 0) return false;
                if (this.handoverCustodyFilter === 'distributed' && Number(handover.counts?.distributed || 0) <= 0) return false;
                if (this.handoverCustodyFilter === 'delivered' && Number(handover.counts?.delivered || 0) <= 0) return false;
                return true;
            });
        },

        paginatedHandovers() {
            const start = (this.handoverPage - 1) * this.handoverPerPage;
            return this.filteredHandovers().slice(start, start + this.handoverPerPage);
        },

        handoverTotalPages() {
            return Math.max(1, Math.ceil(this.filteredHandovers().length / this.handoverPerPage));
        },

        handoverFooterText() {
            const total = this.filteredHandovers().length;
            if (!total) return 'Showing 0 of 0';
            const start = (this.handoverPage - 1) * this.handoverPerPage + 1;
            const end = Math.min(total, start + this.handoverPerPage - 1);
            return `Showing ${start} to ${end} of ${total}`;
        },

        clearHandoverFilters() {
            this.handoverSearch = '';
            this.handoverStatusFilter = '';
            this.handoverTeamFilter = '';
            this.handoverCustodyFilter = '';
            this.handoverPage = 1;
        },

        formatStatus(status) {
            return String(status || '-').replaceAll('_', ' ');
        },
    };
}
</script>
@endsection
