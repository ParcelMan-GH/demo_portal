@extends('admin.layouts.app')

@php
    $isWarehouseScope = ($roleScope ?? 'system') === 'warehouse';
    $backUrl = $isWarehouseScope ? route('admin.roles.warehouse.index') : route('admin.roles.index');
@endphp

@section('title', $isWarehouseScope ? 'Create Warehouse Role' : 'Create Role')

@section('breadcrumb-parent', 'Roles & Permissions')
@section('breadcrumb-current', $isWarehouseScope ? 'Create Warehouse Role' : 'Create Role')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <a href="{{ $backUrl }}"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        <span>{{ $isWarehouseScope ? 'Back to Warehouse Roles' : 'Back to System Roles' }}</span>
    </a>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 shadow-xl shadow-slate-300/40 ring-1 ring-slate-100 backdrop-blur-xl">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 px-6 py-5 text-white lg:px-8">
            <div class="flex items-start gap-4">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m-6-8h6m4 11H5a2 2 0 01-2-2V7a2 2 0 012-2h9l5 5v7a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ $isWarehouseScope ? 'Create Warehouse Role' : 'Create System Role' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-200">
                        {{ $isWarehouseScope ? 'Define a warehouse-specific role with access boundaries.' : 'Define a system role with the exact permissions needed.' }}
                    </p>
                </div>
            </div>
        </div>

        <form
            action="{{ route('admin.roles.store') }}"
            method="POST"
            class="space-y-6 p-6 lg:p-8"
            data-role-create-form
        >
            @csrf
            <input type="hidden" name="scope" value="{{ $isWarehouseScope ? 'warehouse' : 'system' }}">
            <p data-role-create-error="general" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></p>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 lg:col-span-2">
                    <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-800">
                        Role Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="e.g., Operations Supervisor"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-300/70 @error('name') border-rose-400 focus:border-rose-400 focus:ring-rose-200 @enderror"
                    >
                    @error('name')
                        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    <p data-role-create-error="name" class="mt-1.5 hidden text-sm text-rose-600"></p>

                    <label for="description" class="mt-5 mb-1.5 block text-sm font-semibold text-slate-800">
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Short description of responsibilities and intended access."
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-300/70 @error('description') border-rose-400 focus:border-rose-400 focus:ring-rose-200 @enderror"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    <p data-role-create-error="description" class="mt-1.5 hidden text-sm text-rose-600"></p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-slate-800">Role Status</p>
                    <p class="mt-1 text-xs text-slate-500">Choose whether this role is immediately assignable.</p>

                    <div class="mt-4 space-y-2">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                            <input
                                type="radio"
                                name="is_active"
                                value="1"
                                {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                                class="h-4 w-4 border-slate-300 text-slate-700 focus:ring-slate-500"
                            >
                            <span>Active</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                            <input
                                type="radio"
                                name="is_active"
                                value="0"
                                {{ old('is_active') === '0' ? 'checked' : '' }}
                                class="h-4 w-4 border-slate-300 text-slate-700 focus:ring-slate-500"
                            >
                            <span>Inactive</span>
                        </label>
                    </div>
                </div>
            </div>

            @php
                $permissionModules = $permissions->keys()->values();
            @endphp

            <div
                class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5"
                x-data="{
                    openModules: @js($permissionModules),
                    allModules: @js($permissionModules),
                    toggleModules() {
                        this.openModules = this.openModules.length ? [] : [...this.allModules];
                    },
                    setAllPermissions(checked) {
                        this.$refs.permissionsRoot.querySelectorAll('.permission-checkbox').forEach((checkbox) => {
                            checkbox.checked = checked;
                        });
                    },
                    setModulePermissions(module, checked) {
                        this.$refs.permissionsRoot.querySelectorAll('.permission-checkbox').forEach((checkbox) => {
                            if (checkbox.dataset.module !== module) {
                                return;
                            }
                            checkbox.checked = checked;
                        });
                    }
                }"
            >
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-800">
                            Permissions <span class="text-rose-500">*</span>
                        </label>
                        <p class="mt-1 text-xs text-slate-500">Select modules and actions this role can perform.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                            @click="setAllPermissions(true)"
                        >
                            Select All
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                            @click="setAllPermissions(false)"
                        >
                            Deselect All
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                            @click="toggleModules()"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span x-text="openModules.length ? 'Collapse All' : 'Expand All'"></span>
                        </button>
                    </div>
                </div>

                @if($permissions->isEmpty())
                    <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        No permissions available. Please run permission seeders first.
                    </p>
                @else
                    <div class="space-y-3" x-ref="permissionsRoot">
                        @foreach($permissions as $module => $modulePermissions)
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50/80 px-4 py-3">
                                    <button
                                        type="button"
                                        @click="openModules.includes('{{ $module }}') ? openModules = openModules.filter(m => m !== '{{ $module }}') : openModules.push('{{ $module }}')"
                                        class="flex flex-1 items-center justify-between gap-2 text-left hover:text-slate-950"
                                    >
                                        <span class="text-sm font-semibold capitalize text-slate-800">
                                            {{ str_replace('_', ' ', $module) }}
                                            <span class="ml-1 text-xs font-medium text-slate-500">({{ $modulePermissions->count() }})</span>
                                        </span>
                                        <svg
                                            class="h-4 w-4 text-slate-500 transition-transform"
                                            :class="openModules.includes('{{ $module }}') ? 'rotate-180' : ''"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                            @click.stop="setModulePermissions('{{ $module }}', true)"
                                        >
                                            Select All
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                            @click.stop="setModulePermissions('{{ $module }}', false)"
                                        >
                                            Deselect All
                                        </button>
                                    </div>
                                </div>

                                <div x-show="openModules.includes('{{ $module }}')" x-cloak class="border-t border-slate-100 px-4 py-3">
                                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach($modulePermissions as $permission)
                                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission->id }}"
                                                    data-module="{{ $module }}"
                                                    {{ is_array(old('permissions')) && in_array($permission->id, old('permissions')) ? 'checked' : '' }}
                                                    class="permission-checkbox mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-700 focus:ring-slate-500"
                                                >
                                                <span class="leading-snug">
                                                    {{ $permission->label() }}
                                                    @if($permission->description)
                                                        <span class="block text-xs text-slate-500">{{ $permission->description }}</span>
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @error('permissions')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <p data-role-create-error="permissions" class="mt-2 hidden text-sm text-rose-600"></p>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ $backUrl }}"
                   class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </a>
                <button
                    type="submit"
                    data-role-create-submit
                    class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Create Role
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
