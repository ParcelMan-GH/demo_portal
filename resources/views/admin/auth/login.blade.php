<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - Parcelman</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/admin/modules/auth/login.js'])
</head>
<body class="admin-login-shell admin-login-grid min-h-screen antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-10 sm:px-6 lg:px-8" x-data="adminLoginPage()">
        <div class="admin-login-card w-full max-w-md rounded-3xl p-5 sm:p-6 lg:p-7">
            <div class="mb-7 text-center">
                <img src="{{ asset('logo.png') }}"
                     alt="Parcelman"
                     class="mx-auto h-auto w-full max-w-[200px] sm:max-w-[240px]">
                <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Admin Portal</p>
                <h1 class="mt-2 text-xl font-bold text-slate-900 sm:text-2xl">Sign in</h1>
                <p class="mt-1 text-sm text-slate-600">Use your admin credentials to access the dashboard.</p>
            </div>

            @if($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.67 18h16.66a1 1 0 00.87-1.5l-7.5-13a1 1 0 00-1.74 0z"/>
                            </svg>
                        </span>
                        <div class="text-sm text-rose-700">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.login') }}" method="POST" class="space-y-5" @@submit="handleSubmit">
                @csrf

                <div class="space-y-1.5">
                    <label for="email" class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-700">Email</label>
                    <div class="relative">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="admin@example.com"
                            class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 transition
                                {{ $errors->has('email') ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-200' : 'border-slate-300 focus:border-primary-400 focus:ring-primary-200' }}"
                        >
                        <svg class="pointer-events-none absolute right-3 top-2.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="password" class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-700">Password</label>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-11 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        >
                        <button
                            type="button"
                            @@click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 px-3 text-slate-500 transition hover:text-slate-800"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        >
                            <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.829M9.88 9.88A3 3 0 0114.12 14.12M6.228 6.228A9.956 9.956 0 002.458 12c1.274 4.057 5.064 7 9.542 7 1.732 0 3.36-.44 4.772-1.212M9.88 9.88L6.228 6.228m7.892 7.892l3.652 3.652"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            {{ old('remember') ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-slate-300 bg-white text-primary-500 focus:ring-primary-300"
                        >
                        Keep me signed in
                    </label>
                    <span class="text-[11px] text-slate-500">Secure access</span>
                </div>

                <button
                    type="submit"
                    :disabled="isSubmitting"
                    class="group relative inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-slate-900 to-slate-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/25 transition hover:from-slate-800 hover:to-slate-600 disabled:cursor-not-allowed disabled:opacity-70"
                >
                    <svg x-show="!isSubmitting" class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <svg x-show="isSubmitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="isSubmitting ? 'Signing in...' : 'Sign In'"></span>
                </button>
            </form>

            <div class="mt-6 border-t border-slate-200 pt-4 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} Parcelman Express. Authorized use only.
            </div>
        </div>
    </main>
</body>
</html>
