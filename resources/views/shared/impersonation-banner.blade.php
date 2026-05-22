@if(!empty($backOfficeImpersonator) && Auth::guard('admin')->check())
    <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 text-amber-950 shadow-sm">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-black">
                        Impersonating {{ Auth::guard('admin')->user()?->name }}
                    </p>
                    <p class="truncate text-xs font-semibold text-amber-800">
                        Original account: {{ $backOfficeImpersonator->name }}. Actions are audited.
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.impersonation.stop') }}" class="shrink-0">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-black text-amber-900 shadow-sm transition hover:bg-amber-100 sm:w-auto">
                    Return to my account
                </button>
            </form>
        </div>
    </div>
@endif
