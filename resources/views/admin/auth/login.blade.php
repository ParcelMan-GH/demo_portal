<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Sign in - ParcelMan Express</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300..800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/access/sign-in.js'])
</head>
<body class="min-h-screen antialiased text-slate-900 bg-white font-sans">
    
    <main class="flex min-h-screen w-full" x-data="accessSignInPage()">
        
        {{-- Left Side: Image Background (Hidden on Mobile, 50% width on Desktop) --}}
        {{-- Make sure to replace 'images/login-rider-bg.jpg' with the actual path to your rider image --}}
        <section class="hidden lg:block lg:w-1/2 relative bg-slate-100 bg-cover bg-center bg-no-repeat" 
                 style="background-image: url('{{ asset('images/login-rider-bg.jpg') }}');">
            {{-- Optional: Soft inner shadow or overlay if you want to darken the edges --}}
            <div class="absolute inset-0 bg-gradient-to-r from-transparent to-black/5 pointer-events-none"></div>
        </section>

        {{-- Right Side: Form Container --}}
        <section class="flex flex-col items-center justify-center w-full lg:w-1/2 px-6 py-12 lg:px-16 xl:px-24 bg-white relative">
            
            <div class="w-full max-w-sm flex flex-col items-center">
                
                {{-- Logo & Headers --}}
                <div class="mb-10 text-center w-full">
                    <img src="{{ asset('logo.png') }}" 
                         alt="ParcelMan Express" 
                         class="mx-auto h-20 w-auto mb-8 object-contain">
                         
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Welcome back</h1>
                    <p class="mt-2 text-sm text-slate-500 font-medium">Enter your credentials to continue.</p>
                </div>

                {{-- Error Messages --}}
                @if($errors->any())
                    <div class="mb-6 w-full rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.67 18h16.66a1 1 0 00.87-1.5l-7.5-13a1 1 0 00-1.74 0z"/>
                                </svg>
                            </span>
                            <div class="text-sm font-semibold text-rose-700">
                                @foreach($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Login Form --}}
                <form method="POST" action="{{ route('admin.login') }}" class="w-full space-y-5" @submit="handleSubmit">
                    @csrf

                    {{-- Email Field --}}
                    <div class="space-y-1.5">
                        <label for="email" class="block text-[11px] font-extrabold uppercase tracking-widest text-slate-500">Email or Phone</label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus 
                                autocomplete="username" 
                                placeholder="Enter email or phone number" 
                                class="w-full rounded-xl border border-slate-200 bg-white py-3.5 pl-4 pr-11 text-sm font-semibold text-slate-900 placeholder:text-slate-400 placeholder:font-medium focus:border-[#E2762B] focus:outline-none focus:ring-4 focus:ring-orange-500/10 transition-all {{ $errors->has('email') ? 'border-rose-300' : '' }}"
                            >
                            {{-- Envelope Icon --}}
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Password Field --}}
                    <div class="space-y-1.5">
                        <label for="password" class="block text-[11px] font-extrabold uppercase tracking-widest text-slate-500">Password</label>
                        <div class="relative">
                            <input 
                                :type="showPassword ? 'text' : 'password'" 
                                id="password" 
                                name="password" 
                                required 
                                autocomplete="current-password" 
                                placeholder="Enter your password" 
                                class="w-full rounded-xl border border-slate-200 bg-white py-3.5 pl-4 pr-11 text-sm font-semibold text-slate-900 placeholder:text-slate-400 placeholder:font-medium focus:border-[#E2762B] focus:outline-none focus:ring-4 focus:ring-orange-500/10 transition-all"
                            >
                            {{-- Eye / Eye-off Icon --}}
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-slate-700 transition-colors focus:outline-none"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            >
                                <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.829M9.88 9.88A3 3 0 0114.12 14.12M6.228 6.228A9.956 9.956 0 002.458 12c1.274 4.057 5.064 7 9.542 7 1.732 0 3.36-.44 4.772-1.212M9.88 9.88L6.228 6.228m7.892 7.892l3.652 3.652"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Bottom Controls (Remember Me & Encrypted) --}}
                    <div class="flex items-center justify-between pt-1 pb-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer group">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                value="1" 
                                {{ old('remember') ? 'checked' : '' }} 
                                class="h-4 w-4 rounded border-slate-300 text-[#E2762B] focus:ring-[#E2762B] cursor-pointer"
                            >
                            <span class="text-[13px] font-semibold text-slate-600 group-hover:text-slate-900 transition-colors">Keep me signed in</span>
                        </label>
                        <span class="text-[12px] font-medium text-slate-400">Encrypted session</span>
                    </div>

                    {{-- Submit Button --}}
                    <button 
                        type="submit" 
                        :disabled="isSubmitting" 
                        class="group relative flex w-full items-center justify-center gap-2 rounded-xl bg-[#E2762B] px-4 py-3.5 text-[15px] font-bold text-white shadow-[0_8px_20px_rgba(226,118,43,0.25)] transition-all hover:bg-[#d1651d] hover:shadow-[0_8px_20px_rgba(226,118,43,0.4)] disabled:cursor-not-allowed disabled:opacity-70 active:scale-[0.98]"
                    >
                        <span x-text="isSubmitting ? 'Signing in...' : 'Continue'"></span>
                        
                        {{-- Loading Spinner --}}
                        <svg x-show="isSubmitting" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>

                        {{-- Right Arrow --}}
                        <svg x-show="!isSubmitting" class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>

                {{-- Footer --}}
                <div class="mt-14 w-full text-center text-xs font-semibold text-slate-400">
                    &copy; {{ date('Y') }} Parcelman Express. Authorized use only.
                </div>
                
            </div>
        </section>
        
    </main>
</body>
</html>