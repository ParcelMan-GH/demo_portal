<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Capture Walk-in Packages</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-slate-50 min-h-screen antialiased">
<div class="min-h-screen flex flex-col p-5" x-data="mobileUploader()" x-cloak>

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <div class="w-11 h-11 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div class="min-w-0">
            <h1 class="text-lg font-bold text-slate-900 leading-tight">Walk-in Packages</h1>
            <p class="text-slate-500 text-xs">
                <span x-show="step === 'select'">Take or pick the package photos</span>
                <span x-show="step === 'details'">Fill the details for each package</span>
                <span x-show="step === 'done'">All sent to the dashboard</span>
            </p>
        </div>
    </div>

    {{-- ══ STEP 1: capture / select ══ --}}
    <div x-show="step === 'select'" class="flex-1 flex flex-col justify-center space-y-4 max-w-sm w-full mx-auto">
        <label class="w-full bg-orange-600 active:bg-orange-700 text-white rounded-2xl py-4 px-6 flex items-center justify-center gap-3 text-lg font-bold shadow-xl shadow-orange-600/30 active:scale-95 cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Take Photos
            <input type="file" accept="image/*" capture="environment" multiple class="hidden" @change="uploadPhotos">
        </label>

        <label class="w-full bg-slate-900 active:bg-slate-800 text-white rounded-2xl py-4 px-6 flex items-center justify-center gap-3 text-lg font-bold shadow-xl shadow-slate-900/30 active:scale-95 cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Choose from Gallery
            <input type="file" accept="image/*" multiple class="hidden" @change="uploadPhotos">
        </label>

        <p class="text-center text-xs text-slate-500 px-2">
            Select as many photos as you have packages — you will add each package's details next.
        </p>
    </div>

    {{-- uploading --}}
    <div x-show="status === 'uploading'" class="flex-1 flex items-center justify-center">
        <div class="bg-slate-900 text-white rounded-2xl py-4 px-6 flex items-center gap-3 text-lg font-bold shadow-xl">
            <svg class="w-6 h-6 animate-spin text-orange-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            Uploading <span x-text="progress.total > 1 ? ` ${progress.done}/${progress.total}` : ''"></span>...
        </div>
    </div>

    {{-- ══ STEP 2: details per package ══ --}}
    <div x-show="step === 'details'" class="flex-1 w-full max-w-md mx-auto flex flex-col">

        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                Package <span x-text="index + 1"></span> of <span x-text="images.length"></span>
            </span>
            <button type="button" @click="addMore()" class="text-xs font-bold text-orange-600 underline underline-offset-4">+ Add more photos</button>
        </div>

        {{-- thumbnails --}}
        <div class="flex gap-2 overflow-x-auto pb-3 mb-1">
            <template x-for="(img, i) in images" :key="i">
                <button type="button" @click="index = i"
                        :class="i === index ? 'ring-2 ring-orange-500' : 'ring-1 ring-slate-200'"
                        class="relative w-14 h-14 rounded-xl overflow-hidden shrink-0">
                    <img :src="img.preview" class="w-full h-full object-cover" alt="">
                    <template x-if="img.description">
                        <span class="absolute bottom-0 right-0 bg-emerald-500 text-white text-[10px] px-1 rounded-tl">✓</span>
                    </template>
                </button>
            </template>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-4">
            <img :src="current.preview" class="w-full h-52 object-cover bg-slate-100" alt="package">

            <div class="p-4 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Receiver Name <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="current.recipient_name" placeholder="Full name"
                               class="w-full rounded-xl border-slate-300 border px-3 py-2.5 text-sm focus:border-orange-500 focus:ring-orange-500">
                        <p x-show="fieldErrorVisible(current, 'recipient_name', current.recipient_name)" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="imageFieldError(current, 'recipient_name')"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Number <span class="text-rose-500">*</span></label>
                        <input type="tel" inputmode="numeric" maxlength="10" x-model="current.recipient_phone"
                               @input="normalizePhoneInput()" placeholder="0551234567"
                               class="w-full rounded-xl border-slate-300 border px-3 py-2.5 text-sm focus:border-orange-500 focus:ring-orange-500">
                        <p x-show="fieldErrorVisible(current, 'recipient_phone', current.recipient_phone)" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="imageFieldError(current, 'recipient_phone')"></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Location</label>
                        <input type="text" x-model="current.town" @input="searchLocations()" placeholder="e.g. Taifa"
                               class="w-full rounded-xl border-slate-300 border px-3 py-2.5 text-sm focus:border-orange-500 focus:ring-orange-500">
                        <p class="text-[11px] text-slate-400 mt-1" x-show="current.region_name">
                            <span x-text="current.town"></span>, <span x-text="current.district_name"></span>, <span x-text="current.region_name"></span>
                        </p>
                        <p x-show="fieldErrorVisible(current, 'town', current.town)" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="imageFieldError(current, 'town')"></p>

                        <div x-show="current.locationResults.length" x-cloak
                             class="absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-52 overflow-y-auto">
                            <template x-for="(loc, i) in current.locationResults" :key="i">
                                <button type="button" @click="pickLocation(loc)"
                                        class="w-full text-left px-3 py-2.5 text-sm hover:bg-orange-50 border-b border-slate-100 last:border-0">
                                    <span x-text="loc.display"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Description *</label>
                        <input type="text" x-model="current.description" placeholder="e.g. Shoe, Box"
                               class="w-full rounded-xl border-slate-300 border px-3 py-2.5 text-sm focus:border-orange-500 focus:ring-orange-500">
                        <p x-show="fieldErrorVisible(current, 'description', current.description)" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="imageFieldError(current, 'description')"></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Quantity *</label>
                        <input type="number" min="1" step="1" x-model.number="current.quantity"
                               class="w-full rounded-xl border-slate-300 border px-3 py-2.5 text-sm focus:border-orange-500 focus:ring-orange-500">
                        <p x-show="fieldErrorVisible(current, 'quantity', current.quantity)" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="imageFieldError(current, 'quantity')"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Price</label>
                        <input type="number" min="0" step="0.01" x-model="current.delivery_fee" placeholder="0.00"
                               class="w-full rounded-xl border-slate-300 border px-3 py-2.5 text-sm focus:border-orange-500 focus:ring-orange-500">
                        <p x-show="fieldErrorVisible(current, 'delivery_fee', current.delivery_fee)" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="imageFieldError(current, 'delivery_fee')"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-auto">
            <button type="button" x-show="index > 0" @click="prev()"
                    class="flex-1 bg-slate-100 active:bg-slate-200 text-slate-700 rounded-2xl py-3.5 font-bold text-sm">Back</button>

            <button type="button" x-show="index < images.length - 1" @click="next()"
                    class="flex-1 bg-slate-900 active:bg-slate-800 text-white rounded-2xl py-3.5 font-bold text-sm">Next package</button>

            <button type="button" x-show="index === images.length - 1" @click="submitAll()" :disabled="sending"
                    class="flex-1 bg-orange-600 active:bg-orange-700 disabled:opacity-60 text-white rounded-2xl py-3.5 font-bold text-sm">
                <span x-text="sending ? 'Sending...' : `Send ${images.length} package${images.length === 1 ? '' : 's'}`"></span>
            </button>
        </div>
    </div>

    {{-- ══ STEP 3: done ══ --}}
    <div x-show="step === 'done'" class="flex-1 flex flex-col items-center justify-center text-center max-w-sm mx-auto">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-5">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2 class="text-xl font-bold text-slate-900 mb-2">
            <span x-text="sentCount"></span> package<span x-text="sentCount === 1 ? '' : 's'"></span> sent
        </h2>
        <p class="text-slate-500 text-sm mb-8">They are now on the dashboard, ready to save. You can close this page.</p>
        <button type="button" @click="addMore()" class="text-orange-600 font-bold text-sm underline underline-offset-4">Capture more packages</button>
    </div>

    <p class="text-center text-[11px] text-slate-400 mt-6">ParcelMan Express · walk-in capture</p>
