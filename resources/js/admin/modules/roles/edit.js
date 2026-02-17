function initRoleEditForm() {
    const form = document.querySelector('[data-role-edit-form]');
    if (!form) {
        return;
    }

    const submitButton = form.querySelector('[data-role-edit-submit]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const errorTargets = {
        general: form.querySelector('[data-role-edit-error="general"]'),
        name: form.querySelector('[data-role-edit-error="name"]'),
        description: form.querySelector('[data-role-edit-error="description"]'),
        permissions: form.querySelector('[data-role-edit-error="permissions"]'),
    };

    const clearErrors = () => {
        Object.values(errorTargets).forEach((target) => {
            if (!target) return;
            target.textContent = '';
            target.classList.add('hidden');
        });
    };

    const setError = (key, message) => {
        const target = errorTargets[key] || errorTargets.general;
        if (!target || !message) {
            return;
        }

        target.textContent = String(message);
        target.classList.remove('hidden');
    };

    const setSubmittingState = (isSubmitting) => {
        if (!submitButton) {
            return;
        }

        submitButton.disabled = isSubmitting;
        submitButton.classList.toggle('opacity-70', isSubmitting);
        submitButton.classList.toggle('cursor-not-allowed', isSubmitting);
        submitButton.textContent = isSubmitting ? 'Updating...' : 'Update Role';
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        setSubmittingState(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 422 && result.errors) {
                    Object.entries(result.errors).forEach(([key, messages]) => {
                        const message = Array.isArray(messages) ? messages[0] : messages;
                        setError(key, message);
                    });

                    const firstError = Object.values(result.errors)[0];
                    const firstMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                    if (window.showToast && firstMessage) {
                        window.showToast(firstMessage, 'error');
                    }
                    return;
                }

                const message = result.message || 'Failed to update role.';
                setError('general', message);
                if (window.showToast) {
                    window.showToast(message, 'error');
                }
                return;
            }

            const message = result.message || 'Role updated successfully.';
            if (window.showToast) {
                window.showToast(message, 'success');
            }

            const redirectUrl = result?.data?.redirect_url;
            if (redirectUrl) {
                window.location.assign(redirectUrl);
            }
        } catch (error) {
            console.error('Role update request failed:', error);
            const message = 'An unexpected error occurred while updating the role.';
            setError('general', message);
            if (window.showToast) {
                window.showToast(message, 'error');
            }
        } finally {
            setSubmittingState(false);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRoleEditForm);
} else {
    initRoleEditForm();
}
