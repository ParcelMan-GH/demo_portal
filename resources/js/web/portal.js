import {
    apiRequest,
    clearToken,
    getToken,
    setToken,
} from './auth-client';
import './vendor-shipments';
import './vendor-invoices';
import './driver-pickups';

function redirectTo(url) {
    window.location.href = url;
}

function normalizePhone(value) {
    let cleaned = String(value || '').replace(/[^\d+]/g, '');

    // Keep "+" only as first character.
    if (cleaned.includes('+')) {
        cleaned = `${cleaned[0] === '+' ? '+' : ''}${cleaned.replace(/\+/g, '')}`;
    }

    return cleaned.slice(0, 13);
}

function isValidGhanaPhone(value) {
    return /^(\+233\d{9}|233\d{9}|0\d{9})$/.test(value);
}

function registerVendorAuthPage() {
    return {
        step: 'phone',
        phone: '',
        verifiedPhone: '',
        otp: '',
        registerForm: {
            name: '',
            business_name: '',
            email: '',
        },
        loading: false,
        alert: null,
        cooldownRemaining: 0,
        cooldownHandle: null,
        otpExpiresIn: 300,

        init() {
            this.checkExistingSession();
        },

        get canResendOtp() {
            return this.cooldownRemaining <= 0 && !this.loading;
        },

        showAlert(type, message) {
            this.alert = { type, message };
        },

        clearAlert() {
            this.alert = null;
        },

        setStep(step) {
            this.step = step;
            this.clearAlert();
        },

        startCooldown(seconds) {
            if (this.cooldownHandle) {
                window.clearInterval(this.cooldownHandle);
            }

            this.cooldownRemaining = Math.max(0, Number(seconds) || 0);
            if (this.cooldownRemaining === 0) {
                return;
            }

            this.cooldownHandle = window.setInterval(() => {
                if (this.cooldownRemaining <= 1) {
                    window.clearInterval(this.cooldownHandle);
                    this.cooldownHandle = null;
                    this.cooldownRemaining = 0;
                    return;
                }

                this.cooldownRemaining -= 1;
            }, 1000);
        },

        formatCooldown() {
            if (this.cooldownRemaining <= 0) {
                return 'Resend OTP';
            }

            const minutes = Math.floor(this.cooldownRemaining / 60);
            const seconds = this.cooldownRemaining % 60;
            return `Resend in ${minutes}:${String(seconds).padStart(2, '0')}`;
        },

        onPhoneInput(event) {
            this.phone = normalizePhone(event.target.value);
        },

        onOtpInput(event) {
            this.otp = String(event.target.value || '').replace(/\D/g, '').slice(0, 6);
        },

        async checkExistingSession() {
            const token = getToken('vendor');
            if (!token) {
                return;
            }

            const result = await apiRequest('/api/v1/vendor/profile', { role: 'vendor' });
            if (result.success) {
                redirectTo('/vendor/home');
                return;
            }

            clearToken('vendor');
        },

        async sendOtp() {
            const normalizedPhone = normalizePhone(this.phone);
            this.phone = normalizedPhone;

            if (!normalizedPhone) {
                this.showAlert('error', 'Phone number is required.');
                return;
            }

            if (!isValidGhanaPhone(normalizedPhone)) {
                this.showAlert('error', 'Enter a valid Ghana phone number (e.g. 0241234567 or +233241234567).');
                return;
            }

            this.loading = true;
            this.clearAlert();

            try {
                const result = await apiRequest('/api/v1/auth/vendor/send-otp', {
                    method: 'POST',
                    data: { phone: normalizedPhone },
                });

                const retryAfter = Number(result.payload?.data?.retry_after || 0);
                if (retryAfter > 0) {
                    this.startCooldown(retryAfter);
                }

                if (result.success) {
                    this.verifiedPhone = normalizedPhone;
                    this.step = 'otp';
                    this.otpExpiresIn = Number(result.payload?.data?.expires_in || 300);
                    this.showAlert('success', result.message);
                    return;
                }

                this.showAlert('error', result.message);
            } catch {
                this.showAlert('error', 'Unable to send OTP right now.');
            } finally {
                this.loading = false;
            }
        },

        async resendOtp() {
            if (!this.canResendOtp) {
                return;
            }

            await this.sendOtp();
        },

        async verifyOtp() {
            const phone = normalizePhone(this.verifiedPhone || this.phone);
            this.phone = phone;
            if (!phone) {
                this.showAlert('error', 'Phone number is required.');
                this.step = 'phone';
                return;
            }

            if (!isValidGhanaPhone(phone)) {
                this.showAlert('error', 'Enter a valid Ghana phone number (e.g. 0241234567 or +233241234567).');
                this.step = 'phone';
                return;
            }

            this.otp = String(this.otp || '').replace(/\D/g, '').slice(0, 6);
            if (this.otp.length !== 6) {
                this.showAlert('error', 'OTP must be 6 digits.');
                return;
            }

            this.loading = true;
            this.clearAlert();

            try {
                const result = await apiRequest('/api/v1/auth/vendor/verify-phone', {
                    method: 'POST',
                    data: {
                        phone,
                        otp: this.otp,
                    },
                });

                if (!result.success) {
                    this.showAlert('error', result.message);
                    return;
                }

                const authData = result.payload?.data || {};
                if (authData.user_exists === true && authData.token) {
                    setToken('vendor', authData.token);
                    redirectTo('/vendor/home');
                    return;
                }

                this.verifiedPhone = phone;
                this.setStep('register');
                this.showAlert('info', result.message);
            } catch {
                this.showAlert('error', 'Unable to verify OTP right now.');
            } finally {
                this.loading = false;
            }
        },

        backToPhone() {
            this.otp = '';
            this.verifiedPhone = '';
            this.setStep('phone');
        },

        async registerVendor() {
            const phone = normalizePhone(this.verifiedPhone || this.phone);
            if (!phone) {
                this.showAlert('error', 'Phone verification is required.');
                this.step = 'phone';
                return;
            }

            if (!isValidGhanaPhone(phone)) {
                this.showAlert('error', 'Phone verification is invalid. Please restart verification.');
                this.step = 'phone';
                return;
            }

            if (!this.registerForm.name.trim()) {
                this.showAlert('error', 'Name is required.');
                return;
            }

            this.loading = true;
            this.clearAlert();

            try {
                const result = await apiRequest('/api/v1/auth/vendor/register', {
                    method: 'POST',
                    data: {
                        name: this.registerForm.name.trim(),
                        business_name: this.registerForm.business_name.trim() || null,
                        email: this.registerForm.email.trim() || null,
                        phone,
                    },
                });

                if (!result.success) {
                    this.showAlert('error', result.message);
                    return;
                }

                const authData = result.payload?.data || {};
                if (!authData.token) {
                    this.showAlert('error', 'Registration succeeded but token was not returned.');
                    return;
                }

                setToken('vendor', authData.token);
                redirectTo('/vendor/home');
            } catch {
                this.showAlert('error', 'Unable to complete registration right now.');
            } finally {
                this.loading = false;
            }
        },
    };
}

