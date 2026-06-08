@php
    $driver = $settings['storage.driver']['value'] ?? 'local';
    $s3Configured = $tabData['s3_configured'] ?? false;
    $missingFields = $tabData['missing_s3_fields'] ?? [];
    $connectionStatus = $tabData['connection_status'] ?? [];
@endphp

<div class="space-y-6"
     x-data="{
        requiredS3Fields: [
            { key: 'storage.s3.access_key', label: 'Access key' },
            { key: 'storage.s3.secret_key', label: 'Secret key' },
            { key: 'storage.s3.bucket', label: 'Bucket' },
            { key: 'storage.s3.endpoint', label: 'Endpoint' },
            { key: 'storage.s3.region', label: 'Region' },
        ],
        missingS3Fields() {
            return this.requiredS3Fields
                .filter((field) => {
                    const setting = this.settings[field.key] || {};
                    return !String(setting.value || '').trim() && !setting.configured;
                })
                .map((field) => field.label);
        },
        s3Ready() {
            return this.missingS3Fields().length === 0;
        }
     }">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <button type="button"
                @@click="settings['storage.driver'].value = 'local'"
                class="rounded-2xl border p-4 text-left transition"
                :class="settings['storage.driver'].value === 'local' ? 'border-orange-200 bg-orange-50/60 ring-1 ring-orange-100' : 'border-slate-200 bg-slate-50/70 hover:border-slate-300'">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold text-slate-900">Local Public Storage</p>
                    <p class="mt-1 text-sm font-medium leading-5 text-slate-500">Store uploads in storage/app/public and serve them through /storage.</p>
                </div>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                      :class="settings['storage.driver'].value === 'local' ? 'bg-orange-600 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
        </button>

        <button type="button"
                @@click="settings['storage.driver'].value = 's3'"
                class="rounded-2xl border p-4 text-left transition lg:col-span-2"
                :class="settings['storage.driver'].value === 's3' ? 'border-orange-200 bg-orange-50/60 ring-1 ring-orange-100' : 'border-slate-200 bg-slate-50/70 hover:border-slate-300'">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold text-slate-900">S3 / Storj Bucket</p>
                    <p class="mt-1 text-sm font-medium leading-5 text-slate-500">Use a private S3-compatible bucket and generate signed image links.</p>
                </div>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                      :class="settings['storage.driver'].value === 's3' ? 'bg-orange-600 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
        </button>
    </div>

    <input type="hidden" x-model="settings['storage.driver'].value">

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-black uppercase tracking-wide text-slate-400">Active Driver</p>
            <p class="mt-2 text-2xl font-black text-slate-950" x-text="settings['storage.driver'].value === 's3' ? 'S3 / Storj' : 'Local'"></p>
            <p class="mt-2 text-sm font-semibold text-slate-500">Current setting used for new uploads.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-black uppercase tracking-wide text-slate-400">S3 Status</p>
            <p class="mt-2 text-2xl font-black" :class="s3Ready() ? 'text-emerald-700' : 'text-amber-700'" x-text="s3Ready() ? 'Ready' : 'Incomplete'"></p>
            <p class="mt-2 text-sm font-semibold text-slate-500" x-text="s3Ready() ? 'Signing can generate valid private URLs.' : 'Missing: ' + missingS3Fields().join(', ')"></p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-black uppercase tracking-wide text-slate-400">Local Path</p>
            <p class="mt-2 break-all font-mono text-sm font-black text-slate-800">{{ $tabData['local_path'] ?? storage_path('app/public') }}</p>
            <p class="mt-2 text-sm font-semibold text-slate-500">Local files are served by the public storage link.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-black uppercase tracking-wide text-slate-400">Connection</p>
            <p class="mt-2 text-2xl font-black {{ ($connectionStatus['reachable'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">
                {{ ($connectionStatus['reachable'] ?? false) ? 'Reachable' : 'Needs Check' }}
            </p>
            <p class="mt-2 text-sm font-semibold leading-5 text-slate-500">{{ $connectionStatus['message'] ?? 'Storage connection has not been checked.' }}</p>
        </div>
    </div>

    @if (($connectionStatus['driver'] ?? null) === 's3')
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-6">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">Bucket</p>
                    <p class="mt-1 break-all font-mono font-bold text-slate-800">{{ $connectionStatus['bucket'] ?: 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">Endpoint</p>
                    <p class="mt-1 break-all font-mono font-bold text-slate-800">{{ $connectionStatus['endpoint'] ?: 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">Region</p>
                    <p class="mt-1 font-mono font-bold text-slate-800">{{ $connectionStatus['region'] ?: 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">Prefix</p>
                    <p class="mt-1 font-mono font-bold text-slate-800">{{ $connectionStatus['prefix'] ?: 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">Credentials</p>
                    <p class="mt-1 font-bold text-slate-800">
                        {{ ($connectionStatus['access_key_configured'] ?? false) && ($connectionStatus['secret_key_configured'] ?? false) ? 'Saved' : 'Incomplete' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">URL Expiry</p>
                    <p class="mt-1 font-mono font-bold text-slate-800">{{ $connectionStatus['signed_url_expiry'] ?? 60 }} min</p>
                </div>
            </div>
        </div>
    @endif

    <template x-if="settings['storage.driver'].value === 's3' && !s3Ready()">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
            <div class="flex gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                <div>
                    <p class="text-sm font-extrabold">S3 / Storj is selected but not fully configured.</p>
                    <p class="mt-1 text-sm font-semibold leading-5">Signed image links will fail until the missing fields are saved. This is what caused the malformed `X-Amz-Credential` URL.</p>
                </div>
            </div>
        </div>
    </template>

    <section class="rounded-2xl border p-4 sm:p-5"
             :class="settings['storage.driver'].value === 's3' ? 'border-orange-200 bg-orange-50/40 ring-1 ring-orange-100' : 'border-slate-200 bg-slate-50/70'">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">S3 / Storj Configuration</h3>
                <p class="mt-1 text-sm font-semibold text-slate-500">For Storj, use the gateway endpoint and `us-east-1` region unless your provider says otherwise.</p>
            </div>
            <span class="inline-flex w-fit items-center rounded-xl px-3 py-2 text-xs font-extrabold"
                  :class="s3Ready() ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-100'">
                <span x-text="s3Ready() ? 'Configured' : 'Missing fields'"></span>
            </span>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Access Key</label>
                <div x-data="{ show: false }" class="relative">
                    <input :type="show ? 'text' : 'password'"
                           x-model="settings['storage.s3.access_key'].value"
                           class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 pr-10 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                           placeholder="Enter S3 access key">
                    <button type="button" @@click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/></svg>
                        <svg x-show="show" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 0 0 2.828 2.826M9.363 5.365A9.953 9.953 0 0 1 12 5c4.478 0 8.268 2.943 9.543 7a9.98 9.98 0 0 1-2.087 3.368M6.21 6.21A9.98 9.98 0 0 0 2.458 12C3.732 16.057 7.523 19 12 19a9.96 9.96 0 0 0 4.79-1.21"/></svg>
                    </button>
                </div>
                <p class="mt-2 text-sm font-medium leading-5 text-slate-500">This must not be blank when S3 / Storj is active.</p>
            </div>

            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Secret Key</label>
                <div x-data="{ show: false }" class="relative">
                    <input :type="show ? 'text' : 'password'"
                           x-model="settings['storage.s3.secret_key'].value"
                           class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 pr-10 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                           placeholder="Enter S3 secret key">
                    <button type="button" @@click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/></svg>
                        <svg x-show="show" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 0 0 2.828 2.826M9.363 5.365A9.953 9.953 0 0 1 12 5c4.478 0 8.268 2.943 9.543 7a9.98 9.98 0 0 1-2.087 3.368M6.21 6.21A9.98 9.98 0 0 0 2.458 12C3.732 16.057 7.523 19 12 19a9.96 9.96 0 0 0 4.79-1.21"/></svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Bucket</label>
                <input type="text"
                       x-model="settings['storage.s3.bucket'].value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="shaxi">
            </div>

            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Endpoint URL</label>
                <input type="text"
                       x-model="settings['storage.s3.endpoint'].value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="https://gateway.storjshare.io">
            </div>

            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Region</label>
                <input type="text"
                       x-model="settings['storage.s3.region'].value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="us-east-1">
            </div>

            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Folder Prefix</label>
                <input type="text"
                       x-model="settings['storage.s3.env'].value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                       placeholder="demo">
                <p class="mt-2 text-sm font-medium leading-5 text-slate-500">Example upload path: <span class="font-mono">demo/shipments/...</span></p>
            </div>

            <div>
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Signed URL Expiry</label>
                <input type="number"
                       min="1"
                       x-model="settings['storage.s3.signed_url_expiry'].value"
                       class="w-full rounded-xl border-2 border-slate-200 bg-white px-3.5 py-3 text-sm font-semibold text-slate-900 placeholder-slate-400 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                <p class="mt-2 text-sm font-medium leading-5 text-slate-500">Minutes before private S3 / Storj links expire.</p>
            </div>
        </div>
    </section>
</div>
