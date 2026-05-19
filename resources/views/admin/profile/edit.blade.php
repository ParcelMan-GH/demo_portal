@extends('admin.layouts.app')

@section('title', 'My Profile')
@section('breadcrumb-parent', 'Profile')

@section('content')
<div class="max-w-3xl space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-[20px] font-bold text-slate-800">My Profile</h1>
        <p class="text-[13px] text-slate-500 mt-0.5">Manage your account information and password.</p>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200/60 rounded-2xl px-4 py-3 flex items-center gap-3">
        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <p class="text-[13px] text-emerald-700 font-medium">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Profile Info Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-[14px] font-semibold text-slate-800">Account Information</h2>
            <p class="text-[12px] text-slate-500 mt-0.5">Update your name and email address.</p>
        </div>
        <form action="{{ route('admin.profile.update') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf @method('PUT')

            {{-- Avatar + role badge --}}
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-[15px] font-bold text-slate-800">{{ $admin->name }}</p>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 mt-1 bg-slate-100 text-slate-600 text-[11px] font-semibold rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        {{ $admin->roles->first()?->name ?? ($admin->isHqUser() ? 'Administrator' : 'Staff') }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $admin->name) }}"
                           class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors @error('name') border-rose-400 @enderror">
                    @error('name')<p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $admin->email) }}"
                           class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors @error('email') border-rose-400 @enderror">
                    @error('email')<p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Change Password Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-[14px] font-semibold text-slate-800">Change Password</h2>
            <p class="text-[12px] text-slate-500 mt-0.5">Choose a strong password. Minimum 8 characters.</p>
        </div>
        <form action="{{ route('admin.profile.change-password') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf

            <div>
                <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">Current Password <span class="text-rose-500">*</span></label>
                <input type="password" name="current_password"
                       class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors @error('current_password') border-rose-400 @enderror"
                       placeholder="Enter current password">
                @error('current_password')<p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">New Password <span class="text-rose-500">*</span></label>
                    <input type="password" name="password"
                           class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors @error('password') border-rose-400 @enderror"
                           placeholder="New password">
                    @error('password')<p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-slate-600 mb-1.5">Confirm New Password <span class="text-rose-500">*</span></label>
                    <input type="password" name="password_confirmation"
                           class="w-full px-3.5 py-2.5 border border-slate-200/70 rounded-xl bg-white/70 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors"
                           placeholder="Confirm new password">
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-5 py-2.5 bg-rose-600 text-white text-[13px] font-semibold rounded-xl hover:bg-rose-700 transition-colors">
                    Change Password
                </button>
            </div>
        </form>
    </div>

    {{-- Roles & Permissions (read-only) --}}
    @if($admin->roles->count())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-[14px] font-semibold text-slate-800">Roles & Permissions</h2>
            <p class="text-[12px] text-slate-500 mt-0.5">Your assigned role and permissions are managed by your warehouse administrator.</p>
        </div>
        <div class="px-6 py-5">
            @foreach($admin->roles as $role)
            <div class="mb-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="px-3 py-1 bg-slate-900 text-white text-[12px] font-semibold rounded-full">{{ $role->name }}</span>
                    @if($role->description)
                    <span class="text-[12px] text-slate-400">{{ $role->description }}</span>
                    @endif
                </div>
                @if($role->permissions->count())
                <div class="flex flex-wrap gap-1.5">
                    @foreach($role->permissions->take(20) as $permission)
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-medium rounded-md">{{ $permission->name }}</span>
                    @endforeach
                    @if($role->permissions->count() > 20)
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-400 text-[10px] font-medium rounded-md">+{{ $role->permissions->count() - 20 }} more</span>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