function registerDriverAuthPage() {
    return {
        email: '',
        password: '',
        showPassword: false,
        loading: false,
        alert: null,

        init() {
            this.checkExistingSession();
        },

        showAlert(type, message) {
            this.alert = { type, message };
        },

        clearAlert() {
            this.alert = null;
        },

        async checkExistingSession() {
            const token = getToken('driver');
            if (!token) {
                return;
            }

            const result = await apiRequest('/api/v1/driver/profile', { role: 'driver' });
            if (result.success) {
                redirectTo('/driver/home');
                return;
            }

            clearToken('driver');
        },

        async login() {
            if (!this.email.trim() || !this.password) {
                this.showAlert('error', 'Email and password are required.');
                return;
            }

            this.loading = true;
            this.clearAlert();

            try {
                const result = await apiRequest('/api/v1/driver/login', {
                    method: 'POST',
                    data: {
                        email: this.email.trim(),
                        password: this.password,
                    },
                });

                if (!result.success) {
                    this.showAlert('error', result.message);
                    return;
                }

                const authData = result.payload?.data || {};
                if (!authData.token) {
                    this.showAlert('error', 'Login succeeded but token was not returned.');
                    return;
                }

                setToken('driver', authData.token);
                redirectTo('/driver/home');
            } catch {
                this.showAlert('error', 'Unable to login right now.');
            } finally {
                this.loading = false;
            }
        },
    };
}

