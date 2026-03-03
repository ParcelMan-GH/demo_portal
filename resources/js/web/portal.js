import {
    apiRequest,
    clearToken,
    getToken,
    setToken,
} from './auth-client';
import './vendor-shipments';
import './vendor-invoices';
import './driver-pickups';
import './driver-transports';
import './driver-deliveries';

function redirectTo(url) {
    window.location.href = url;
}

function cleanPhoneInput(value) {
    // Light cleanup for use during typing — just strip invalid chars.
    let v = String(value || '').replace(/[^\d+]/g, '');
    if (v.includes('+')) {
        v = '+' + v.replace(/\+/g, '');
    }
    return v.slice(0, 14);
}

function normalizePhone(value) {
    // Full normalization — converts any Ghana format to +233XXXXXXXXX.
    const digits = String(value || '').replace(/\D/g, '');

    // 233XXXXXXXXX or +233XXXXXXXXX (12 digits)
    if (digits.startsWith('233') && digits.length === 12) return '+' + digits;
    // 0XXXXXXXXX (10 digits)
    if (digits.startsWith('0') && digits.length === 10) return '+233' + digits.slice(1);
    // Bare 9 digits (no prefix at all)
    if (digits.length === 9 && !digits.startsWith('0') && !digits.startsWith('233')) return '+233' + digits;

    // Partial / already clean — return lightly cleaned
    const v = String(value || '').replace(/[^\d+]/g, '');
    return v.startsWith('+') ? ('+' + v.replace(/\+/g, '')).slice(0, 14) : v.slice(0, 13);
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
        otpDigits: ['', '', '', '', '', ''],
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
        otpExpiresHandle: null,
        registrationExpired: false,
        registrationSuccess: false,
        registrationExpiresIn: 0,
        registrationExpiresHandle: null,

        init() {
            if (this.restoreRegisterState()) return;
            if (this.restoreOtpState()) return;
            this.checkExistingSession();
        },

        saveOtpState(expiresIn) {
            localStorage.setItem('parcelman_vendor_otp', JSON.stringify({
                verifiedPhone: this.verifiedPhone,
                expiresAt: Date.now() + expiresIn * 1000,
            }));
        },

        clearOtpState() {
            localStorage.removeItem('parcelman_vendor_otp');
        },

        saveRegisterState() {
            localStorage.setItem('parcelman_vendor_register', JSON.stringify({
                verifiedPhone: this.verifiedPhone,
                verifiedAt: Date.now(),
            }));
        },

        clearRegisterState() {
            localStorage.removeItem('parcelman_vendor_register');
        },

        restoreRegisterState() {
            try {
                const raw = localStorage.getItem('parcelman_vendor_register');
                if (!raw) return false;
                const { verifiedPhone, verifiedAt } = JSON.parse(raw);
                if (!verifiedPhone) {
                    this.clearRegisterState();
                    return false;
                }
                // Expire after 10 minutes — matches backend window
                const ageMs = Date.now() - (verifiedAt || 0);
                if (ageMs > 10 * 60 * 1000) {
                    this.clearRegisterState();
                    return false;
                }
                const remainingSecs = Math.max(0, Math.floor((10 * 60 * 1000 - ageMs) / 1000));
                this.verifiedPhone = verifiedPhone;
                this.step = 'register';
                this.startRegistrationCountdown(remainingSecs);
                return true;
            } catch {
                this.clearRegisterState();
                return false;
            }
        },

        restoreOtpState() {
            try {
                const raw = localStorage.getItem('parcelman_vendor_otp');
                if (!raw) return false;
                const { verifiedPhone, expiresAt } = JSON.parse(raw);
                const remaining = Math.floor((expiresAt - Date.now()) / 1000);
                if (remaining <= 0 || !verifiedPhone) {
                    this.clearOtpState();
                    return false;
                }
                this.verifiedPhone = verifiedPhone;
                this.step = 'otp';
                this.otpDigits = ['', '', '', '', '', ''];
                this.otp = '';
                this.startOtpCountdown(remaining);
                return true;
            } catch {
                this.clearOtpState();
                return false;
            }
        },

        get canResendOtp() {
            return this.cooldownRemaining <= 0 && !this.loading;
        },

        get maskedPhone() {
            const p = this.verifiedPhone;
            if (!p || !p.startsWith('+233') || p.length < 8) return p || '';
            const local = p.slice(4); // 9 local digits
            const start = local.slice(0, 2);
            const end = local.slice(-3);
            return `+233 ${start} XXXX ${end}`;
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
            // Allow typing freely — only strip non-phone characters, don't reformat while typing.
            let v = String(event.target.value || '').replace(/[^\d+]/g, '');
            if (v.includes('+')) v = '+' + v.replace(/\+/g, '');
            this.phone = v.slice(0, 14);
        },

        formatPhoneOnBlur() {
            // On focus loss: strip +233 / 233 / leading 0 — show only the 9 local digits.
            let v = String(this.phone || '').replace(/\D/g, '');
            v = v.replace(/^233/, '').replace(/^0+/, '');
            this.phone = v.slice(0, 9);
        },

        onOtpInput(event) {
            this.otp = String(event.target.value || '').replace(/\D/g, '').slice(0, 6);
        },

        onOtpDigit(event, index) {
            const val = event.target.value.replace(/\D/g, '').slice(-1);
            const digits = [...this.otpDigits];
            digits[index] = val;
            this.otpDigits = digits;
            this.otp = digits.join('');
            if (val && index < 5) {
                this.$nextTick(() => this.$refs['otp' + (index + 1)]?.focus());
            } else if (val && index === 5 && this.otp.length === 6 && this.otpExpiresIn > 0) {
                this.$nextTick(() => this.verifyOtp());
            }
        },

        onOtpBack(event, index) {
            if (event.key === 'Backspace') {
                const digits = [...this.otpDigits];
                if (!digits[index] && index > 0) {
                    digits[index - 1] = '';
                    this.otpDigits = digits;
                    this.otp = digits.join('');
                    this.$nextTick(() => this.$refs['otp' + (index - 1)]?.focus());
                } else {
                    digits[index] = '';
                    this.otpDigits = digits;
                    this.otp = digits.join('');
                }
            }
        },

        onOtpPaste(event) {
            const text = (event.clipboardData || window.clipboardData).getData('text');
            const chars = text.replace(/\D/g, '').slice(0, 6).split('');
            const digits = ['', '', '', '', '', ''];
            chars.forEach((c, i) => { digits[i] = c; });
            this.otpDigits = digits;
            this.otp = digits.join('');
            const focusIdx = Math.min(chars.length, 5);
            this.$nextTick(() => {
                this.$refs['otp' + focusIdx]?.focus();
                if (this.otp.length === 6 && this.otpExpiresIn > 0) {
                    this.verifyOtp();
                }
            });
        },

        startOtpCountdown(seconds) {
            if (this.otpExpiresHandle) clearInterval(this.otpExpiresHandle);
            this.otpExpiresIn = Math.max(0, Number(seconds) || 300);
            if (this.otpExpiresIn <= 0) return;
            this.otpExpiresHandle = setInterval(() => {
                if (this.otpExpiresIn <= 1) {
                    clearInterval(this.otpExpiresHandle);
                    this.otpExpiresHandle = null;
                    this.otpExpiresIn = 0;
                } else {
                    this.otpExpiresIn -= 1;
                }
            }, 1000);
        },

        startRegistrationCountdown(seconds) {
            if (this.registrationExpiresHandle) clearInterval(this.registrationExpiresHandle);
            this.registrationExpiresHandle = null;
            if (seconds <= 0) {
                this.registrationExpiresIn = 0;
                this.registrationExpired = true;
                return;
            }
            this.registrationExpiresIn = Math.floor(seconds);
            this.registrationExpiresHandle = setInterval(() => {
                this.registrationExpiresIn -= 1;
                if (this.registrationExpiresIn <= 0) {
                    this.registrationExpiresIn = 0;
                    clearInterval(this.registrationExpiresHandle);
                    this.registrationExpiresHandle = null;
                    this.registrationExpired = true;
                }
            }, 1000);
        },

        formatOtpExpiry() {
            const v = this.otpExpiresIn;
            const m = Math.floor(v / 60);
            const s = v % 60;
            return m + ':' + String(s).padStart(2, '0');
        },

        formatRegistrationExpiry() {
            const v = this.registrationExpiresIn;
            const m = Math.floor(v / 60);
            const s = v % 60;
            return m + ':' + String(s).padStart(2, '0');
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
            const normalizedPhone = normalizePhone(this.phone || this.verifiedPhone);

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
                    this.otpDigits = ['', '', '', '', '', ''];
                    this.otp = '';
                    const expiresIn = Number(result.payload?.data?.expires_in || 300);
                    this.startOtpCountdown(expiresIn);
                    this.saveOtpState(expiresIn);
                    this.clearAlert();
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
                        fcm_token: localStorage.getItem('parcelman_vendor_token') || null,
                    },
                });

                if (!result.success) {
                    this.showAlert('error', result.message);
                    return;
                }

                const authData = result.payload?.data || {};
                if (authData.user_exists === true && authData.token) {
                    this.clearOtpState();
                    setToken('vendor', authData.token);
                    redirectTo('/vendor/home');
                    return;
                }

                this.clearOtpState();
                this.verifiedPhone = phone;
                this.saveRegisterState();
                this.setStep('register');
                this.startRegistrationCountdown(10 * 60);
            } catch {
                this.showAlert('error', 'Unable to verify OTP right now.');
            } finally {
                this.loading = false;
            }
        },

        backToPhone() {
            this.otp = '';
            this.verifiedPhone = '';
            this.clearOtpState();
            this.clearRegisterState();
            if (this.registrationExpiresHandle) clearInterval(this.registrationExpiresHandle);
            this.registrationExpiresHandle = null;
            this.registrationExpired = false;
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
                        fcm_token: localStorage.getItem('parcelman_vendor_token') || null,
                    },
                });

                if (!result.success) {
                    if ((result.message || '').toLowerCase().includes('verification expired')) {
                        this.registrationExpired = true;
                    } else {
                        this.showAlert('error', result.message);
                    }
                    return;
                }

                const authData = result.payload?.data || {};
                if (!authData.token) {
                    this.showAlert('error', 'Registration succeeded but token was not returned.');
                    return;
                }

                this.clearRegisterState();
                setToken('vendor', authData.token);
                this.registrationSuccess = true;
                setTimeout(() => redirectTo('/vendor/home'), 2000);
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
                const driverName = authData.user?.name || '';
                localStorage.setItem('parcelman_driver_name', driverName);
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
        stats: {
            shipments: { total: 0, draft: 0, in_progress: 0, delivered: 0, cancelled: 0 },
            invoices: { total: 0, pending: 0, accepted: 0, rejected: 0 },
        },
        recentShipments: [],
        recentInvoices: [],

        init() {
            this.bootstrap();
        },

        get todayFormatted() {
            return new Date().toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        },

        statusColor(status) {
            const map = {
                draft: 'gray', submitted: 'blue',
                invoice_sent: 'orange', invoice_accepted: 'orange',
                pickup_assigned: 'cyan', picked_up: 'cyan',
                at_warehouse: 'cyan', sorted: 'cyan',
                in_transit: 'cyan', at_destination: 'cyan',
                out_for_delivery: 'cyan',
                delivered: 'green', cancelled: 'red',
            };
            return map[status] || 'gray';
        },

        invoiceStatusColor(status) {
            const map = {
                pending: 'amber', sent: 'amber',
                accepted: 'green', rejected: 'red', cancelled: 'gray',
            };
            return map[status] || 'gray';
        },

        statusLabel(status) {
            if (!status) return '-';
            return String(status).split('_').map(t => t.charAt(0).toUpperCase() + t.slice(1)).join(' ');
        },

        formatDate(value) {
            if (!value) return '-';
            const d = new Date(value);
            if (Number.isNaN(d.getTime())) return '-';
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        },

        formatMoney(value, currency = 'GHS') {
            const amount = Number(value);
            if (Number.isNaN(amount)) return '-';
            try {
                return new Intl.NumberFormat('en-GH', { style: 'currency', currency, minimumFractionDigits: 2 }).format(amount);
            } catch {
                return `${amount.toFixed(2)} ${currency}`;
            }
        },

        async bootstrap() {
            const token = getToken('vendor');
            if (!token) {
                redirectTo('/vendor/login');
                return;
            }

            const inProgressStatuses = 'status[]=submitted&status[]=invoice_sent&status[]=invoice_accepted&status[]=pickup_assigned&status[]=picked_up&status[]=at_warehouse&status[]=sorted&status[]=in_transit&status[]=at_destination&status[]=out_for_delivery';

            const [profileResult, ...rest] = await Promise.all([
                apiRequest('/api/v1/vendor/profile', { role: 'vendor' }),
                // Shipment counts: total, draft, in_progress, delivered, cancelled
                apiRequest('/api/v1/vendor/shipments?limit=1&offset=0', { role: 'vendor' }),
                apiRequest('/api/v1/vendor/shipments?limit=1&offset=0&status[]=draft', { role: 'vendor' }),
                apiRequest(`/api/v1/vendor/shipments?limit=1&offset=0&${inProgressStatuses}`, { role: 'vendor' }),
                apiRequest('/api/v1/vendor/shipments?limit=1&offset=0&status[]=delivered', { role: 'vendor' }),
                apiRequest('/api/v1/vendor/shipments?limit=1&offset=0&status[]=cancelled', { role: 'vendor' }),
                // Invoice counts: total, pending, accepted, rejected
                apiRequest('/api/v1/vendor/invoices?limit=1&offset=0', { role: 'vendor' }),
                apiRequest('/api/v1/vendor/invoices?limit=1&offset=0&status[]=pending&status[]=sent', { role: 'vendor' }),
                apiRequest('/api/v1/vendor/invoices?limit=1&offset=0&status[]=accepted', { role: 'vendor' }),
                apiRequest('/api/v1/vendor/invoices?limit=1&offset=0&status[]=rejected', { role: 'vendor' }),
                // Recent lists
                apiRequest('/api/v1/vendor/shipments?limit=5&offset=0&sort_by=created_at&sort_dir=desc', { role: 'vendor' }),
                apiRequest('/api/v1/vendor/invoices?limit=5&offset=0&sort_by=created_at&sort_dir=desc', { role: 'vendor' }),
            ]);

            if (profileResult.unauthorized || rest.some(r => r.unauthorized)) {
                clearToken('vendor');
                redirectTo('/vendor/login');
                return;
            }

            if (!profileResult.success) {
                this.error = profileResult.message;
                this.loading = false;
                return;
            }

            this.profile = profileResult.payload?.data?.user || null;

            const ct = (r) => r.success ? (r.payload?.data?.pagination?.total || 0) : 0;
            this.stats.shipments.total = ct(rest[0]);
            this.stats.shipments.draft = ct(rest[1]);
            this.stats.shipments.in_progress = ct(rest[2]);
            this.stats.shipments.delivered = ct(rest[3]);
            this.stats.shipments.cancelled = ct(rest[4]);
            this.stats.invoices.total = ct(rest[5]);
            this.stats.invoices.pending = ct(rest[6]);
            this.stats.invoices.accepted = ct(rest[7]);
            this.stats.invoices.rejected = ct(rest[8]);

            if (rest[9].success) {
                this.recentShipments = rest[9].payload?.data?.shipments || [];
            }
            if (rest[10].success) {
                this.recentInvoices = rest[10].payload?.data?.invoices || [];
            }

            this.loading = false;
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

function registerVendorProfilePage() {
    return {
        loading: true,
        error: null,
        profile: null,
        editing: false,
        saving: false,
        alert: null,
        form: { name: '', business_name: '', email: '' },

        init() {
            this.loadProfile();
        },

        get memberSince() {
            if (!this.profile?.created_at) return '-';
            const d = new Date(this.profile.created_at);
            if (Number.isNaN(d.getTime())) return '-';
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        },

        get initial() {
            return (this.profile?.name || 'V').charAt(0).toUpperCase();
        },

        showAlert(type, message, duration = 4000) {
            this.alert = { type, message };
            clearTimeout(this._alertTimer);
            this._alertTimer = setTimeout(() => { this.alert = null; }, duration);
        },

        startEditing() {
            this.form.name = this.profile?.name || '';
            this.form.business_name = this.profile?.business_name || '';
            this.form.email = this.profile?.email || '';
            this.editing = true;
            this.alert = null;
        },

        cancelEditing() {
            this.editing = false;
            this.alert = null;
        },

        async loadProfile() {
            const token = getToken('vendor');
            if (!token) {
                redirectTo('/vendor/login');
                return;
            }

            const result = await apiRequest('/api/v1/vendor/profile', { role: 'vendor' });

            if (result.unauthorized) {
                clearToken('vendor');
                redirectTo('/vendor/login');
                return;
            }

            if (!result.success) {
                this.error = result.message;
                this.loading = false;
                return;
            }

            this.profile = result.payload?.data?.user || null;
            this.loading = false;
        },

        async saveProfile() {
            if (!this.form.name.trim()) {
                this.showAlert('error', 'Name is required.');
                return;
            }

            this.saving = true;
            this.alert = null;

            try {
                const result = await apiRequest('/api/v1/vendor/profile', {
                    method: 'PUT',
                    role: 'vendor',
                    data: {
                        name: this.form.name.trim(),
                        business_name: this.form.business_name.trim() || null,
                        email: this.form.email.trim() || null,
                    },
                });

                if (result.success) {
                    this.profile = result.payload?.data?.user || this.profile;
                    this.editing = false;
                    this.showAlert('success', 'Profile updated successfully.');
                } else {
                    this.showAlert('error', result.message || 'Failed to update profile.');
                }
            } catch {
                this.showAlert('error', 'Something went wrong. Please try again.');
            } finally {
                this.saving = false;
            }
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
        stats: {
            pickups: { total: 0, active: 0, en_route: 0, completed: 0, cancelled: 0 },
            transports: { active: 0, in_transit: 0, arrived: 0 },
            deliveries: { active: 0, out_for_delivery: 0, partially_delivered: 0, completed: 0 },
        },
        recentPickups: [],
        recentDeliveries: [],

        init() {
            this.bootstrap();
        },

        get todayFormatted() {
            return new Date().toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        },

        get totalActiveTasks() {
            return this.stats.pickups.active + this.stats.transports.active + this.stats.deliveries.active;
        },

        get vehicleSubtitle() {
            const parts = [];
            if (this.profile?.vehicle_type) {
                parts.push(this.profile.vehicle_type.charAt(0).toUpperCase() + this.profile.vehicle_type.slice(1));
            }
            if (this.profile?.vehicle_number) {
                parts.push(this.profile.vehicle_number);
            }
            if (this.profile?.base_location) {
                parts.push('Based at ' + this.profile.base_location);
            }
            return parts.join(' · ');
        },

        pickupStatusColor(status) {
            const map = {
                assigned: 'blue',
                en_route: 'orange',
                arrived: 'amber',
                picking_up: 'emerald',
                completed: 'green',
                cancelled: 'red',
            };
            return map[status] || 'gray';
        },

        deliveryStatusColor(status) {
            const map = {
                draft: 'gray',
                assigned: 'blue',
                out_for_delivery: 'orange',
                partially_delivered: 'amber',
                completed: 'green',
                cancelled: 'red',
            };
            return map[status] || 'gray';
        },

        statusLabel(status) {
            if (!status) return '-';
            return String(status).split('_').map(t => t.charAt(0).toUpperCase() + t.slice(1)).join(' ');
        },

        formatDate(value) {
            if (!value) return '-';
            const d = new Date(value);
            if (Number.isNaN(d.getTime())) return '-';
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        },

        async bootstrap() {
            const token = getToken('driver');
            if (!token) {
                redirectTo('/driver/login');
                return;
            }

            const activePickupStatuses = 'status[]=assigned&status[]=en_route&status[]=arrived&status[]=picking_up';
            const activeTransportStatuses = 'status[]=assigned&status[]=loading&status[]=in_transit';
            const activeDeliveryStatuses = 'status[]=assigned&status[]=out_for_delivery';

            const [profileResult, ...rest] = await Promise.all([
                apiRequest('/api/v1/driver/profile', { role: 'driver' }),
                // Pickup counts
                apiRequest('/api/v1/driver/pickups?limit=1&offset=0', { role: 'driver' }),
                apiRequest(`/api/v1/driver/pickups?limit=1&offset=0&${activePickupStatuses}`, { role: 'driver' }),
                apiRequest('/api/v1/driver/pickups?limit=1&offset=0&status[]=en_route', { role: 'driver' }),
                apiRequest('/api/v1/driver/pickups?limit=1&offset=0&status[]=completed', { role: 'driver' }),
                apiRequest('/api/v1/driver/pickups?limit=1&offset=0&status[]=cancelled', { role: 'driver' }),
                // Transport counts
                apiRequest(`/api/v1/driver/transports?limit=1&offset=0&${activeTransportStatuses}`, { role: 'driver' }),
                apiRequest('/api/v1/driver/transports?limit=1&offset=0&status[]=in_transit', { role: 'driver' }),
                apiRequest('/api/v1/driver/transports?limit=1&offset=0&status[]=arrived', { role: 'driver' }),
                // Delivery counts
                apiRequest(`/api/v1/driver/deliveries?limit=1&offset=0&${activeDeliveryStatuses}`, { role: 'driver' }),
                apiRequest('/api/v1/driver/deliveries?limit=1&offset=0&status[]=out_for_delivery', { role: 'driver' }),
                apiRequest('/api/v1/driver/deliveries?limit=1&offset=0&status[]=partially_delivered', { role: 'driver' }),
                apiRequest('/api/v1/driver/deliveries?limit=1&offset=0&status[]=completed', { role: 'driver' }),
                // Recent lists
                apiRequest('/api/v1/driver/pickups?limit=5&offset=0&sort_by=created_at&sort_dir=desc', { role: 'driver' }),
                apiRequest('/api/v1/driver/deliveries?limit=5&offset=0&sort_by=created_at&sort_dir=desc', { role: 'driver' }),
            ]);

            if (profileResult.unauthorized || rest.some(r => r.unauthorized)) {
                clearToken('driver');
                redirectTo('/driver/login');
                return;
            }

            if (!profileResult.success) {
                this.error = profileResult.message || 'Failed to load profile.';
                this.loading = false;
                return;
            }

            this.profile = profileResult.payload?.data?.user || null;

            const ct = (r) => r.success ? (r.payload?.data?.pagination?.total ?? 0) : 0;
            this.stats.pickups.total       = ct(rest[0]);
            this.stats.pickups.active      = ct(rest[1]);
            this.stats.pickups.en_route    = ct(rest[2]);
            this.stats.pickups.completed   = ct(rest[3]);
            this.stats.pickups.cancelled   = ct(rest[4]);
            this.stats.transports.active   = ct(rest[5]);
            this.stats.transports.in_transit = ct(rest[6]);
            this.stats.transports.arrived  = ct(rest[7]);
            this.stats.deliveries.active             = ct(rest[8]);
            this.stats.deliveries.out_for_delivery   = ct(rest[9]);
            this.stats.deliveries.partially_delivered = ct(rest[10]);
            this.stats.deliveries.completed          = ct(rest[11]);

            if (rest[12].success) {
                this.recentPickups = rest[12].payload?.data?.pickups || [];
            }
            if (rest[13].success) {
                this.recentDeliveries = rest[13].payload?.data?.deliveries
                    || rest[13].payload?.data?.runs
                    || [];
            }

            this.loading = false;
        },

        async logout() {
            await apiRequest('/api/v1/driver/logout', {
                method: 'POST',
                role: 'driver',
            });
            clearToken('driver');
            localStorage.removeItem('parcelman_driver_name');
            redirectTo('/driver/login');
        },
    };
}

function registerDriverProfilePage() {
    return {
        loading: true,
        profile: null,
        error: null,
        alert: null,
        editMode: false,
        profileSaving: false,
        passwordSaving: false,
        passwordAlert: null,
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
        vehicleTypeOptions: ['Motorcycle', 'Bicycle', 'Car', 'Van', 'Truck', 'Mini Truck'],

        async init() {
            const token = getToken('driver');
            if (!token) { redirectTo('/driver/login'); return; }
            await this.loadProfile();
        },

        async loadProfile() {
            this.loading = true;
            try {
                const res = await apiRequest('/api/v1/driver/profile', { role: 'driver' });
                if (res.unauthorized) { clearToken('driver'); redirectTo('/driver/login'); return; }
                if (res.success) {
                    this.profile = res.payload?.data?.driver || res.payload?.data?.user || res.payload?.data || null;
                } else {
                    this.error = res.message || 'Could not load profile.';
                }
            } catch {
                this.error = 'Network error loading profile.';
            } finally {
                this.loading = false;
            }
        },

        startEdit() {
            this.profileForm = {
                name: this.profile?.name || '',
                phone: this.profile?.phone || '',
                vehicle_type: this.vehicleTypeOptions.find(o => o.toLowerCase() === (this.profile?.vehicle_type || '').toLowerCase()) || this.profile?.vehicle_type || '',
                vehicle_number: this.profile?.vehicle_number || '',
                license_number: this.profile?.license_number || '',
                base_location: this.profile?.base_location || '',
            };
            this.editMode = true;
        },

        cancelEdit() {
            this.editMode = false;
            this.alert = null;
        },

        showToast(type, message, duration = 4000) {
            this.alert = { type, message };
            clearTimeout(this._alertTimer);
            this._alertTimer = setTimeout(() => { this.alert = null; }, duration);
        },

        async saveProfile() {
            this.profileSaving = true;
            this.alert = null;
            try {
                const data = {
                    ...this.profileForm,
                    vehicle_type: (this.profileForm.vehicle_type || '').toLowerCase(),
                };
                const res = await apiRequest('/api/v1/driver/profile', {
                    method: 'PUT',
                    role: 'driver',
                    data,
                });
                if (res.unauthorized) { clearToken('driver'); redirectTo('/driver/login'); return; }
                if (res.success) {
                    this.profile = res.payload?.data?.driver || res.payload?.data?.user || res.payload?.data || this.profile;
                    this.showToast('success', 'Profile updated successfully.');
                    this.editMode = false;
                } else {
                    this.showToast('error', res.message || 'Failed to update profile.');
                }
            } catch {
                this.showToast('error', 'Network error. Please try again.');
            } finally {
                this.profileSaving = false;
            }
        },

        showPasswordToast(type, message, duration = 4000) {
            this.passwordAlert = { type, message };
            clearTimeout(this._pwAlertTimer);
            this._pwAlertTimer = setTimeout(() => { this.passwordAlert = null; }, duration);
        },

        async changePassword() {
            if (this.passwordForm.new_password !== this.passwordForm.confirm_password) {
                this.showPasswordToast('error', 'New passwords do not match.');
                return;
            }
            this.passwordSaving = true;
            this.passwordAlert = null;
            try {
                const res = await apiRequest('/api/v1/driver/change-password', {
                    method: 'POST',
                    role: 'driver',
                    data: {
                        current_password: this.passwordForm.current_password,
                        new_password: this.passwordForm.new_password,
                        new_password_confirmation: this.passwordForm.confirm_password,
                    },
                });
                if (res.unauthorized) { clearToken('driver'); redirectTo('/driver/login'); return; }
                if (res.success) {
                    this.showPasswordToast('success', 'Password changed successfully.');
                    this.passwordForm = { current_password: '', new_password: '', confirm_password: '' };
                } else {
                    this.showPasswordToast('error', res.message || 'Failed to change password.');
                }
            } catch {
                this.showPasswordToast('error', 'Network error. Please try again.');
            } finally {
                this.passwordSaving = false;
            }
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
    window.Alpine.data('vendorProfilePage', registerVendorProfilePage);
    window.Alpine.data('driverHomePage', registerDriverHomePage);
    window.Alpine.data('driverProfilePage', registerDriverProfilePage);
}

if (window.Alpine) {
    registerPortalComponents();
} else {
    document.addEventListener('alpine:init', registerPortalComponents);
}
