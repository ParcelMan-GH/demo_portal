export function initAdminToast() {
    const container = document.getElementById('admin-toast-container');
    if (!container) {
        return;
    }

    const typeConfig = {
        success: {
            wrapper: 'border-emerald-200/80 bg-emerald-50/95 text-emerald-900',
            iconBg: 'bg-emerald-100 text-emerald-700',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />',
        },
        error: {
            wrapper: 'border-red-200/80 bg-red-50/95 text-red-900',
            iconBg: 'bg-red-100 text-red-700',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />',
        },
        warning: {
            wrapper: 'border-amber-200/80 bg-amber-50/95 text-amber-900',
            iconBg: 'bg-amber-100 text-amber-700',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.67 18h16.66a1 1 0 00.87-1.5l-7.5-13a1 1 0 00-1.74 0z" />',
        },
        info: {
            wrapper: 'border-blue-200/80 bg-blue-50/95 text-blue-900',
            iconBg: 'bg-blue-100 text-blue-700',
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z" />',
        },
    };

    window.showToast = (message, type = 'success', timeout = 4000) => {
        if (!message) return;

        const config = typeConfig[type] || typeConfig.info;
        const toast = document.createElement('div');
        toast.className = `pointer-events-auto overflow-hidden rounded-xl border px-3 py-3 shadow-lg backdrop-blur transition-all duration-200 opacity-0 translate-y-2 ${config.wrapper}`;
        toast.innerHTML = `
            <div class="flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg ${config.iconBg}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">${config.icon}</svg>
                </span>
                <p class="flex-1 text-sm font-medium leading-5 break-words">${String(message)}</p>
                <button type="button" class="rounded-md p-1 text-current/60 hover:text-current" aria-label="Close notification">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        `;

        const removeToast = () => {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 200);
        };

        const closeBtn = toast.querySelector('button');
        if (closeBtn) closeBtn.addEventListener('click', removeToast);

        container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0', 'translate-y-2');
            toast.classList.add('opacity-100', 'translate-y-0');
        });

        window.setTimeout(removeToast, Math.max(1500, Number(timeout) || 4000));
    };

    const rawSuccess = container.dataset.flashSuccess;
    const rawError = container.dataset.flashError;

    if (rawSuccess && rawSuccess !== 'null') {
        window.showToast(rawSuccess, 'success');
    }

    if (rawError && rawError !== 'null') {
        window.showToast(rawError, 'error');
    }
}