function registerVendorHomePage() {
    return {
        loading: true,
        error: null,
        profile: null,
        profileForm: {
            name: '',
            business_name: '',
            email: '',
        },
        profileSaving: false,
        profileAlert: null,

        init() {
            this.bootstrap();
        },

        showProfileAlert(type, message) {
            this.profileAlert = { type, message };
        },

        syncProfileForm() {
            this.profileForm.name = this.profile?.name || '';
            this.profileForm.business_name = this.profile?.business_name || '';
            this.profileForm.email = this.profile?.email || '';
        },

        async bootstrap() {
            const token = getToken('vendor');
            if (!token) {
                redirectTo('/vendor/login');
                return;
            }

            const result = await apiRequest('/api/v1/vendor/profile', { role: 'vendor' });
            if (result.success) {
                this.profile = result.payload?.data?.user || null;
                this.syncProfileForm();
                this.loading = false;
                return;
            }

            if (result.unauthorized) {
                clearToken('vendor');
                redirectTo('/vendor/login');
                return;
            }

            this.error = result.message;
            this.loading = false;
        },

        async updateProfile() {
            if (!this.profileForm.name.trim()) {
                this.showProfileAlert('error', 'Name is required.');
                return;
            }

            this.profileSaving = true;
            this.profileAlert = null;

            const result = await apiRequest('/api/v1/vendor/profile', {
                method: 'PUT',
                role: 'vendor',
                data: {
                    name: this.profileForm.name.trim(),
                    business_name: this.profileForm.business_name.trim() || null,
                    email: this.profileForm.email.trim() || null,
                },
            });

            if (result.unauthorized) {
                clearToken('vendor');
                redirectTo('/vendor/login');
                return;
            }

            if (!result.success) {
                this.showProfileAlert('error', result.message);
                this.profileSaving = false;
                return;
            }

            this.profile = result.payload?.data?.user || this.profile;
            this.syncProfileForm();
            this.showProfileAlert('success', result.message);
            this.profileSaving = false;
        },

        async logout() {
            await apiRequest('/api/v1/auth/vendor/logout', {
                method: 'POST',
                role: 'vendor',
            });

            clearToken('vendor');
            redirectTo('/vendor/login');
        },
    };
}

