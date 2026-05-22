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
         class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">

        <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.75m12 0a5.971 5.971 0 0 0-.941-3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xl font-black text-slate-950" x-text="modalMode === 'create' ? 'Add User' : 'Edit User'"></h3>
                    <p class="mt-1 text-sm font-semibold leading-5 text-slate-500" x-text="modalMode === 'create' ? 'Create login access and assign a role.' : 'Update access, role, status, or password.'"></p>
                </div>
            </div>
            <button type="button" @@click="closeModal()" class="shrink-0 rounded-2xl border border-slate-200 p-3 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form @@submit.prevent="submitForm()" class="flex min-h-0 flex-1 flex-col">
            <div class="min-h-0 flex-1 overflow-y-auto p-5">
                <div x-show="formErrors.general" x-cloak class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 p-3">
                    <p class="text-sm font-semibold text-rose-700" x-text="formErrors.general"></p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">User Photo</label>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-orange-50 text-xl font-black text-orange-700 ring-1 ring-orange-100">
                                <template x-if="formData.photo_preview_url">
                                    <img :src="formData.photo_preview_url" alt="" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!formData.photo_preview_url">
                                    <span x-text="(formData.name || 'U').trim().charAt(0).toUpperCase() || 'U'"></span>
                                </template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <input x-ref="userPhotoInput"
                                       type="file"
                                       accept="image/*"
                                       @@change="handleUserPhoto($event)"
                                       class="block w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-orange-50 file:px-4 file:py-2 file:text-sm file:font-black file:text-orange-700 hover:file:bg-orange-100 focus:border-orange-400 focus:outline-none focus:ring-4 focus:ring-orange-100">
                                <p x-show="formErrors.profile_photo" x-text="formErrors.profile_photo" class="mt-1 text-xs font-semibold text-rose-600"></p>
                                <button type="button"
                                        x-show="formData.photo"
                                        x-cloak
                                        @@click="clearSelectedPhoto()"
                                        class="mt-2 text-xs font-black text-slate-500 hover:text-slate-800">
                                    Clear selected photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="formData.name"
                               class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                               :class="formErrors.name ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''"
                               placeholder="Enter full name">
                        <p x-show="formErrors.name" x-text="formErrors.name" class="mt-1 text-xs font-semibold text-rose-600"></p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Phone Number <span class="text-rose-500">*</span></label>
                        <input type="tel" x-model="formData.phone"
                               @@input="normalizePhoneInput()"
                               @@blur="validatePhoneInput(true)"
                               inputmode="numeric"
                               pattern="[0-9]{10}"
                               maxlength="10"
                               class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                               :class="formErrors.phone ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''"
                               placeholder="0241234567">
                        <p x-show="formErrors.phone" x-text="formErrors.phone" class="mt-1 text-xs font-semibold text-rose-600"></p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Email Address</label>
                        <input type="email" x-model="formData.email"
                               class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                               :class="formErrors.email ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''"
                               placeholder="user@example.com">
                        <p x-show="formErrors.email" x-text="formErrors.email" class="mt-1 text-xs font-semibold text-rose-600"></p>
                    </div>

                    @if($isHqUser ?? false)
                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Warehouse <span class="text-rose-500">*</span></label>
                        <select x-model="formData.warehouse_id"
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                :class="formErrors.warehouse_id ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''">
                            <option value="">Select warehouse</option>
                            @foreach($warehouses as $warehouseOption)
                                <option value="{{ $warehouseOption->id }}">{{ $warehouseOption->name }}</option>
                            @endforeach
                        </select>
                        <p x-show="formErrors.warehouse_id" x-text="formErrors.warehouse_id" class="mt-1 text-xs font-semibold text-rose-600"></p>
                    </div>
                    @endif

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Role <span class="text-rose-500">*</span></label>
                        <select x-model="formData.role_id"
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                :class="(formErrors.role_id || formErrors.roles) ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''">
                            <option value="">Select role</option>
                            @foreach($roles as $role)
                                @php
                                    $isAssignable = (bool) $role->is_assignable_by_warehouse_manager || (bool) ($canAssignRestrictedRoles ?? false);
                                @endphp
                                <option value="{{ $role->id }}"
                                        :disabled="!{{ $isAssignable ? 'true' : 'false' }} && String(formData.role_id) !== '{{ $role->id }}'">
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        <p x-show="formErrors.role_id || formErrors.roles"
                           x-text="formErrors.role_id || formErrors.roles"
                           class="mt-1 text-xs font-semibold text-rose-600"></p>
                    </div>

                    <div x-show="modalMode === 'edit' && !editingUser?.is_self" x-cloak class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Status</label>
                        <select x-model="formData.is_active"
                                class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2" x-data="{ showPassword: false, showPasswordConfirmation: false }">
                        <div x-show="modalMode === 'edit'" x-cloak class="mb-4">
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-bold text-slate-700">
                                <input type="checkbox" x-model="changePassword" class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                Change password
                            </label>
                        </div>

                        <div x-show="modalMode === 'create' || changePassword" x-cloak class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                    <span x-text="modalMode === 'create' ? 'Password' : 'New Password'"></span>
                                    <span x-show="modalMode === 'create'" class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'"
                                           x-model="formData.password"
                                           class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 pr-12 text-base font-bold text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                           :class="formErrors.password ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100' : ''"
                                           placeholder="Minimum 8 characters">
                                    <button type="button"
                                            @@click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 px-4 text-slate-500 transition hover:text-slate-800"
                                            :aria-label="showPassword ? 'Hide password' : 'Show password'">
                                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.829M9.88 9.88A3 3 0 0114.12 14.12M6.228 6.228A9.956 9.956 0 002.458 12c1.274 4.057 5.064 7 9.542 7 1.732 0 3.36-.44 4.772-1.212M9.88 9.88L6.228 6.228m7.892 7.892l3.652 3.652"/>
                                        </svg>
                                    </button>
                                </div>
                                <p x-show="formErrors.password" x-text="formErrors.password" class="mt-1 text-xs font-semibold text-rose-600"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
                                    Confirm Password <span x-show="modalMode === 'create'" class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showPasswordConfirmation ? 'text' : 'password'"
                                           x-model="formData.password_confirmation"
                                           class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 pr-12 text-base font-bold text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                           placeholder="Re-enter password">
                                    <button type="button"
                                            @@click="showPasswordConfirmation = !showPasswordConfirmation"
                                            class="absolute inset-y-0 right-0 px-4 text-slate-500 transition hover:text-slate-800"
                                            :aria-label="showPasswordConfirmation ? 'Hide password' : 'Show password'">
                                        <svg x-show="!showPasswordConfirmation" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showPasswordConfirmation" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.829M9.88 9.88A3 3 0 0114.12 14.12M6.228 6.228A9.956 9.956 0 002.458 12c1.274 4.057 5.064 7 9.542 7 1.732 0 3.36-.44 4.772-1.212M9.88 9.88L6.228 6.228m7.892 7.892l3.652 3.652"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="shrink-0 flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @@click="closeModal()" class="rounded-2xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center justify-center rounded-2xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
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