</div>

<script>
    function mobileUploader() {
        return {
            step: 'select',            // select | details | done
            status: 'idle',            // idle | uploading
            images: [],
            index: 0,
            progress: { done: 0, total: 0, failed: 0 },
            sending: false,
            sentCount: 0,
            sessionId: '{{ $sessionId }}',
            locationTimer: null,
            submitAttempted: false,

            get current() {
                return this.images[this.index] || {};
            },

            /* ---- VALIDATION / PHONE FORMATTING ---- */
            normalizePhoneValue(value) {
                return String(value || '').replace(/\D/g, '').slice(0, 10);
            },

            // Never allow more than 10 digits in a phone field.
            normalizePhoneInput() {
                const img = this.current;
                if (!img) return;

                img.recipient_phone = this.normalizePhoneValue(img.recipient_phone);
            },

            nameError(value, label) {
                const text = String(value || '').trim();

                if (!text) return `${label} is required.`;
                if (text.length < 2) return `${label} must be at least 2 characters.`;
                if (!/^[A-Za-z][A-Za-z .'-]*$/.test(text)) {
                    return `${label}: letters, spaces, apostrophes, dots and hyphens only.`;
                }

                return '';
            },

            phoneError(value, label) {
                const digits = String(value || '').replace(/\D/g, '');

                if (!digits) return `${label} is required.`;
                if (digits.length !== 10) return `${label} must be exactly 10 digits.`;

                return '';
            },

            quantityError(value) {
                const text = String(value ?? '').trim();

                if (text === '') return 'Quantity is required.';
                if (!/^\d+$/.test(text)) return 'Quantity must be a whole number.';

                const quantity = Number(text);
                if (quantity < 1) return 'Quantity must be at least 1.';
                if (quantity > 9999) return 'Quantity cannot be more than 9999.';

                return '';
            },

            priceError(value) {
                const text = String(value ?? '').trim();

                if (text === '') return '';
                if (!/^\d+(\.\d{1,2})?$/.test(text)) return 'Price must be a number with at most 2 decimals.';
                if (Number(text) > 1000000) return 'Price looks too large.';

                return '';
            },

            imageFieldError(img, field) {
                if (!img) return '';

                switch (field) {
                    case 'recipient_name':
                        return this.nameError(img.recipient_name, 'Receiver name');
                    case 'recipient_phone':
                        return this.phoneError(img.recipient_phone, 'Number');
                    case 'town':
                        return String(img.town || '').trim() ? '' : 'Location is required.';
                    case 'description':
                        return String(img.description || '').trim() ? '' : 'Description is required.';
                    case 'quantity':
                        return this.quantityError(img.quantity);
                    case 'delivery_fee':
                        return this.priceError(img.delivery_fee);
                    default:
                        return '';
                }
            },

            fieldErrorVisible(img, field, rawValue) {
                const message = this.imageFieldError(img, field);
                if (!message) return false;

                return this.submitAttempted || String(rawValue ?? '').trim() !== '';
            },

            // Returns the message for the first invalid field, or '' when the package is fine.
            firstImageError(img) {
                const fields = ['description', 'quantity', 'recipient_name', 'recipient_phone', 'town', 'delivery_fee'];

                return fields.map((field) => this.imageFieldError(img, field)).find(Boolean) || '';
            },

            emptyImage(path) {
                return {
                    path: path,
                    preview: `/storage/${path}`,
                    description: '',
                    quantity: 1,
                    delivery_fee: '',
                    delivery_method: 'direct',
                    recipient_name: '',
                    recipient_phone: '',
                    town: '',
                    region_id: '',
                    region_name: '',
                    district_id: '',
                    district_name: '',
                    landmark: '',
                    instructions: '',
                    locationResults: [],
                };
            },

            async compressImage(file, maxSize = 1600, quality = 0.8) {
                try {
                    if (!file || !file.type || !file.type.startsWith('image/')) return file;

                    const bitmap = await createImageBitmap(file);
                    const scale = Math.min(1, maxSize / Math.max(bitmap.width, bitmap.height));
                    const width = Math.max(1, Math.round(bitmap.width * scale));
                    const height = Math.max(1, Math.round(bitmap.height * scale));

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    canvas.getContext('2d').drawImage(bitmap, 0, 0, width, height);
                    if (bitmap.close) bitmap.close();

                    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
                    if (!blob || blob.size >= file.size) return file;

                    const name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';

                    return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
                } catch (error) {
                    return file;
                }
            },

            async uploadPhotos(event) {
                const files = Array.from(event.target.files || []);
                event.target.value = '';   // allow re-picking the same file
                if (!files.length) return;

                this.status = 'uploading';
                this.progress = { done: 0, total: files.length, failed: 0 };

                for (const file of files) {
                    const upload = await this.compressImage(file);
                    const formData = new FormData();
                    formData.append('photo', upload);

                    try {
                        const response = await fetch(`/mobile-camera/${this.sessionId}/upload`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: formData,
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (response.ok && payload.path) {
                            this.images.push(this.emptyImage(payload.path));
                        } else {
                            this.progress.failed++;
                        }
                    } catch (error) {
                        this.progress.failed++;
                    }

                    this.progress.done++;
                }

                this.status = 'idle';

                if (!this.images.length) {
                    Swal.fire({ title: 'Upload failed', text: 'No photo reached the server. Please try again.', icon: 'error', confirmButtonColor: '#E2762B' });
                    return;
                }

                if (this.progress.failed > 0) {
                    Swal.fire({
                        title: 'Some photos did not upload',
                        text: this.progress.failed + ' of ' + this.progress.total + ' failed. You can add them again from this page.',
                        icon: 'warning',
                        confirmButtonColor: '#E2762B',
                    });
                }

                if (this.step !== 'details') {
                    this.step = 'details';
                    this.index = 0;
                }
            },

            addMore() {
                this.step = 'select';
                this.index = 0;
            },

            next() {
                if (this.index < this.images.length - 1) this.index++;
            },

            prev() {
                if (this.index > 0) this.index--;
            },

            searchLocations() {
                const query = (this.current.town || '').trim();
                const img = this.current;

                img.region_id = '';
                img.district_id = '';
                img.region_name = '';
                img.district_name = '';

                if (this.locationTimer) clearTimeout(this.locationTimer);

                if (query.length < 2) {
                    img.locationResults = [];
                    return;
                }

                this.locationTimer = setTimeout(async () => {
                    try {
                        const response = await fetch(`/mobile-camera/${this.sessionId}/locations?q=${encodeURIComponent(query)}`);
                        const payload = await response.json();

                        if (img === this.current) {
                            img.locationResults = payload.locations || [];
                        }
                    } catch (error) {
                        img.locationResults = [];
                    }
                }, 250);
            },

            pickLocation(location) {
                const img = this.current;
                img.town = location.name;
                img.region_id = location.region_id || '';
                img.region_name = location.region || '';
                img.district_id = location.district_id || '';
                img.district_name = location.district || '';
                img.locationResults = [];
            },

            async submitAll() {
                this.submitAttempted = true;

                const invalid = this.images.findIndex((img) => this.firstImageError(img) !== '');

                if (invalid !== -1) {
                    this.index = invalid;
                    Swal.fire({
                        title: `Package ${invalid + 1} needs attention`,
                        text: this.firstImageError(this.images[invalid]),
                        icon: 'warning',
                        confirmButtonColor: '#E2762B',
                    });
                    return;
                }

                this.sending = true;

                const payload = this.images.map((img) => ({
                    path: img.path,
                    description: img.description,
                    quantity: img.quantity || 1,
                    delivery_fee: img.delivery_fee === '' ? null : img.delivery_fee,
                    delivery_method: 'direct',
                    recipient_name: (img.recipient_name || '').trim(),
                    recipient_phone: this.normalizePhoneValue(img.recipient_phone),
                    town: img.town,
                    region_id: img.region_id || null,
                    district_id: img.district_id || null,
                }));

                try {
                    const response = await fetch(`/mobile-camera/${this.sessionId}/packages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ packages: payload }),
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Could not send the packages.');
                    }

                    this.sentCount = result.received ?? this.images.length;
                    this.images = [];
                    this.index = 0;
                    this.step = 'done';
                } catch (error) {
                    Swal.fire({
                        title: 'Sending failed',
                        text: error.message || 'Please check your connection and try again.',
                        icon: 'error',
                        confirmButtonColor: '#E2762B',
                    });
                } finally {
                    this.sending = false;
                }
            },
        };
    }
</script>
</body>
</html>
