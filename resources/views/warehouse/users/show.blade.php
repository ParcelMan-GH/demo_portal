@extends('warehouse.layouts.app')

@section('title', 'View Warehouse User - ' . $admin->name)
@section('breadcrumb-parent', 'Warehouse')
@section('breadcrumb-current', $admin->name)
@section('page-title', $admin->name)

@section('content')
@php
    $currentAdmin = Auth::guard('admin')->user();
    $canManage = $canManage ?? false;
    $isSelf = $admin->id === $currentAdmin->id;
    $allPermissions = $admin->getAllPermissions();
    $groupedPermissions = $allPermissions->groupBy(fn ($permission) => $permission->module ?? 'general');
    $initialTab = request('tab', 'permissions');
    if (!in_array($initialTab, ['permissions', 'activity'], true)) {
        $initialTab = 'permissions';
    }
@endphp

<div
    class="space-y-6"
    x-data="{
        activeTab: @js($initialTab),
        auditLogsMeta: { total: 0 },
        showModal: false,
        showStatusModal: false,
        submitting: false,
        statusSubmitting: false,
        formErrors: {},
        modalMode: 'edit',
        canAssignRoles: @js($currentAdmin->hasPermission('warehouse.users.assign_roles')),
        isSelf: @js($isSelf),
        editingUser: { is_self: @js($isSelf) },
        changePassword: false,
        formData: {
            name: @js($admin->name),
            email: @js($admin->email),
            phone: @js($admin->phone),
            role_id: @js(optional($admin->roles->first())?->id ? (string) optional($admin->roles->first())->id : ''),
            password: '',
            password_confirmation: '',
            is_active: @js($admin->is_active ? '1' : '0'),
        },
        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        },
        openEditModal() {
            this.formErrors = {};
            this.modalMode = 'edit';
            this.changePassword = false;
            this.formData.password = '';
            this.formData.password_confirmation = '';
            this.showModal = true;
        },
        closeModal() {
            if (this.submitting) return;
            this.showModal = false;
            this.formErrors = {};
        },
        openStatusModal() {
            this.showStatusModal = true;
        },
        closeStatusModal() {
            if (this.statusSubmitting) return;
            this.showStatusModal = false;
        },
        async submitStatusToggle() {
            this.statusSubmitting = true;
            try {
                const response = await fetch(@js(route('warehouse.users.toggle-active', $admin)), {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                    },
                    body: JSON.stringify({}),
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result.message || 'Failed to update user status.');
                }
                window.showToast?.(result.message || 'User status updated successfully.', 'success');
                window.location.reload();
            } catch (error) {
                window.showToast?.(error.message || 'Failed to update user status.', 'error');
            } finally {
                this.statusSubmitting = false;
                this.showStatusModal = false;
            }
        },
        async submitForm() {
            this.submitting = true;
            this.formErrors = {};

            const payload = {
                name: this.formData.name,
                email: this.formData.email,
                phone: this.formData.phone,
            };

            if (!this.isSelf) {
                payload.is_active = this.formData.is_active;
            }

            if (this.canAssignRoles && this.formData.role_id) {
                payload.role_id = Number(this.formData.role_id);
            }

            if (this.changePassword && this.formData.password) {
                payload.password = this.formData.password;
                payload.password_confirmation = this.formData.password_confirmation;
            }

            try {
                const response = await fetch(@js(route('warehouse.users.update', $admin)), {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && result.errors) {
                        this.formErrors = {};
                        for (const [key, messages] of Object.entries(result.errors)) {
                            this.formErrors[key] = Array.isArray(messages) ? messages[0] : messages;
                        }
                    } else {
                        this.formErrors.general = result.message || 'Failed to update user.';
                    }
                    window.showToast?.(result.message || 'Failed to update user.', 'error');
                    return;
                }

                window.showToast?.(result.message || 'User updated successfully.', 'success');
                this.closeModal();
                window.location.reload();
            } catch (error) {
                this.formErrors.general = 'Failed to update user.';
                window.showToast?.('Failed to update user.', 'error');
            } finally {
                this.submitting = false;
            }
        },
    }"
