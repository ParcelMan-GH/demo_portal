@extends('admin.layouts.app')

@section('title', 'Commission Rules')
@section('breadcrumb-parent', 'Operations')
@section('breadcrumb-current', 'Commission Rules')

@section('content')
{{--
    Commission bands.

    This screen edits the same rows `CommissionTier::findTierForAmount()` reads on
    every earnings calculation, so changes here are live the moment they save —
    there is no draft state and no "apply" step. That is intentional (an admin
    fixing a wrong rate wants it fixed now), but it does mean a mistake pays agents
    the wrong money immediately, which is why the server refuses overlapping
    ranges rather than accepting them and resolving later.
--}}
<div x-data="commissionRules()" x-init="load()" class="space-y-6">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Commission Rules</h1>
            <p class="mt-1 text-sm text-slate-500">
                The payout ladder agents earn against. Bands must not overlap.
            </p>
        </div>
        <button type="button" @click="openCreate()"
                class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Band
        </button>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <template x-for="tile in tiles" :key="tile.label">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-text="tile.label"></div>
                <div class="mt-1 text-2xl font-bold text-slate-800" x-text="tile.value"></div>
            </div>
        </template>
    </div>

    {{-- A gap is legal but silent: that range pays nothing. Report, do not block. --}}
    <template x-if="summary.gaps && summary.gaps.length">
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4">
            <div class="text-sm font-semibold text-amber-800">
                <span x-text="summary.gaps.length"></span> range(s) pay nothing
            </div>
            <ul class="mt-2 space-y-1 text-sm text-amber-700">
                <template x-for="(g, i) in summary.gaps" :key="i">
                    <li>
                        No band covers <span class="font-semibold" x-text="g.after"></span> –
                        <span class="font-semibold" x-text="g.before"></span>.
                        Collections landing there earn nothing.
                    </li>
                </template>
            </ul>
        </div>
    </template>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Lower</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Upper</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Commission</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <template x-if="loading">
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">Loading…</td></tr>
                </template>
                <template x-if="!loading && !bands.length">
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">No bands configured.</td></tr>
                </template>
                <template x-for="band in bands" :key="band.id">
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-sm text-slate-700" x-text="money(band.min)"></td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            <span x-show="band.max === null" class="font-semibold text-slate-500">No limit</span>
                            <span x-show="band.max !== null" x-text="money(band.max)"></span>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-800" x-text="money(band.amount)"></td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                  :class="band.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                  x-text="band.is_active ? 'Active' : 'Inactive'"></span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" @click="openEdit(band)"
                                    class="text-sm font-semibold text-orange-600 hover:text-orange-700">Edit</button>
                            <button type="button" @click="openDelete(band)"
                                    class="ml-4 text-sm font-semibold text-rose-600 hover:text-rose-700">Delete</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{--
        Hub agent commission.

        A different shape of money from the ladder above: a flat amount per parcel,
        paid once when a parcel is checked in at the hub (inbound) and once when it
        is released — counter handover or doorstep dispatch (outbound). There is no
        amount collected to band, so this is a switch and two rates rather than
        tiers. Off by default: nothing is paid until an admin turns it on.
    --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-sm font-bold text-slate-800">Hub agent commission</h2>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">
                    A flat amount per parcel. Inbound is paid when a parcel is scanned in at the hub;
                    outbound is paid once when it leaves, by counter handover or doorstep dispatch.
                    Disabled means nothing is paid.
                </p>
            </div>
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input type="checkbox" x-model="hubAgent.enabled" class="peer sr-only">
                <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500
                             after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full
                             after:bg-white after:transition-all peer-checked:after:translate-x-5"></span>
                <span class="text-sm font-semibold text-slate-700" x-text="hubAgent.enabled ? 'Enabled' : 'Disabled'"></span>
            </label>
        </div>

        <div class="grid grid-cols-1 gap-4 px-5 py-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-slate-600">Inbound rate (GH₵ per parcel)</label>
                <input type="number" step="0.01" min="0" x-model="hubAgent.inbound_rate"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-400">
                    Paid when a parcel is checked in at the hub. Defaults to 0.50.
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600">Outbound rate (GH₵ per parcel)</label>
                <input type="number" step="0.01" min="0" x-model="hubAgent.outbound_rate"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-400">
                    Paid when a parcel is released from the hub. Defaults to 1.00.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-5 py-4">
            <span x-show="hubAgent.saved" x-cloak class="text-sm font-semibold text-emerald-600">Saved.</span>
            <span x-show="hubAgent.error" x-cloak class="text-sm text-rose-600" x-text="hubAgent.error"></span>
            <button type="button" @click="saveHubAgent()" :disabled="hubAgent.saving"
                    class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                    x-text="hubAgent.saving ? 'Saving…' : 'Save'"></button>
        </div>
    </div>
</div>

{{-- Create / Edit --}}
<div x-data x-show="$store.modal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" @click="$store.modal.close()"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-bold text-slate-800" x-text="$store.modal.title"></h2>

        <div class="mt-4 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-600">Lower threshold (GH₵)</label>
                <input type="number" step="0.01" min="0" x-model="$store.modal.min"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600">Upper threshold (GH₵)</label>
                <input type="number" step="0.01" min="0" x-model="$store.modal.max"
                       placeholder="Leave blank for no limit"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-400">
                    Blank means no upper limit — this band then covers every amount above its lower
                    threshold, which is what makes the ladder a ceiling.
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600">Commission (GH₵)</label>
                <input type="number" step="0.01" min="0" x-model="$store.modal.amount"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" x-model="$store.modal.isActive" class="rounded border-slate-300">
                Active (inactive bands are ignored by the calculation)
            </label>
        </div>

        <div x-show="$store.modal.error" x-cloak class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700"
             x-text="$store.modal.error"></div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" @click="$store.modal.close()"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Cancel</button>
            <button type="button" @click="$store.modal.save()" :disabled="$store.modal.saving"
                    class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                    x-text="$store.modal.saving ? 'Saving…' : 'Save Band'"></button>
        </div>
    </div>
</div>

{{-- Delete confirm --}}
<div x-data x-show="$store.confirm.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" @click="$store.confirm.close()"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-bold text-slate-800">Delete band?</h2>
        <p class="mt-2 text-sm text-slate-600">
            Collections in <span class="font-semibold" x-text="$store.confirm.label"></span> will earn
            nothing until another band covers them.
        </p>
        <div x-show="$store.confirm.error" x-cloak class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700"
             x-text="$store.confirm.error"></div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" @click="$store.confirm.close()"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Cancel</button>
            <button type="button" @click="$store.confirm.remove()"
                    class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Delete</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/*
 * Inline rather than a Vite module: this page has no build dependency, so it can
 * be iterated without a rebuild and cannot break because the asset pipeline
 * missed it.
 */
document.addEventListener('alpine:init', () => {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    /*
     * A 419 means the session rolled over under an open tab. Re-read the token
     * once and retry; only surface it if the retry fails too, so a long-lived
     * admin page does not turn a routine session refresh into a lost edit.
     */
    const send = async (url, method, body) => {
        const doFetch = () => fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });

        let res = await doFetch();
        if (res.status === 419) res = await doFetch();

        const json = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, json };
    };

    Alpine.data('commissionRules', () => ({
        bands: [], summary: { gaps: [] }, loading: true,
        // The hub agent's switch and per-parcel rates. Defaults match the server's
        // so the form is sensible before the first load completes.
        hubAgent: { enabled: false, inbound_rate: 0.5, outbound_rate: 1.0, saving: false, saved: false, error: '' },

        get tiles() {
            return [
                { label: 'Bands', value: this.summary.total ?? 0 },
                { label: 'Active', value: this.summary.active ?? 0 },
                { label: 'Lowest', value: this.money(this.summary.lowest) },
                { label: 'Highest', value: this.money(this.summary.highest) },
            ];
        },

        money(v) {
            if (v === null || v === undefined || v === '') return '—';
            return Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async load() {
            this.loading = true;
            const { json } = await send(@json(route('admin.commissions.rules.data')), 'GET');
            this.bands = json.data?.bands ?? [];
            this.summary = json.data?.summary ?? { gaps: [] };

            const hubAgent = json.data?.hub_agent;
            if (hubAgent) {
                this.hubAgent.enabled = !!hubAgent.enabled;
                this.hubAgent.inbound_rate = hubAgent.inbound_rate ?? 0.5;
                this.hubAgent.outbound_rate = hubAgent.outbound_rate ?? 1.0;
            }

            this.loading = false;
        },

        openCreate() {
            Alpine.store('modal').open = true;
            Alpine.store('modal').title = 'Add Band';
            Alpine.store('modal').id = null;
            Alpine.store('modal').min = '';
            Alpine.store('modal').max = '';
            Alpine.store('modal').amount = '';
            Alpine.store('modal').isActive = true;
            Alpine.store('modal').error = '';
            Alpine.store('modal').refresh = () => this.load();
        },

        openEdit(band) {
            const m = Alpine.store('modal');
            m.open = true;
            m.title = 'Edit Band';
            m.id = band.id;
            m.min = band.min;
            m.max = band.max === null ? '' : band.max;
            m.amount = band.amount;
            m.isActive = band.is_active;
            m.error = '';
            m.refresh = () => this.load();
        },

        openDelete(band) {
            const c = Alpine.store('confirm');
            c.open = true;
            c.id = band.id;
            c.label = band.max === null
                ? `${this.money(band.min)} and above`
                : `${this.money(band.min)} – ${this.money(band.max)}`;
            c.error = '';
            c.refresh = () => this.load();
        },

        /*
         * Save the hub agent's switch and rates. Separate endpoint from the
         * ladder, because it is a different shape of setting — but the same
         * page, the same round-trip and the same error handling as the bands.
         */
        async saveHubAgent() {
            this.hubAgent.saving = true;
            this.hubAgent.error = '';
            this.hubAgent.saved = false;

            const payload = {
                enabled: this.hubAgent.enabled,
                inbound_rate: this.hubAgent.inbound_rate === '' ? 0 : Number(this.hubAgent.inbound_rate),
                outbound_rate: this.hubAgent.outbound_rate === '' ? 0 : Number(this.hubAgent.outbound_rate),
            };

            const { ok, json } = await send(@json(route('admin.commissions.rules.hub-agent')), 'POST', payload);
            this.hubAgent.saving = false;

            if (! ok) {
                const errs = json.errors || {};
                this.hubAgent.error = errs.inbound_rate?.[0] || errs.outbound_rate?.[0]
                    || errs.enabled?.[0] || json.message || 'Could not save.';
                return;
            }

            // Echo the server's own values back, so what the form shows is what
            // was actually persisted rather than what was typed.
            const hubAgent = json.data?.hub_agent;
            if (hubAgent) {
                this.hubAgent.enabled = !!hubAgent.enabled;
                this.hubAgent.inbound_rate = hubAgent.inbound_rate ?? this.hubAgent.inbound_rate;
                this.hubAgent.outbound_rate = hubAgent.outbound_rate ?? this.hubAgent.outbound_rate;
            }

            this.hubAgent.saved = true;
            setTimeout(() => { this.hubAgent.saved = false; }, 3000);
        },
    }));

    Alpine.store('modal', {
        open: false, saving: false, error: '', title: '', id: null,
        min: '', max: '', amount: '', isActive: true, refresh: () => {},
        close() { this.open = false; },
        async save() {
            this.saving = true; this.error = '';
            const payload = {
                min_collection: this.min === '' ? null : Number(this.min),
                max_collection: this.max === '' ? null : Number(this.max),
                payout_amount: this.amount === '' ? null : Number(this.amount),
                is_active: this.isActive,
            };
            const url = this.id
                ? @json(url('/admin/commissions/rules')) + '/' + this.id
                : @json(route('admin.commissions.rules.store'));
            const { ok, json } = await send(url, this.id ? 'PUT' : 'POST', payload);
            this.saving = false;

            if (! ok) {
                // Show the server's own message: it names the band that clashed.
                const errs = json.errors || {};
                this.error = errs.min_collection?.[0] || errs.max_collection?.[0]
                    || errs.payout_amount?.[0] || json.message || 'Could not save.';
                return;
            }
            this.open = false;
            this.refresh();
        },
    });

    Alpine.store('confirm', {
        open: false, id: null, label: '', error: '', refresh: () => {},
        close() { this.open = false; },
        async remove() {
            this.error = '';
            const url = @json(url('/admin/commissions/rules')) + '/' + this.id;
            const { ok, json } = await send(url, 'DELETE');
            if (! ok) { this.error = json.message || 'Could not delete.'; return; }
            this.open = false;
            this.refresh();
        },
    });
});
</script>
@endpush
