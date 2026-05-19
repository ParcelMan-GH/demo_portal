<template x-teleport="body">
<div x-show="showModal"
     x-cloak
     class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
     @@click.self="closeModal()"
     @@keydown.escape.window="closeModal()">
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @@click.stop
         class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">

        <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
            <div class="flex min-w-0 items-start gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.75m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.803m0 0A5.971 5.971 0 0 0 6 18.75m0 0v.031m0-.031a9.094 9.094 0 0 1-3.741-.479 3 3 0 0 1 4.682-2.72M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg font-extrabold text-slate-900" x-text="modalMode === 'create' ? 'Add User' : 'Edit User'"></h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500" x-text="modalMode === 'create' ? 'Create a user and assign their role.' : 'Update user profile, role, status, or password.'"></p>
                </div>
            </div>
            <button type="button" @@click="closeModal()" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form @@submit.prevent="submitForm()" class="flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto bg-slate-50/70 p-5">
                <div x-show="formErrors.general" x-cloak class="rounded-2xl border border-rose-200 bg-rose-50 p-3">
                    <p class="text-sm font-semibold text-rose-700" x-text="formErrors.general"></p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-4">
                        <p class="text-sm font-black text-slate-900">Profile</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Basic login and contact details.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="formData.name"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                   :class="formErrors.name ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''"
                                   placeholder="John Doe">
                            <p x-show="formErrors.name" x-text="formErrors.name" class="mt-1 text-xs font-semibold text-rose-600"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" x-model="formData.email"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                   :class="formErrors.email ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''"
                                   placeholder="user@example.com">
                            <p x-show="formErrors.email" x-text="formErrors.email" class="mt-1 text-xs font-semibold text-rose-600"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Phone Number <span class="text-rose-500">*</span></label>
                            <input type="tel" x-model="formData.phone"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                   :class="formErrors.phone ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''"
                                   placeholder="0241234567">
                            <p x-show="formErrors.phone" x-text="formErrors.phone" class="mt-1 text-xs font-semibold text-rose-600"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-4">
                        <p class="text-sm font-black text-slate-900">Access</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Choose the operational role this user should have.</p>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($roles as $role)
                        @php
                            $isAssignable = (bool) $role->is_assignable_by_warehouse_manager || (bool) ($canAssignRestrictedRoles ?? false);
                        @endphp
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-3 transition hover:border-orange-200 hover:bg-orange-50/60"
                               :class="[
                                   String(formData.role_id) === '{{ $role->id }}' ? 'border-orange-300 bg-orange-50 ring-1 ring-orange-200' : '',
                                   !{{ $isAssignable ? 'true' : 'false' }} && String(formData.role_id) !== '{{ $role->id }}' ? 'opacity-60 cursor-not-allowed' : ''
                               ]">
                            <input type="radio"
                                   name="modal_role_id"
                                   value="{{ $role->id }}"
                                   x-model="formData.role_id"
                                   :disabled="!{{ $isAssignable ? 'true' : 'false' }} && String(formData.role_id) !== '{{ $role->id }}'"
                                   class="h-4 w-4 border-slate-300 text-orange-600 focus:ring-orange-500">
                            <span class="text-sm font-bold text-slate-800">{{ $role->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    <p x-show="formErrors.role_id || formErrors.roles"
                       x-text="formErrors.role_id || formErrors.roles"
                       class="mt-2 text-xs font-semibold text-rose-600"></p>

                    <div x-show="modalMode === 'edit' && !editingUser?.is_self" x-cloak class="mt-5 border-t border-slate-100 pt-4">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Status</label>
                        <div class="flex flex-wrap gap-3">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700">
                                <input type="radio" x-model="formData.is_active" value="1" class="h-4 w-4 border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                Active
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700">
                                <input type="radio" x-model="formData.is_active" value="0" class="h-4 w-4 border-slate-300 text-slate-600 focus:ring-slate-500">
                                Inactive
                            </label>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" x-data="{ showPassword: false }">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-black text-slate-900">Password</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500" x-text="modalMode === 'create' ? 'Set the first login password.' : 'Leave unchanged unless the password must be reset.'"></p>
                        </div>
                        <label x-show="modalMode === 'edit'" x-cloak class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700">
                            <input type="checkbox" x-model="changePassword" class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                            Change Password
                        </label>
                    </div>

                    <div x-show="modalMode === 'create' || changePassword" x-cloak class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                <span x-text="modalMode === 'create' ? 'Password' : 'New Password'"></span>
                                <span x-show="modalMode === 'create'" class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'"
                                       x-model="formData.password"
                                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 pr-10 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                       :class="formErrors.password ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''"
                                       placeholder="Minimum 8 characters">
                                <button type="button" @@click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-700">
                                    <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/>
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 3 18 18M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58M9.88 4.24A9.9 9.9 0 0 1 12 4c4.48 0 8.27 2.94 9.54 7a10.2 10.2 0 0 1-3.02 4.54M6.53 6.53A10.3 10.3 0 0 0 2.46 11c1.27 4.06 5.06 7 9.54 7 1.42 0 2.76-.3 3.98-.84"/>
                                    </svg>
                                </button>
                            </div>
                            <p x-show="formErrors.password" x-text="formErrors.password" class="mt-1 text-xs font-semibold text-rose-600"></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                Confirm Password <span x-show="modalMode === 'create'" class="text-rose-500">*</span>
                            </label>
                            <input :type="showPassword ? 'text' : 'password'"
                                   x-model="formData.password_confirmation"
                                   class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                                   placeholder="Re-enter password">
                        </div>
                    </div>
                </div>
            </div>

            <div class="shrink-0 flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @@click="closeModal()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center justify-center rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-show="!submitting" x-text="modalMode === 'create' ? 'Create User' : 'Update User'"></span>
                    <span x-show="submitting" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
</template>