>

    <!-- Hero / Header Card -->
    <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-[2rem]">
            <div class="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.24),transparent_58%)]"></div>
            <div class="absolute inset-y-0 left-0 w-1/2 bg-[radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.95),transparent_70%)]"></div>
        </div>

        <div class="relative p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <a href="{{ route('warehouse.users.index') }}" class="inline-flex h-11 w-auto shrink-0 items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 text-sm font-black text-slate-100 transition hover:bg-white/15">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back</span>
                </a>

                <div class="ml-auto flex w-auto max-w-[calc(100%-5.75rem)] flex-wrap items-center justify-end gap-2 sm:max-w-none">
                    <span class="inline-flex h-9 items-center whitespace-nowrap rounded-full px-3 text-xs font-black {{ $admin->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-300' }}">
                        <span class="mr-2 h-2 w-2 rounded-full {{ $admin->is_active ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                        {{ $admin->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($isSelf)
                        <span class="inline-flex h-9 items-center whitespace-nowrap rounded-full border border-orange-400/45 bg-orange-500/15 px-3 text-xs font-black text-orange-100">This is you</span>
                    @endif
                    @if($canManage)
                        <button type="button"
                                @click="openEditModal()"
                                class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-white/15 bg-white/10 px-3 text-xs font-black text-slate-100 transition hover:bg-white/15">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487 19.5 7.125M18 2.25a2.121 2.121 0 0 1 3 3L7.5 18.75 3 21l2.25-4.5L18 2.25Z"/>
                            </svg>
                            Edit User
                        </button>
                        @if(!$isSelf)
                            <button type="button"
                                    @click="openStatusModal()"
                                    class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border px-3 text-xs font-black transition {{ $admin->is_active ? 'border-amber-400/45 bg-amber-500/15 text-amber-100 hover:bg-amber-500/25' : 'border-emerald-400/45 bg-emerald-500/15 text-emerald-100 hover:bg-emerald-500/25' }}">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $admin->is_active ? 'M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636' : 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' }}"/>
                                </svg>
                                {{ $admin->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        @endif
                    @endif
                </div>
            </div>

            <div class="relative mt-7 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 lg:max-w-[760px] lg:shrink">
                    <div class="flex min-w-0 items-start gap-4 sm:gap-5">
                        <div class="relative shrink-0">
                            <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-orange-600 text-2xl font-black text-white shadow-xl shadow-orange-600/25 sm:h-24 sm:w-24 sm:text-4xl">
                                {{ strtoupper(substr($admin->name, 0, 1)) }}
                            </div>
                            <div class="absolute -bottom-1 -right-1 flex h-7 w-7 items-center justify-center rounded-full border-4 border-slate-950 {{ $admin->is_active ? 'bg-emerald-500' : 'bg-slate-500' }} sm:h-8 sm:w-8">
                                <svg class="h-3.5 w-3.5 text-white sm:h-4 sm:w-4" fill="currentColor" viewBox="0 0 20 20">
                                    @if($admin->is_active)
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0z" clip-rule="evenodd"/>
                                    @else
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414z" clip-rule="evenodd"/>
                                    @endif
                                </svg>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <p class="text-[11px] font-black uppercase tracking-[0.22em] text-orange-200">Warehouse User Profile</p>
                            <h1 class="mt-2 max-w-4xl break-words text-3xl font-black leading-tight text-white sm:text-5xl xl:text-4xl 2xl:text-5xl">{{ $admin->name }}</h1>
                            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm font-bold text-slate-300 sm:text-base">
                                <span>{{ $admin->email }}</span>
                                <span class="text-slate-500">/</span>
                                <span>{{ $admin->phone ?: 'No phone set' }}</span>
                                <span class="text-slate-500">/</span>
                                <span>Created by {{ $admin->creator?->name ?? 'System' }}</span>
                                <span class="text-slate-500">/</span>
                                <span>Created {{ $admin->created_at->format('d M Y, h:i A') }}</span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($admin->roles as $role)
                                    <span class="inline-flex h-8 items-center rounded-full border border-orange-400/35 bg-orange-500/15 px-3 text-xs font-black text-orange-100">{{ $role->name }}</span>
                                @endforeach
                                <span class="inline-flex h-8 items-center rounded-full border border-white/10 bg-white/10 px-3 text-xs font-black text-slate-200">Updated {{ $admin->updated_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:ml-auto lg:w-[430px] lg:shrink-0 2xl:w-[480px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:p-4">
                        <p class="text-2xl font-black leading-tight text-white">{{ number_format($admin->roles->count()) }} roles</p>
                        <p class="mt-2 text-sm font-black leading-snug text-slate-400">{{ number_format($allPermissions->count()) }} permissions</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:p-4">
                        <p class="text-2xl font-black leading-tight text-white">{{ $admin->last_login_at ? $admin->last_login_at->format('d M') : 'Never' }}</p>
                        <p class="mt-2 text-sm font-black leading-snug text-slate-400">{{ $admin->last_login_at ? $admin->last_login_at->format('h:i A') : 'No login recorded' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:flex lg:min-h-[620px]">
        <aside class="border-b border-slate-100 bg-white p-3 lg:w-56 lg:shrink-0 lg:border-b-0 lg:border-r">
            <p class="mb-2 px-2 text-[9px] font-black uppercase tracking-widest text-slate-400">User Record</p>
            <div class="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible">
                <button
                    type="button"
                    @click="setTab('permissions')"
                    class="group flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-left transition-all duration-150 lg:w-full"
                    :class="activeTab === 'permissions'
                        ? 'bg-orange-50 text-orange-800 ring-1 ring-orange-100 shadow-sm'
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'"
                >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition"
                          :class="activeTab === 'permissions' ? 'bg-orange-600 text-white shadow-sm shadow-orange-200' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600'">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </span>
                    <span class="text-xs" :class="activeTab === 'permissions' ? 'font-black' : 'font-bold'">Permissions</span>
                    <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-black"
                          :class="activeTab === 'permissions' ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-500'">{{ $allPermissions->count() }}</span>
                </button>

                <button
                    type="button"
                    @click="setTab('activity')"
                    class="group flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-left transition-all duration-150 lg:w-full"
                    :class="activeTab === 'activity'
                        ? 'bg-amber-50 text-amber-800 ring-1 ring-amber-100 shadow-sm'
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'"
                >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition"
                          :class="activeTab === 'activity' ? 'bg-amber-500 text-white shadow-sm shadow-amber-200' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600'">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </span>
                    <span class="whitespace-nowrap text-xs" :class="activeTab === 'activity' ? 'font-black' : 'font-bold'">Activity Logs</span>
                    <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-black"
                          :class="activeTab === 'activity' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500'"
                          x-text="typeof auditLogsMeta !== 'undefined' ? auditLogsMeta.total : '...'">&hellip;</span>
                </button>
            </div>
        </aside>

        <div class="min-w-0 flex-1 bg-slate-50/60 p-4 sm:p-6">

        <!-- Permissions Panel -->
        <div x-show="activeTab === 'permissions'" x-cloak x-transition.opacity.duration.150ms class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-base font-black text-slate-900">Permissions</h2>
                            <p class="truncate text-sm font-semibold text-slate-500">Access granted through assigned warehouse roles.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700">{{ $allPermissions->count() }} total</span>
                </div>
            </div>
            @if($groupedPermissions->isNotEmpty())
                <div class="p-5">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @php
                            $moduleColors = [
                                'users' => ['from-blue-500/15 to-blue-600/10', 'text-blue-600', 'border-blue-100'],
                                'roles' => ['from-violet-500/15 to-violet-600/10', 'text-violet-600', 'border-violet-100'],
                                'vendors' => ['from-amber-500/15 to-amber-600/10', 'text-amber-600', 'border-amber-100'],
                                'shipments' => ['from-cyan-500/15 to-cyan-600/10', 'text-cyan-600', 'border-cyan-100'],
                                'drivers' => ['from-emerald-500/15 to-emerald-600/10', 'text-emerald-600', 'border-emerald-100'],
                                'warehouses' => ['from-orange-500/15 to-orange-600/10', 'text-orange-600', 'border-orange-100'],
                                'warehouse' => ['from-orange-500/15 to-orange-600/10', 'text-orange-600', 'border-orange-100'],
                                'settings' => ['from-slate-500/15 to-slate-600/10', 'text-slate-600', 'border-slate-200'],
                                'platform_settings' => ['from-slate-500/15 to-slate-600/10', 'text-slate-600', 'border-slate-200'],
                                'invoices' => ['from-rose-500/15 to-rose-600/10', 'text-rose-600', 'border-rose-100'],
                                'dashboard' => ['from-indigo-500/15 to-indigo-600/10', 'text-indigo-600', 'border-indigo-100'],
                                'reports' => ['from-teal-500/15 to-teal-600/10', 'text-teal-600', 'border-teal-100'],
                            ];
                            $moduleLabels = [
                                'users' => 'Users',
                                'roles' => 'Roles',
                                'vendors' => 'Vendors',
                                'shipments' => 'Orders',
                                'drivers' => 'Drivers',
                                'warehouses' => 'Warehouses',
                                'warehouse' => 'Warehouse Ops',
                                'settings' => 'Settings',
                                'platform_settings' => 'Platform Settings',
                                'invoices' => 'Invoices',
                                'dashboard' => 'Dashboard',
                                'reports' => 'Reports',
                            ];
                            $moduleIcons = [
                                'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
                                'roles' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                                'vendors' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
                                'shipments' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                                'drivers' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                                'warehouses' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>',
                                'warehouse' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>',
                                'settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                'platform_settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                'invoices' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>',
                                'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>',
                                'reports' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                            ];

                            $actionColorMap = [
                                'view' => 'bg-sky-50 text-sky-700 ring-sky-200/60',
                                'create' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/60',
                                'edit' => 'bg-amber-50 text-amber-700 ring-amber-200/60',
                                'update' => 'bg-amber-50 text-amber-700 ring-amber-200/60',
                                'delete' => 'bg-rose-50 text-rose-700 ring-rose-200/60',
                                'deactivate' => 'bg-rose-50 text-rose-700 ring-rose-200/60',
                                'export' => 'bg-violet-50 text-violet-700 ring-violet-200/60',
                                'assign' => 'bg-indigo-50 text-indigo-700 ring-indigo-200/60',
                                'manage' => 'bg-purple-50 text-purple-700 ring-purple-200/60',
                                'activate' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/60',
                                'scan' => 'bg-teal-50 text-teal-700 ring-teal-200/60',
                            ];
                            $actionIconMap = [
                                'view' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
                                'create' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>',
                                'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                                'update' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                                'delete' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
                                'deactivate' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>',
                                'export' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                                'assign' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                'manage' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                                'activate' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                'scan' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>',
                            ];

                            // Keyword-based matching for compound actions like assign_driver, dashboard_view, etc.
                            $resolveActionStyle = function(string $action) use ($actionColorMap, $actionIconMap) {
                                $a = strtolower($action);
                                $keywords = ['delete', 'deactivate', 'create', 'edit', 'update', 'view', 'export', 'assign', 'manage', 'activate', 'scan'];
                                foreach ($keywords as $keyword) {
                                    if (str_contains($a, $keyword)) {
                                        return [
                                            'color' => $actionColorMap[$keyword],
                                            'icon' => $actionIconMap[$keyword],
                                        ];
                                    }
                                }
                                return [
                                    'color' => 'bg-slate-50 text-slate-600 ring-slate-200/60',
                                    'icon' => $actionIconMap['manage'],
                                ];
                            };
                        @endphp
                        @foreach($groupedPermissions as $module => $permissions)
                            @php
                                $key = strtolower(str_replace(' ', '_', $module));
                                $colors = $moduleColors[$key] ?? ['from-slate-500/15 to-slate-600/10', 'text-slate-600', 'border-slate-200'];
                                $icon = $moduleIcons[$key] ?? $moduleIcons['settings'];
                                $label = $moduleLabels[$key] ?? Str::of($module)->replace('_', ' ')->title()->toString();
                            @endphp
                            <div class="group rounded-xl border {{ $colors[2] }} bg-white p-4 transition-all duration-200 hover:shadow-md hover:shadow-slate-200/50 hover:border-slate-200">
                                <div class="flex items-center gap-2.5 mb-3 pb-3 border-b border-slate-100">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br {{ $colors[0] }} flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 {{ $colors[1] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icon !!}</svg>
                                    </div>
                                    <h3 class="text-xs font-bold text-slate-800 tracking-wide">{{ $label }}</h3>
                                    <span class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-slate-100 text-[10px] font-bold text-slate-500">{{ $permissions->count() }}</span>
                                </div>
                                <div class="space-y-1.5">
                                    @foreach($permissions as $permission)
                                        @php
                                            $style = $resolveActionStyle($permission->action);
                                            $permLabel = $permission->description ?: Str::of($permission->action)->replace('_', ' ')->title()->toString();
                                        @endphp
                                        <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg {{ $style['color'] }} ring-1 transition-colors" title="{{ $permission->name }}">
                                            <svg class="w-3 h-3 flex-shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $style['icon'] !!}</svg>
                                            <span class="text-[11px] font-semibold">{{ $permLabel }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-500">No permissions assigned to this user role yet.</p>
                    <p class="text-xs text-slate-400 mt-1">Assign a role to grant permissions.</p>
                </div>
            @endif
        </div>

        <!-- Activity Logs Panel -->
        <div x-show="activeTab === 'activity'" x-cloak x-transition.opacity.duration.150ms
             x-data="auditLogsTable"
             x-init="$watch('meta', val => { auditLogsMeta = val })"
             data-endpoint="{{ route('warehouse.users.audit-logs-data', $admin) }}"
             data-export-endpoint="{{ route('warehouse.users.audit-logs-data', $admin) }}"
             class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-base font-black text-slate-900">Activity Logs</h2>
                            <p class="truncate text-sm font-semibold text-slate-500">Audit activity recorded for this user.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700" x-text="meta.total + ' logs'">0 logs</span>
                </div>
            </div>

            <!-- Table Controls -->
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <!-- Search + Date Range -->
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-sm">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                            <input
                                type="text"
                                x-model="search"
                                @input.debounce.500ms="meta.current_page = 1; loadData()"
                                placeholder="Search logs..."
                                class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                            >
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <div class="relative w-full sm:w-56">
                            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Date Range</label>
                            <input
                                type="text"
                                x-ref="dateRange"
                                placeholder="Date range"
                                class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                readonly
                            >
                            <svg class="absolute left-3 bottom-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Right Controls -->
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <!-- Customize Columns -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                </svg>
                                View
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
                                 style="display: none;">
                                <template x-for="col in columns" :key="col.key">
                                    <button type="button" @click="toggleColumn(col.key)"
                                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <span x-text="col.label"></span>
                                        <svg x-show="visibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Export -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
                                 style="display: none;">
                                <button type="button" @click="exportData('csv'); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                                    <svg class="w-4 h-4 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @click="printData(); open = false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden">
                <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display: none;"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th x-show="visibleColumns.created_at" @click="sort('created_at')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                                    <div class="flex items-center">
                                        DATE
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'created_at' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.type" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                                    TYPE
                                </th>
                                <th x-show="visibleColumns.action" @click="sort('action')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                                    <div class="flex items-center">
                                        ACTION
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'action' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                                <th x-show="visibleColumns.request" class="px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                                    REQUEST
                                </th>
                                <th x-show="visibleColumns.result" @click="sort('status_code')" class="cursor-pointer px-5 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">
                                    <div class="flex items-center">
                                        RESULT
                                        <svg class="w-2.5 h-2.5 ml-1" :class="sortBy === 'status_code' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-if="logs.length === 0 && !loading">
                                <tr>
                                    <td :colspan="visibleColumnCount()" class="px-4 py-12 text-center text-sm font-semibold text-slate-500">
                                        No activity logs found
                                    </td>
                                </tr>
                            </template>

                            <template x-for="log in logs" :key="log.id">
                                <tr class="transition hover:bg-amber-50/30">
                                    <td x-show="visibleColumns.created_at" class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600" x-text="log.created_at"></td>
                                    <td x-show="visibleColumns.type" class="px-5 py-4">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-black"
                                                  :class="log.scope === 'warehouse' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-indigo-200 bg-indigo-50 text-indigo-700'"
                                                  x-text="log.scope === 'warehouse' ? 'Warehouse' : 'System'"></span>
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-black text-slate-700"
                                                  x-text="log.action_type"></span>
                                        </div>
                                    </td>
                                    <td x-show="visibleColumns.action" class="px-5 py-4 text-sm text-slate-600">
                                        <div class="font-black text-slate-900" x-text="log.action"></div>
                                        <div class="mt-0.5 max-w-sm text-xs font-semibold text-slate-500" x-text="log.description"></div>
                                    </td>
                                    <td x-show="visibleColumns.request" class="px-5 py-4 text-xs font-semibold text-slate-600">
                                        <div><span class="font-black text-slate-900" x-text="log.method"></span> <span class="break-all text-slate-500" x-text="log.url"></span></div>
                                        <div class="mt-1 font-mono text-[11px] text-slate-500" x-text="'IP: ' + log.ip_address"></div>
                                    </td>
                                    <td x-show="visibleColumns.result" class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                        <div class="font-black" :class="Number(log.status_code) >= 400 ? 'text-rose-700' : 'text-emerald-700'" x-text="'HTTP ' + log.status_code"></div>
                                        <div class="text-xs font-semibold text-slate-500" x-text="log.duration_ms + ' ms'"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">
                                Showing
                                <span class="font-medium" x-text="meta.from"></span>
                                to
                                <span class="font-medium" x-text="meta.to"></span>
                                of
                                <span class="font-medium" x-text="meta.total"></span>
                                results
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors"
                                        >
                                            <span x-text="perPage"></span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div
                                            x-show="open"
                                            @click.away="open = false"
                                            x-transition
                                            class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]"
                                            style="display: none;"
                                        >
                                            <button type="button" @click="perPage = 15; meta.current_page = 1; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 15 ? 'bg-slate-100/70' : ''">15</button>
                                            <button type="button" @click="perPage = 25; meta.current_page = 1; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                            <button type="button" @click="perPage = 50; meta.current_page = 1; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                            <button type="button" @click="perPage = 100; meta.current_page = 1; loadData(); open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="perPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs font-medium text-slate-600">
                                    Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span>
                                </div>

                                <div class="flex space-x-1">
                                    <button @click="firstPage()" :disabled="meta.current_page === 1"
                                            :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button @click="previousPage()" :disabled="meta.current_page === 1"
                                            :class="meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button @click="nextPage()" :disabled="meta.current_page === meta.last_page"
                                            :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    <button @click="lastPage()" :disabled="meta.current_page === meta.last_page"
                                            :class="meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('warehouse.users.partials.user-modal')

    <template x-teleport="body">
        <div x-show="showStatusModal"
             x-cloak
             class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
             @click.self="closeStatusModal()"
             @keydown.escape.window="closeStatusModal()">
            <div x-show="showStatusModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop
                 class="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="p-6">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl {{ $admin->is_active ? 'bg-amber-50 text-amber-600 ring-1 ring-amber-100' : 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100' }}">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $admin->is_active ? 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z' : 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' }}"/>
                        </svg>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-black text-slate-900">{{ $admin->is_active ? 'Deactivate User' : 'Activate User' }}</h3>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">
                            {{ $admin->is_active ? 'This user will no longer be able to access the warehouse portal.' : 'This user will regain access to the warehouse portal.' }}
                        </p>
                    </div>
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-sm font-black text-slate-900">{{ $admin->name }}</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ $admin->email }}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 bg-slate-50 p-4">
                    <button type="button"
                            @click="closeStatusModal()"
                            :disabled="statusSubmitting"
                            class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                        Cancel
                    </button>
                    <button type="button"
                            @click="submitStatusToggle()"
                            :disabled="statusSubmitting"
                            class="inline-flex items-center justify-center rounded-xl border-2 px-5 py-3 text-sm font-black text-white shadow-lg transition disabled:cursor-not-allowed disabled:opacity-50 {{ $admin->is_active ? 'border-amber-500 bg-amber-500 shadow-amber-500/20 hover:border-amber-600 hover:bg-amber-600' : 'border-emerald-600 bg-emerald-600 shadow-emerald-600/20 hover:border-emerald-700 hover:bg-emerald-700' }}">
                        <span x-show="!statusSubmitting">{{ $admin->is_active ? 'Deactivate User' : 'Activate User' }}</span>
                        <span x-show="statusSubmitting" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
