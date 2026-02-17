@extends('warehouse.layouts.app')

@section('title', 'Warehouse Users')
@section('page-title', 'Warehouse Users')

@php
    $usersConfig = [
        'endpoint' => route('warehouse.users.data'),
        'exportEndpoint' => route('warehouse.users.export'),
        'storeEndpoint' => route('warehouse.users.store'),
        'updateEndpointTemplate' => route('warehouse.users.update', ['user' => '__ID__']),
        'toggleEndpointTemplate' => route('warehouse.users.toggle-active', ['user' => '__ID__']),
        'csrfToken' => csrf_token(),
        'roles' => $roles,
        'permissions' => [
            'can_create' => $canCreateUsers,
            'can_edit' => $canEditUsers,
            'can_deactivate' => $canDeactivateUsers,
            'can_assign_roles' => $canAssignRoles,
        ],
    ];
@endphp

@section('content')
<div class="space-y-6" x-data="warehouseUsersPage" data-warehouse-users-config="{{ e(json_encode($usersConfig)) }}">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Warehouse Users</h2>
                <p class="text-sm text-slate-500">Manage users assigned to {{ $warehouse->name }}.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" x-text="meta.total + ' Users'"></span>
        </div>

        <div class="mt-5 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex flex-1 flex-col gap-3 md:flex-row md:items-center">
                <div class="relative md:max-w-xs w-full">
                    <input type="text" x-model="search" @@input.debounce.400ms="meta.current_page = 1; loadData()" placeholder="Search users..." class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-300/40">
                    <svg class="pointer-events-none absolute right-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button type="button" @@click="open = !open" class="inline-flex w-full md:w-56 items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <span x-text="selectedRoleName()"></span>
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @@click.away="open = false" class="absolute z-30 mt-2 w-full rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <button type="button" @@click="roleFilter = ''; open = false; meta.current_page = 1; loadData()" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">All roles</button>
                        <template x-for="role in roles" :key="role.id">
                            <button type="button" @@click="roleFilter = String(role.id); open = false; meta.current_page = 1; loadData()" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50" x-text="role.name"></button>
                        </template>
                    </div>
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button type="button" @@click="open = !open" class="inline-flex w-full md:w-44 items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <span x-text="statusFilterName"></span>
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @@click.away="open = false" class="absolute z-30 mt-2 w-full rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <button type="button" @@click="setStatusFilter('', 'All statuses'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">All statuses</button>
                        <button type="button" @@click="setStatusFilter('1', 'Active'); open = false" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Active</button>
                        <button type="button" @@click="setStatusFilter('0', 'Inactive'); open = false" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Inactive</button>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <div class="relative" x-data="{ open: false }">
                    <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                        View
                    </button>
                    <div x-show="open" x-cloak @@click.away="open = false" class="absolute right-0 z-30 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <template x-for="column in columns" :key="column.key">
                            <button type="button" @@click="toggleColumn(column.key)" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 flex items-center justify-between">
                                <span x-text="column.label"></span>
                                <svg x-show="visibleColumns[column.key]" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </button>
                    <div x-show="open" x-cloak @@click.away="open = false" class="absolute right-0 z-30 mt-2 w-40 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <button type="button" @@click="exportData('excel'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Excel</button>
                        <button type="button" @@click="exportData('pdf'); open = false" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">PDF</button>
                        <button type="button" @@click="exportData('csv'); open = false" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">CSV</button>
                        <button type="button" @@click="exportData('print'); open = false" class="mt-1 w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Print</button>
                    </div>
                </div>

                @if($canCreateUsers)
                    <button type="button" @@click="openCreateModal()" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add User
                    </button>
                @endif
            </div>
        </div>

        <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 relative">
            <div x-show="loading" x-cloak class="absolute inset-0 z-10 bg-white/65 backdrop-blur-[1px]"></div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th x-show="visibleColumns.name" @@click="sort('name')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 cursor-pointer">Name</th>
                        <th x-show="visibleColumns.role" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Role</th>
                        <th x-show="visibleColumns.email" @@click="sort('email')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 cursor-pointer">Email</th>
                        <th x-show="visibleColumns.status" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Status</th>
                        <th x-show="visibleColumns.created_at" @@click="sort('created_at')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 cursor-pointer">Created At</th>
                        <th x-show="visibleColumns.last_login_at" @@click="sort('last_login_at')" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 cursor-pointer">Last Login</th>
                        <th x-show="visibleColumns.actions" class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <tr x-show="!loading && users.length === 0" x-cloak>
                        <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center text-sm text-slate-500">No warehouse users found.</td>
                    </tr>

                    <template x-for="user in users" :key="user.id">
                        <tr class="hover:bg-slate-50/70">
                            <td x-show="visibleColumns.name" class="px-4 py-3 font-semibold text-slate-900" x-text="user.name"></td>
                            <td x-show="visibleColumns.role" class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" x-text="(user.roles && user.roles.length) ? user.roles[0].name : '-' "></span>
                            </td>
                            <td x-show="visibleColumns.email" class="px-4 py-3 text-slate-700" x-text="user.email"></td>
                            <td x-show="visibleColumns.status" class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'" x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td x-show="visibleColumns.created_at" class="px-4 py-3 text-slate-600" x-text="user.created_at || '-' "></td>
                            <td x-show="visibleColumns.last_login_at" class="px-4 py-3 text-slate-600" x-text="user.last_login_at || 'Never'"></td>
                            <td x-show="visibleColumns.actions" class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" @@click="openEditModal(user)" x-show="permissions.can_edit && user.can_manage" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                                    <button type="button" @@click="toggleUserStatus(user)" x-show="permissions.can_deactivate && user.can_manage && !user.is_self" class="rounded-lg border px-3 py-1.5 text-xs font-semibold" :class="user.is_active ? 'border-rose-200 text-rose-700 bg-rose-50 hover:bg-rose-100' : 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100'" x-text="user.is_active ? 'Deactivate' : 'Activate'"></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600" x-text="`Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total || 0} users`"></p>
                <div class="flex items-center gap-3 justify-end">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span>Rows</span>
                        <select x-model.number="perPage" @@change="setPerPage($event.target.value)" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <span class="text-sm text-slate-700" x-text="`Page ${meta.current_page || 1} of ${meta.last_page || 1}`"></span>
                    <div class="flex items-center gap-1">
                        <button @@click="firstPage()" :disabled="meta.current_page <= 1" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">&#171;</button>
                        <button @@click="previousPage()" :disabled="meta.current_page <= 1" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">&#8249;</button>
                        <button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">&#8250;</button>
                        <button @@click="lastPage()" :disabled="meta.current_page >= meta.last_page" class="h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-600 disabled:opacity-40">&#187;</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="showModal" x-cloak class="fixed inset-0 z-[120] overflow-y-auto" @@keydown.escape.window="closeModal()">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @@click="closeModal()"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="showModal" x-transition @@click.stop class="relative w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl">
                    <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900" x-text="modalMode === 'create' ? 'Add Warehouse User' : 'Edit Warehouse User'"></h3>
                            <p class="text-sm text-slate-500">Manage warehouse user account and role assignment.</p>
                        </div>
                        <button type="button" @@click="closeModal()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @@submit.prevent="submitForm()" class="px-6 py-5 space-y-4">
                        <div x-show="errors.general" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" x-text="errors.general?.[0] || errors.general"></div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="form.name" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-300/40">
                            <p x-show="errors.name" x-cloak class="mt-1 text-xs text-rose-600" x-text="errors.name?.[0]"></p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" x-model="form.email" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-300/40">
                            <p x-show="errors.email" x-cloak class="mt-1 text-xs text-rose-600" x-text="errors.email?.[0]"></p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Assign Role <span class="text-rose-500">*</span></label>
                            <select x-model="form.role_id" :disabled="modalMode === 'edit' && !permissions.can_assign_roles" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-300/40 disabled:bg-slate-100">
                                <option value="">Select role</option>
                                <template x-for="role in roles" :key="role.id">
                                    <option :value="role.id" x-text="role.name"></option>
                                </template>
                            </select>
                            <p x-show="errors.role_id" x-cloak class="mt-1 text-xs text-rose-600" x-text="errors.role_id?.[0]"></p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Password <span x-show="modalMode === 'create'" class="text-rose-500">*</span></label>
                            <input type="password" x-model="form.password" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-300/40" :placeholder="modalMode === 'create' ? 'Minimum 8 characters' : 'Leave blank to keep current password'">
                            <p x-show="errors.password" x-cloak class="mt-1 text-xs text-rose-600" x-text="errors.password?.[0]"></p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Confirm Password <span x-show="modalMode === 'create'" class="text-rose-500">*</span></label>
                            <input type="password" x-model="form.password_confirmation" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-300/40">
                        </div>

                        <div x-show="modalMode === 'edit'" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input type="checkbox" x-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                                Account is active
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @@click="closeModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                            <button type="submit" :disabled="submitting" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
                                <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-text="submitting ? 'Saving...' : (modalMode === 'create' ? 'Create User' : 'Update User')"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
