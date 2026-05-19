<div class="space-y-6">
    <!-- Push Toggle -->
    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-800">Push Notifications</h3>
            <p class="text-sm text-slate-500 mt-0.5">Enable or disable push notifications globally</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox"
                   :checked="settings.push_notifications_enabled.value == '1' || settings.push_notifications_enabled.value === true"
                   @@change="settings.push_notifications_enabled.value = $event.target.checked ? '1' : '0'"
                   class="sr-only peer">
            <div class="h-6 w-11 rounded-full bg-slate-200 transition after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-orange-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-100"></div>
        </label>
    </div>

    <!-- Firebase Service Account Credentials -->
    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5"
         x-data="{
             uploading: false,
             uploadError: '',
             uploadSuccess: '',
             selectedFile: null,
             uploadCredentials() {
                 if (!this.selectedFile) {
                     this.uploadError = 'Please select a JSON file first.';
                     return;
                 }
                 this.uploading = true;
                 this.uploadError = '';
                 this.uploadSuccess = '';
                 const formData = new FormData();
                 formData.append('credentials', this.selectedFile);
                 formData.append('_token', document.querySelector('meta[name=csrf-token]').content);
                 fetch('{{ route('admin.settings.upload-firebase-credentials') }}', {
                     method: 'POST',
                     body: formData,
                 })
                 .then(r => r.json())
                 .then(data => {
                     this.uploading = false;
                     if (data.success) {
                         this.uploadSuccess = data.message;
                         this.selectedFile = null;
                         this.$refs.fileInput.value = '';
                         setTimeout(() => location.reload(), 1500);
                     } else {
                         this.uploadError = data.message || 'Upload failed.';
                     }
                 })
                 .catch(() => {
                     this.uploading = false;
                     this.uploadError = 'An unexpected error occurred. Please try again.';
                 });
             }
         }">
        <h3 class="text-sm font-semibold text-slate-800 mb-1">Firebase Service Account Credentials</h3>
        <p class="text-sm text-slate-500 mb-4">Upload the service account JSON file downloaded from Firebase Console → Project Settings → Service Accounts. Used for server-to-server FCM v1 API calls.</p>

        <!-- Current Status -->
        <div class="mb-4 p-3 rounded-xl border
            @php
                $uploadedAt = \App\Models\PlatformSetting::getValue('firebase_credentials_uploaded_at');
                $projectId = \App\Models\PlatformSetting::getValue('firebase_project_id');
            @endphp
            {{ $uploadedAt ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' }}">
            <div class="flex items-start gap-2">
                @if($uploadedAt)
                    <svg class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-xs font-semibold text-green-800">Credentials uploaded</p>
                        <p class="text-xs text-green-700 mt-0.5">
                            Project: <span class="font-mono font-semibold">{{ $projectId ?: 'Unknown' }}</span>
                            &nbsp;·&nbsp; Uploaded: {{ \Carbon\Carbon::parse($uploadedAt)->format('M j, Y H:i') }}
                        </p>
                    </div>
                @else
                    <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-xs font-semibold text-amber-800">No credentials uploaded</p>
                        <p class="text-xs text-amber-700 mt-0.5">Push notifications will not work until you upload a valid service account JSON file.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Upload Form -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="flex-1">
                <input type="file" accept=".json" x-ref="fileInput"
                       @@change="selectedFile = $event.target.files[0]; uploadError = ''; uploadSuccess = ''"
                       class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                <p x-show="selectedFile" x-text="'Selected: ' + (selectedFile ? selectedFile.name : '')" class="text-xs text-slate-500 mt-1"></p>
            </div>
            <button type="button" @@click="uploadCredentials()"
                    :disabled="uploading || !selectedFile"
                    class="flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
                <svg x-show="uploading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="uploading ? 'Uploading...' : 'Upload'"></span>
            </button>
        </div>

        <!-- Feedback messages -->
        <p x-show="uploadError" x-text="uploadError" class="mt-2 text-xs text-red-600"></p>
        <p x-show="uploadSuccess" x-text="uploadSuccess" class="mt-2 text-xs text-green-600"></p>
    </div>

    <!-- Web Push Configuration -->
    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
        <h3 class="text-sm font-semibold text-slate-800 mb-1">Web Push Configuration</h3>
        <p class="text-sm text-slate-500 mb-4">Public Firebase Web App credentials from Firebase Console → Project Settings → General → Your apps (Web app). These are not secret and are safe to save here.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Web API Key</label>
                <input type="text"
                       x-model="settings.firebase_web_api_key.value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="AIza...">
            </div>
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Auth Domain</label>
                <input type="text"
                       x-model="settings.firebase_auth_domain.value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="your-project.firebaseapp.com">
            </div>
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Messaging Sender ID</label>
                <input type="text"
                       x-model="settings.firebase_messaging_sender_id.value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="123456789012">
            </div>
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Web App ID</label>
                <input type="text"
                       x-model="settings.firebase_app_id.value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="1:123456789012:web:abc123">
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">VAPID Key <span class="text-slate-400 font-normal">(Web Push Certificate Public Key)</span></label>
                <input type="text"
                       x-model="settings.firebase_vapid_key.value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 font-mono text-xs font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="BLc2... (from Firebase Console → Cloud Messaging → Web Push certificates)">
                <p class="text-xs text-slate-400 mt-1">Find this in Firebase Console → Project Settings → Cloud Messaging → Web configuration → Web Push certificates → Key pair</p>
            </div>
        </div>
    </div>

    <!-- Test Push Notification -->
    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5"
         x-data="{
             testing: false,
             testMessage: '',
             testSuccess: null,
             permissionDenied: false,

             async runTest() {
                 this.testing = true;
                 this.testMessage = '';
                 this.testSuccess = null;
                 this.permissionDenied = false;

                 // Step 1: check if push is supported
                 if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                     this.testing = false;
                     this.testSuccess = false;
                     this.testMessage = 'Push notifications are not supported in this browser.';
                     return;
                 }

                 // Step 2: request/check notification permission
                 let permission = Notification.permission;
                 if (permission === 'default') {
                     permission = await Notification.requestPermission();
                 }
                 if (permission === 'denied') {
                     this.testing = false;
                     this.testSuccess = false;
                     this.permissionDenied = true;
                     this.testMessage = 'Notification permission is blocked. Please enable it in your browser settings, then try again.';
                     return;
                 }

                 // Step 3: ensure we have an FCM token — capture it now if missing
                 // window.__fcmInitialised is set by firebase-push.js once the token is saved
                 if (!window.__fcmInitialised) {
                     // Try to trigger token capture via the already-loaded Firebase SDK
                     if (window.__fcmGetToken) {
                         try {
                             await window.__fcmGetToken();
                         } catch(e) {
                             console.warn('[Test Push] Token capture failed:', e);
                         }
                         // Give the async save a moment
                         await new Promise(r => setTimeout(r, 1500));
                     } else {
                         this.testing = false;
                         this.testSuccess = false;
                         this.testMessage = 'Firebase SDK not loaded. Please save your Web Push Configuration settings and reload the page.';
                         return;
                     }
                 }

                 // Step 4: call the server-side test endpoint
                 try {
                     const resp = await fetch('{{ route('admin.settings.test-push') }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                             'Accept': 'application/json',
                         },
                         body: JSON.stringify({}),
                     });
                     const data = await resp.json();
                     this.testing = false;
                     this.testSuccess = data.success;

                     if (!data.success && data.needs_token) {
                         this.testMessage = 'Your FCM token could not be captured. Try reloading the page — the browser push permission prompt should appear automatically.';
                     } else {
                         this.testMessage = data.message || (data.success ? 'Notification sent!' : 'Send failed.');
                     }
                 } catch(e) {
                     this.testing = false;
                     this.testSuccess = false;
                     this.testMessage = 'Request failed: ' + e.message;
                 }
             }
         }">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-800 mb-1">Test Push Notification</h3>
                <p class="text-sm text-slate-500">Send a test push notification to yourself (the currently logged-in admin). Requires all settings above to be saved and browser permission granted.</p>
            </div>
            <button type="button" @@click="runTest()"
                    :disabled="testing"
                    class="flex shrink-0 items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
                <svg x-show="testing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <svg x-show="!testing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span x-text="testing ? 'Sending...' : 'Send Test'"></span>
            </button>
        </div>

        <!-- Result feedback -->
        <div x-show="testMessage" x-cloak class="mt-4 p-3 rounded-xl border"
             :class="testSuccess ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
            <div class="flex items-start gap-2">
                <svg x-show="testSuccess" class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="!testSuccess" class="w-4 h-4 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs" :class="testSuccess ? 'text-green-800' : 'text-red-800'" x-text="testMessage"></p>
            </div>
            <!-- Extra instructions when permission is denied -->
            <div x-show="permissionDenied" class="mt-2 pl-6 text-xs text-red-700 space-y-1">
                <p class="font-semibold">How to re-enable notifications in Chrome:</p>
                <p>1. Click the lock icon in your address bar → Site settings</p>
                <p>2. Find <strong>Notifications</strong> → change to <strong>Allow</strong></p>
                <p>3. Reload this page and try again</p>
            </div>
        </div>
    </div>
</div>
