<div class="space-y-6">
    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
        <div>
            <h3 class="text-sm font-extrabold text-slate-800">SMS Notifications</h3>
            <p class="mt-0.5 text-sm font-medium text-slate-500">Enable or disable outbound SMS.</p>
        </div>
        <label class="relative inline-flex cursor-pointer items-center">
            <input type="checkbox"
                   :checked="settings.sms_enabled.value == '1' || settings.sms_enabled.value === true"
                   @@change="settings.sms_enabled.value = $event.target.checked ? '1' : '0'"
                   class="sr-only peer">
            <div class="h-6 w-11 rounded-full bg-slate-200 transition after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-orange-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-100"></div>
        </label>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">SMS Provider</label>
            <select x-model="settings.sms_provider.value"
                    class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                <option value="arkesel">Arkesel</option>
                <option value="twilio">Twilio</option>
            </select>
            <p class="mt-2 text-sm font-medium text-slate-500">Choose the active provider for sending SMS from Parcelman.</p>
        </div>

        <div>
            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Sender ID</label>
            <input type="text"
                   x-model="settings.sms_sender_id.value"
                   class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                   placeholder="Enter sender ID">
            <p class="mt-2 text-sm font-medium text-slate-500">Used where the provider supports branded sender names.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border p-4 sm:p-5"
                 :class="settings.sms_provider.value === 'arkesel' ? 'border-orange-200 bg-orange-50/40 ring-1 ring-orange-100' : 'border-slate-200 bg-slate-50/60'">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Arkesel Configuration</h3>
                    <p class="mt-0.5 text-sm font-medium text-slate-500">API credentials for Arkesel SMS.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-extrabold"
                      :class="settings.sms_provider.value === 'arkesel' ? 'bg-orange-600 text-white' : 'bg-slate-200 text-slate-600'">
                    <span x-text="settings.sms_provider.value === 'arkesel' ? 'Active' : 'Inactive'"></span>
                </span>
            </div>

            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Arkesel API Key</label>
                <div x-data="{ show: false }" class="relative">
                    <input :type="show ? 'text' : 'password'"
                           x-model="settings.arkesel_api_key.value"
                           class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 pr-10 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                           placeholder="Enter Arkesel API key">
                    <button type="button" @@click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.826M9.363 5.365A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a9.98 9.98 0 01-2.087 3.368M6.21 6.21A9.98 9.98 0 002.458 12C3.732 16.057 7.523 19 12 19a9.96 9.96 0 004.79-1.21"/></svg>
                    </button>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border p-4 sm:p-5"
                 :class="settings.sms_provider.value === 'twilio' ? 'border-orange-200 bg-orange-50/40 ring-1 ring-orange-100' : 'border-slate-200 bg-slate-50/60'">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Twilio Configuration</h3>
                    <p class="mt-0.5 text-sm font-medium text-slate-500">SID, token, and sending phone number.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-extrabold"
                      :class="settings.sms_provider.value === 'twilio' ? 'bg-orange-600 text-white' : 'bg-slate-200 text-slate-600'">
                    <span x-text="settings.sms_provider.value === 'twilio' ? 'Active' : 'Inactive'"></span>
                </span>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Twilio SID</label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'"
                               x-model="settings.twilio_sid.value"
                               class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 pr-10 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                               placeholder="Enter Twilio SID">
                        <button type="button" @@click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="show" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.826M9.363 5.365A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a9.98 9.98 0 01-2.087 3.368M6.21 6.21A9.98 9.98 0 002.458 12C3.732 16.057 7.523 19 12 19a9.96 9.96 0 004.79-1.21"/></svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Twilio Token</label>
                    <div x-data="{ show: false }" class="relative">
                        <input :type="show ? 'text' : 'password'"
                               x-model="settings.twilio_token.value"
                               class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 pr-10 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                               placeholder="Enter Twilio token">
                        <button type="button" @@click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="show" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.828 2.826M9.363 5.365A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a9.98 9.98 0 01-2.087 3.368M6.21 6.21A9.98 9.98 0 002.458 12C3.732 16.057 7.523 19 12 19a9.96 9.96 0 004.79-1.21"/></svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Twilio Phone</label>
                    <input type="text"
                           x-model="settings.twilio_phone.value"
                           class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                           placeholder="Enter Twilio phone">
                </div>
            </div>
        </section>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5" x-data="{ testPhone: '' }">
        <h3 class="mb-4 text-sm font-extrabold text-slate-800">Test SMS</h3>
        <div class="flex flex-col gap-3 sm:flex-row">
            <input type="text"
                   x-model="testPhone"
                   class="min-w-0 flex-1 rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                   placeholder="Enter phone number (e.g., +233...)" >
            <button type="button"
                    @@click="testSms(testPhone)"
                    class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                Send Test
            </button>
        </div>
    </div>
</div>