function registerDriverHomePage() {
    return {
        loading: true,
        error: null,
        profile: null,
        profileForm: {
            name: '',
            phone: '',
            vehicle_type: '',
            vehicle_number: '',
            license_number: '',
            base_location: '',
        },
        passwordForm: {
            current_password: '',
            new_password: '',
            confirm_password: '',
        },
        vehicleTypeOptions: ['motorcycle', 'car', 'van', 'truck'],
        profileSaving: false,
        passwordSaving: false,
        profileAlert: null,
        passwordAlert: null,
        showCurrentPassword: false,
        showNewPassword: false,
        showConfirmPassword: false,

        init() {
            this.bootstrap();
        },

        showProfileAlert(type, message) {
            this.profileAlert = { type, message };
        },

        showPasswordAlert(type, message) {
            this.passwordAlert = { type, message };
        },

        syncProfileForm() {
            this.profileForm.name = this.profile?.name || '';
            this.profileForm.phone = this.profile?.phone || '';
            this.profileForm.vehicle_type = this.profile?.vehicle_type || '';
            this.profileForm.vehicle_number = this.profile?.vehicle_number || '';
            this.profileForm.license_number = this.profile?.license_number || '';
            this.profileForm.base_location = this.profile?.base_location || '';
        },

        async bootstrap() {
            const token = getToken('driver');
            if (!token) {
                redirectTo('/driver/login');
                return;
            }

            const result = await apiRequest('/api/v1/driver/profile', { role: 'driver' });
            if (result.success) {
                this.profile = result.payload?.data?.user || null;
                this.syncProfileForm();
                this.loading = false;
                return;
            }

            if (result.unauthorized) {
                clearToken('driver');
                redirectTo('/driver/login');
                return;
            }

            this.error = result.message;
            this.loading = false;
        },

        async updateProfile() {
            if (!this.profileForm.name.trim()) {
                this.showProfileAlert('error', 'Name is required.');
                return;
            }
            if (!this.profileForm.phone.trim()) {
                this.showProfileAlert('error', 'Phone is required.');
                return;
            }
            if (!this.profileForm.vehicle_type) {
                this.showProfileAlert('error', 'Vehicle type is required.');
                return;
            }

            this.profileSaving = true;
            this.profileAlert = null;

            const result = await apiRequest('/api/v1/driver/profile', {
                method: 'PUT',
                role: 'driver',
                data: {
                    name: this.profileForm.name.trim(),
                    phone: this.profileForm.phone.trim(),
                    vehicle_type: this.profileForm.vehicle_type,
                    vehicle_number: this.profileForm.vehicle_number.trim() || null,
                    license_number: this.profileForm.license_number.trim() || null,
                    base_location: this.profileForm.base_location.trim() || null,
                },
            });

            if (result.unauthorized) {
                clearToken('driver');
                redirectTo('/driver/login');
                return;
            }

            if (!result.success) {
                this.showProfileAlert('error', result.message);
                this.profileSaving = false;
                return;
            }

            this.profile = result.payload?.data?.user || this.profile;
            this.syncProfileForm();
            this.showProfileAlert('success', result.message);
            this.profileSaving = false;
        },

        async changePassword() {
            if (!this.passwordForm.current_password) {
                this.showPasswordAlert('error', 'Current password is required.');
                return;
            }
            if (!this.passwordForm.new_password) {
                this.showPasswordAlert('error', 'New password is required.');
                return;
            }
            if (this.passwordForm.new_password.length < 6) {
                this.showPasswordAlert('error', 'New password must be at least 6 characters.');
                return;
            }
            if (!this.passwordForm.confirm_password) {
                this.showPasswordAlert('error', 'Confirm password is required.');
                return;
            }
            if (this.passwordForm.new_password !== this.passwordForm.confirm_password) {
                this.showPasswordAlert('error', 'Password confirmation does not match.');
                return;
            }

            this.passwordSaving = true;
            this.passwordAlert = null;

            const result = await apiRequest('/api/v1/driver/change-password', {
                method: 'PUT',
                role: 'driver',
                data: {
                    current_password: this.passwordForm.current_password,
                    new_password: this.passwordForm.new_password,
                    confirm_password: this.passwordForm.confirm_password,
                },
            });

            if (result.unauthorized) {
                clearToken('driver');
                redirectTo('/driver/login');
                return;
            }

            if (!result.success) {
                this.showPasswordAlert('error', result.message);
                this.passwordSaving = false;
                return;
            }

            const newToken = result.payload?.data?.token || null;
            if (newToken) {
                setToken('driver', newToken);
            }

            this.passwordForm.current_password = '';
            this.passwordForm.new_password = '';
            this.passwordForm.confirm_password = '';
            this.showPasswordAlert('success', result.message);
            this.passwordSaving = false;
        },

        async logout() {
            await apiRequest('/api/v1/driver/logout', {
                method: 'POST',
                role: 'driver',
            });

            clearToken('driver');
            redirectTo('/driver/login');
        },
    };
}

function registerPortalComponents() {
    if (!window.Alpine) {
        return;
    }

    window.Alpine.data('vendorAuthPage', registerVendorAuthPage);
    window.Alpine.data('driverAuthPage', registerDriverAuthPage);
    window.Alpine.data('vendorHomePage', registerVendorHomePage);
    window.Alpine.data('driverHomePage', registerDriverHomePage);
}

if (window.Alpine) {
    registerPortalComponents();
} else {
    document.addEventListener('alpine:init', registerPortalComponents);
}
