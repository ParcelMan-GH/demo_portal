@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $formAction = $isEdit ? route('admin.roles.update', $role) : route('admin.roles.store');
    $submitLabel = $isEdit ? 'Update Role' : 'Create Role';
    $formErrorPrefix = $isEdit ? 'role-edit' : 'role-create';
    $selectedPermissions = $selectedPermissions ?? [];
    $roleName = old('name', $isEdit ? $role->name : '');
    $roleDescription = old('description', $isEdit ? $role->description : '');
    $roleIsActive = old('is_active', (string) (int) ($isEdit ? $role->is_active : true));
    $roleIsAssignable = old('is_assignable_by_warehouse_manager', (string) (int) ($isEdit ? $role->is_assignable_by_warehouse_manager : true));
    $permissionModules = $permissions->keys()->values();
    $localPermissionGroups = $permissions->filter(fn ($modulePermissions) => $modulePermissions->every(
        fn ($permission) => in_array($permission->module, ['dashboard', 'warehouse'], true)
    ));
    $adminPermissionGroups = $permissions->diffKeys($localPermissionGroups);
    $permissionSections = [
        [
            'title' => 'Local Operations',
            'description' => 'Usable by the user inside their own warehouse when the role grants the permission.',
            'badge' => 'Role permission only',
            'groups' => $localPermissionGroups,
            'tone' => 'local',
        ],
        [
            'title' => 'Admin Modules',
            'description' => 'Requires this role permission plus HQ status or an explicit warehouse capability grant.',
            'badge' => 'Capability required',
            'groups' => $adminPermissionGroups,
            'tone' => 'admin',
        ],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-5">
    <a href="{{ $backUrl }}"
       class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        <span>{{ $isEdit ? 'Back to Role Details' : 'Back to Roles' }}</span>
    </a>

    <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-slate-300/30">
        <div class="border-b border-slate-200 bg-white px-5 py-5 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/25">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 4v5c0 4.5-2.9 7.9-7 9-4.1-1.1-7-4.5-7-9V7l7-4z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.5 12.5l1.7 1.7 3.7-4.1"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-[0.26em] text-orange-600">Role Template</p>
                        <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">{{ $isEdit ? 'Edit Role' : 'Create Role' }}</h1>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-slate-500">
                            Configure a reusable role. The warehouse the user belongs to decides whether these permissions apply locally or with HQ-level reach.
                        </p>
                    </div>
                </div>
                <div class="rounded-2xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm font-bold text-orange-800">
                    {{ $permissions->flatten(1)->count() }} permissions available
                </div>
            </div>
        </div>

        <form
            action="{{ $formAction }}"
            method="POST"
            class="space-y-0"
            data-{{ $formErrorPrefix }}-form
        >
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="scope" value="warehouse">

            <div class="grid gap-0 lg:grid-cols-[minmax(320px,420px)_1fr]">
                <aside class="border-b border-slate-200 bg-slate-50/70 p-5 lg:border-b-0 lg:border-r lg:p-8">
                    <p data-{{ $formErrorPrefix }}-error="general" class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700"></p>

                    <div class="space-y-5">
                        <div>
                            <label for="name" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600">
                                Role Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ $roleName }}"
                                required
                                placeholder="Operations Supervisor"
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-black text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 @error('name') border-rose-400 focus:border-rose-400 focus:ring-rose-100 @enderror"
                            >
                            @error('name')
                                <p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                            <p data-{{ $formErrorPrefix }}-error="name" class="mt-1.5 hidden text-sm font-semibold text-rose-600"></p>
                        </div>

                        <div>
                            <label for="description" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                placeholder="Short responsibility summary."
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-sm font-semibold leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 @error('description') border-rose-400 focus:border-rose-400 focus:ring-rose-100 @enderror"
                            >{{ $roleDescription }}</textarea>
                            @error('description')
                                <p class="mt-1.5 text-sm font-semibold text-rose-600">{{ $message }}</p>
                            @enderror
                            <p data-{{ $formErrorPrefix }}-error="description" class="mt-1.5 hidden text-sm font-semibold text-rose-600"></p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <label for="is_active" class="mb-2 block text-sm font-black text-slate-950">Status</label>
                            <select
                                id="is_active"
                                name="is_active"
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-black text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                            >
                                <option value="1" {{ $roleIsActive === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ $roleIsActive === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-black text-slate-950">Assignment</p>
                            <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">Controls whether non-HQ warehouse managers can assign this role to their own users.</p>
                            <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-xl border-2 border-slate-200 bg-slate-50 px-3 py-3 transition hover:border-orange-200 hover:bg-orange-50/60">
                                <input type="checkbox" name="is_assignable_by_warehouse_manager" value="1" {{ $roleIsAssignable === '1' ? 'checked' : '' }} class="mt-1 h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                <span>
                                    <span class="block text-sm font-black text-slate-900">Warehouse assignable</span>
                                    <span class="block text-xs font-semibold leading-5 text-slate-500">Admin module permissions still need HQ status or a warehouse capability grant.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </aside>

                <section
                    class="p-5 lg:p-8"
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
                                if (checkbox.dataset.module !== module) return;
                                checkbox.checked = checked;
                            });
                        }
                    }"
                >
                    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div class="min-w-0">
                            <label class="block text-lg font-black text-slate-950">
                                Permissions <span class="text-rose-500">*</span>
                            </label>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Local permissions work inside the user's warehouse. Admin modules also require HQ status or a capability grant.</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-2 sm:flex-nowrap lg:justify-end">
                            <button type="button" class="whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50" @click="setAllPermissions(true)">Select All</button>
                            <button type="button" class="whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50" @click="setAllPermissions(false)">Deselect All</button>
                            <button type="button" class="whitespace-nowrap rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-black text-orange-700 shadow-sm transition hover:bg-orange-100" @click="toggleModules()">
                                <span x-text="openModules.length ? 'Collapse All' : 'Expand All'"></span>
                            </button>
                        </div>
                    </div>

                    @if($permissions->isEmpty())
                        <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">
                            No permissions available. Please run permission seeders first.
                        </p>
                    @else
                        <div class="grid gap-6" x-ref="permissionsRoot">
                            @foreach($permissionSections as $section)
                                @continue($section['groups']->isEmpty())
                                <div class="rounded-[1.5rem] border {{ $section['tone'] === 'admin' ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200 bg-slate-50/50' }} p-3 sm:p-4">
                                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="text-sm font-black uppercase tracking-wide {{ $section['tone'] === 'admin' ? 'text-amber-700' : 'text-slate-700' }}">{{ $section['title'] }}</p>
                                            <p class="mt-1 max-w-3xl text-xs font-semibold leading-5 text-slate-500">{{ $section['description'] }}</p>
                                        </div>
                                        <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-black {{ $section['tone'] === 'admin' ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-200' : 'bg-white text-slate-700 ring-1 ring-slate-200' }}">{{ $section['badge'] }}</span>
                                    </div>

                                    <div class="grid gap-4">
                                        @foreach($section['groups'] as $module => $modulePermissions)
                                            @php
                                                $moduleKey = \Illuminate\Support\Str::slug($module);
                                            @endphp
                                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                                <div class="flex flex-col gap-3 border-b border-slate-100 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                                                    <button type="button" @click="openModules.includes(@js($module)) ? openModules = openModules.filter(m => m !== @js($module)) : openModules.push(@js($module))" class="flex min-w-0 flex-1 items-center gap-3 text-left">
                                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $section['tone'] === 'admin' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' : 'bg-orange-50 text-orange-700 ring-1 ring-orange-100' }} text-sm font-black">{{ strtoupper(substr($module, 0, 1)) }}</span>
                                                        <span class="min-w-0">
                                                            <span class="block text-base font-black text-slate-950">{{ $module }}</span>
                                                            <span class="block text-xs font-bold text-slate-500">{{ $modulePermissions->count() }} permissions</span>
                                                        </span>
                                                        <svg class="ml-auto h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="openModules.includes(@js($module)) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                        </svg>
                                                    </button>
                                                    <div class="flex shrink-0 items-center gap-2">
                                                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-100" @click.stop="setModulePermissions(@js($moduleKey), true)">Select</button>
                                                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-100" @click.stop="setModulePermissions(@js($moduleKey), false)">Clear</button>
                                                    </div>
                                                </div>

                                                <div x-show="openModules.includes(@js($module))" x-cloak class="p-4">
                                                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                        @foreach($modulePermissions as $permission)
                                                            <label class="group flex min-h-[92px] cursor-pointer items-start gap-3 rounded-2xl border-2 border-slate-200 bg-white p-4 transition hover:border-orange-200 hover:bg-orange-50/40">
                                                                <input
                                                                    type="checkbox"
                                                                    name="permissions[]"
                                                                    value="{{ $permission->id }}"
                                                                    data-module="{{ $moduleKey }}"
                                                                    {{ in_array($permission->id, $selectedPermissions, true) ? 'checked' : '' }}
                                                                    class="permission-checkbox mt-1 h-5 w-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                                                                >
                                                                <span class="min-w-0">
                                                                    <span class="block text-sm font-black leading-5 text-slate-950">{{ $permission->displayLabel() }}</span>
                                                                    @if($permission->displayDescription())
                                                                        <span class="mt-1 block text-xs font-semibold leading-5 text-slate-500">{{ $permission->displayDescription() }}</span>
                                                                    @endif
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('permissions')
                        <p class="mt-2 text-sm font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                    <p data-{{ $formErrorPrefix }}-error="permissions" class="mt-2 hidden text-sm font-semibold text-rose-600"></p>
                </section>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-5 sm:flex-row sm:items-center sm:justify-end lg:px-8">
                <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-2xl border-2 border-slate-200 bg-white px-6 py-4 text-sm font-black text-slate-700 transition hover:bg-slate-50">Cancel</a>
                <button type="submit" data-{{ $formErrorPrefix }}-submit class="inline-flex items-center justify-center rounded-2xl bg-orange-600 px-7 py-4 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                    {{ $submitLabel }}
                </button>
            </div>
        </form>
    </div>
</div>
