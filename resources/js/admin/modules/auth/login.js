import Alpine from 'alpinejs';
import '../../../../css/pages/admin-login.css';

window.Alpine = Alpine;

const register = () => {
    Alpine.data('adminLoginPage', () => ({
        showPassword: false,
        isSubmitting: false,
        handleSubmit(event) {
            if (this.isSubmitting) {
                event.preventDefault();
                return;
            }

            this.isSubmitting = true;
        },
    }));
};

if (window.Alpine) {
    register();
}

Alpine.start();

