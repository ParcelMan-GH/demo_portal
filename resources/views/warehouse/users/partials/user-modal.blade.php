<!-- User Modal (Create/Edit) -->
<template x-teleport="body">
<div x-show="showModal"
     x-cloak
     class="fixed inset-0 z-[100] overflow-y-auto"
     @@keydown.escape.window="closeModal()">
    <!-- Backdrop -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
         @@click="closeModal()"></div>

    <!-- Modal Content -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @@click.stop
             class="relative w-full max-w-lg bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200/50">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900" x-text="modalMode === 'create' ? 'Add New User' : 'Edit User'"></h3>
                    <p class="text-sm text-slate-500" x-text="modalMode === 'create' ? 'Create a new admin user' : 'Update user information'"></p>
                </div>
                <button @@click="closeModal()" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <form @@submit.prevent="submitForm()" class="p-6 space-y-4">
                <!-- Error Alert -->
                <div x-show="formErrors.general" x-cloak class="p-3 rounded-xl bg-red-50 border border-red-200">
                    <p class="text-sm text-red-600" x-text="formErrors.general"></p>
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           x-model="formData.name"
                           class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                           :class="formErrors.name ? 'border-red-300 focus:ring-red-400/50' : ''"
                           placeholder="John Doe">
                    <p x-show="formErrors.name" x-text="formErrors.name" class="mt-1 text-xs text-red-500"></p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email"
                           x-model="formData.email"
                           class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                           :class="formErrors.email ? 'border-red-300 focus:ring-red-400/50' : ''"
                           placeholder="admin@example.com">
                    <p x-show="formErrors.email" x-text="formErrors.email" class="mt-1 text-xs text-red-500"></p>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel"
                           x-model="formData.phone"
                           class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                           :class="formErrors.phone ? 'border-red-300 focus:ring-red-400/50' : ''"
                           placeholder="0241234567">
                    <p x-show="formErrors.phone" x-text="formErrors.phone" class="mt-1 text-xs text-red-500"></p>
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Assign Role</label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border border-slate-200/70 rounded-xl p-3 bg-white/50">
                        @foreach($roles as $role)
                        @php
                            $isAssignable = (bool) $role->is_assignable_by_warehouse_manager;
                        @endphp
                        <label class="flex items-center cursor-pointer" :class="!{{ $isAssignable ? 'true' : 'false' }} && String(formData.role_id) !== '{{ $role->id }}' ? 'opacity-60 cursor-not-allowed' : ''">
                            <input type="radio"
                                   name="modal_role_id"
                                   value="{{ $role->id }}"
                                   x-model="formData.role_id"
                                   :disabled="!{{ $isAssignable ? 'true' : 'false' }} && String(formData.role_id) !== '{{ $role->id }}'"
                                   class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-slate-300">
                            <span class="ml-2 text-sm text-slate-700">{{ $role->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    <p x-show="formErrors.role_id || formErrors.roles"
                       x-text="formErrors.role_id || formErrors.roles"
                       class="mt-1 text-xs text-red-500"></p>
                </div>

                <!-- Status (Edit mode only, not for self) -->
                <div x-show="modalMode === 'edit' && !editingUser?.is_self" x-cloak>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" x-model="formData.is_active" value="1" class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-slate-300">
                            <span class="ml-2 text-sm text-slate-700">Active</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" x-model="formData.is_active" value="0" class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-slate-300">
                            <span class="ml-2 text-sm text-slate-700">Inactive</span>
                        </label>
                    </div>
                </div>

                <!-- Password Section -->
                <div x-data="{ showPassword: false }">
                    <!-- Change Password Toggle (Edit mode) -->
                    <div x-show="modalMode === 'edit'" x-cloak class="mb-3">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" x-model="changePassword" class="h-4 w-4 text-slate-600 focus:ring-slate-500 border-slate-300 rounded">
                            <span class="ml-2 text-sm text-slate-700">Change Password</span>
                        </label>
                    </div>

                    <!-- Password Fields -->
                    <div x-show="modalMode === 'create' || changePassword" x-cloak class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                <span x-text="modalMode === 'create' ? 'Password' : 'New Password'"></span>
                                <span x-show="modalMode === 'create'" class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'"
                                       x-model="formData.password"
                                       class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                                       :class="formErrors.password ? 'border-red-300 focus:ring-red-400/50' : ''"
                                       placeholder="Minimum 8 characters">
                                <button type="button" @@click="showPassword = !showPassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            <p x-show="formErrors.password" x-text="formErrors.password" class="mt-1 text-xs text-red-500"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Confirm Password <span x-show="modalMode === 'create'" class="text-red-500">*</span>
                            </label>
                            <input :type="showPassword ? 'text' : 'password'"
                                   x-model="formData.password_confirmation"
                                   class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                                   placeholder="Re-enter password">
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/50">
                    <button type="button" @@click="closeModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="submitting"
                            class="px-4 py-2 text-sm font-semibold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!submitting" x-text="modalMode === 'create' ? 'Create User' : 'Update User'"></span>
                        <span x-show="submitting" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</template>
