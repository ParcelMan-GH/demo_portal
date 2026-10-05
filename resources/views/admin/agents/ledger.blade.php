@extends('warehouse.layouts.app')

@section('title', $pageTitle ?? 'Commission Ledger')
@section('page-title', $pageTitle ?? 'Commission Ledger')

@section('content')
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" x-data="commissionLedger()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">{{ $pageTitle }}</h1>
            <p class="text-slate-500 text-sm mt-1">{{ $pageSubtitle }}</p>
        </div>
        <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-4 py-2 shadow-sm">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="font-bold text-slate-700">{{ \Carbon\Carbon::parse($currentDate)->format('F j, Y') }}</span>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500 uppercase tracking-wider text-[10px] font-extrabold">
                    <tr>
                        <th class="px-6 py-4">Agent Name</th>
                        <th class="px-6 py-4">Task Quota</th>
                        <th class="px-6 py-4">Collection & Tier</th>
                        <th class="px-6 py-4 text-center">Pickup Codes</th>
                        <th class="px-6 py-4 text-center">Payout Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($ledgers as $ledger)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $ledger['agent_name'] }}</td>
                            
                            {{-- Quota Progress Bar --}}
                            <td class="px-6 py-4 min-w-[200px]">
                                <div class="flex justify-between items-end mb-1">
                                    <span class="text-xs font-semibold text-slate-600">
                                        {{ $ledger['completed_tasks'] }} / {{ $ledger['assigned_tasks'] }} Done
                                    </span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-orange-500 h-2 rounded-full transition-all" 
                                         style="width: {{ $ledger['assigned_tasks'] > 0 ? ($ledger['completed_tasks'] / $ledger['assigned_tasks']) * 100 : 0 }}%">
                                    </div>
                                </div>
                            </td>

                            {{-- Financials --}}
                            <td class="px-6 py-4">
                                <div class="text-sm font-black text-emerald-600">₵ {{ number_format($ledger['collected_amount'], 2) }}</div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">Earned: ₵ {{ number_format($ledger['earned_commission'], 2) }}</div>
                            </td>

                            {{--
                                Pickup-code confirmation.

                                A day's commission does not unlock until every parcel
                                that requires a code has been confirmed. The split
                                between agent-confirmed and admin-confirmed is shown
                                deliberately: if the desk confirms everything on the
                                agents' behalf, that has to look different from agents
                                doing the work themselves.
                            --}}
                            <td class="px-6 py-4 text-center min-w-[190px]">
                                @if($ledger['codes_required'] === 0)
                                    <span class="text-xs font-medium text-slate-400 italic">
                                        {{ $ledger['codes_exempt'] > 0 ? $ledger['codes_exempt'].' exempt' : 'None yet' }}
                                    </span>
                                @else
                                    <div class="text-sm font-black {{ $ledger['codes_pending'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                        {{ $ledger['codes_confirmed'] }} / {{ $ledger['codes_required'] }}
                                    </div>
                                    <div class="flex flex-wrap items-center justify-center gap-1 mt-1.5">
                                        @if($ledger['codes_confirmed_by_agent'] > 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ $ledger['codes_confirmed_by_agent'] }} agent
                                            </span>
                                        @endif
                                        @if($ledger['codes_confirmed_by_admin'] > 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                                {{ $ledger['codes_confirmed_by_admin'] }} admin
                                            </span>
                                        @endif
                                        @if($ledger['codes_pending'] > 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                {{ $ledger['codes_pending'] }} to confirm
                                            </span>
                                        @endif
                                        @if($ledger['codes_exempt'] > 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200"
                                                  title="Cancelled, returned, or unreachable after repeated attempts">
                                                {{ $ledger['codes_exempt'] }} exempt
                                            </span>
                                        @endif
                                    </div>

                                    @if($ledger['codes_pending'] > 0)
                                        <button type="button"
                                                @click="openCodeModal({{ $ledger['id'] }}, '{{ addslashes($ledger['agent_name']) }}', @js($ledger['awaiting']))"
                                                class="mt-2 text-xs font-bold text-sky-600 hover:text-sky-700 bg-sky-50 hover:bg-sky-100 px-3 py-1.5 rounded-lg transition-colors">
                                            Enter a code
                                        </button>
                                    @endif
                                @endif
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-6 py-4 text-center" data-status-cell="{{ $ledger['id'] }}">
                                @if($ledger['has_cleared_list'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Cleared
                                    </span>
                                @elseif($ledger['is_unlocked'])
                                    {{-- The reason is shown on hover: an override with the
                                         why recorded is auditable, one without is just a green light. --}}
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                          title="Overridden by {{ $ledger['overridden_by'] ?? 'an admin' }}{{ $ledger['override_reason'] ? ' — '.$ledger['override_reason'] : '' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        Override Active
                                    </span>
                                @else
                                    <span data-badge class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        Locked
                                    </span>
                                 @endif

                             {{--
                                 The unlocked badge lives in the DOM as a TEMPLATE rather than
                                 being rebuilt in JavaScript: this markup already exists twice
                                 in this file, and a third copy inside a JS string is the one
                                 that drifts silently when someone restyles a badge.

                                 It must sit INSIDE this status cell. A template element
                                 placed between two cells is invalid table markup, and
                                 browsers hoist it out of the row, taking the badge with it.
                             --}}
                             <template data-unlocked-template>
                                <span data-badge class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>

                                    Override Active
                                </span>
                            </template>
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-right" data-action-cell="{{ $ledger['id'] }}">
                                @if(!$ledger['has_cleared_list'] && !$ledger['is_unlocked'])
                                    {{-- The override is the force path, so say plainly when it
                                         would release money with parcels still unconfirmed.
                                         That count is written to the audit log on submit. --}}
                                    @if($ledger['codes_pending'] > 0)
                                        <p class="text-[10px] font-bold text-amber-600 mb-1">
                                            {{ $ledger['codes_pending'] }} code(s) unconfirmed
                                        </p>
                                    @endif
                                    <button @click="openOverrideModal({{ $ledger['id'] }}, '{{ addslashes($ledger['agent_name']) }}')" 
                                            class="text-xs font-bold text-orange-600 hover:text-orange-700 bg-orange-50 hover:bg-orange-100 px-3 py-1.5 rounded-lg transition-colors">
                                        Override Lock
                                    </button>
                                @else
                                    <span data-no-action class="text-xs font-medium text-slate-300 italic">No Action Needed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <p class="text-sm font-bold">No agent quotas tracked for this date.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Override Modal --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="modalOpen = false" x-transition.opacity></div>
        
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 overflow-hidden" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 translate-y-4" 
             x-transition:enter-end="opacity-100 translate-y-0">
            
            <h3 class="text-lg font-black text-slate-900 mb-1">Authorize Manual Override</h3>
            <p class="text-xs font-medium text-slate-500 mb-5">You are unlocking commissions for <span class="text-orange-600 font-bold" x-text="selectedAgentName"></span>.</p>
            
            {{--
    The action is built from the NAMED route, then the quota id is substituted.

    It used to be hard-coded as `/admin/agents/ledger/${selectedQuotaId}/override`,
    which 404s: the route group carries an `admin.` NAME prefix but no `admin/`
    URI prefix, so the real path is `/agents/ledger/{quota}/override`. Any
    hand-written URL here is a guess about the prefix, and it was the wrong one.

    Substituting in JS rather than in Blade because the id is only known once a
    row's button is clicked.
--}}
<form :action="'{{ route('admin.agents.ledger.override', ['quota' => '__QUOTA_ID__']) }}'.replace('__QUOTA_ID__', selectedQuotaId)" @submit.prevent="submitOverride($event)" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block text-[11px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">Reason for Override (Required)</label>
                    <textarea name="override_reason" required minlength="5" rows="3" 
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500" 
                              placeholder="e.g., Excused due to medical emergency..."></textarea>
                </div>
                
                <div x-show="overrideError" x-cloak class="mb-4 rounded-xl bg-rose-50 border border-rose-200 p-3 text-xs font-medium text-rose-700" x-text="overrideError"></div>

                <div class="flex gap-3 justify-end">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" :disabled="savingOverride"
                            class="px-4 py-2 text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 shadow-lg shadow-orange-500/30 rounded-xl transition-all disabled:opacity-60"
                            x-text="savingOverride ? 'Authorizing…' : 'Authorize & Unlock'"></button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

    {{--
        Pickup-code entry.

        The desk sees the code the recipient was texted, which is exactly why this
        modal exists: the agent may not have been able to get it. Confirming here is
        recorded as an ADMIN confirmation, never as the agent's own, so the ledger
        keeps showing the difference between work the agents did and work the desk
        did for them.
    --}}
    <div x-show="codeModalOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="codeModalOpen = false" x-transition.opacity></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-lg font-black text-slate-900">Confirm a pickup code</h3>
                <p class="text-xs text-slate-500 mt-0.5" x-text="'On behalf of ' + codeAgentName"></p>
            </div>

            <form :action="'{{ route('admin.agents.ledger.confirm-code', ['quota' => '__QUOTA_ID__']) }}'.replace('__QUOTA_ID__', codeQuotaId)"
                  @submit.prevent="submitCode($event)" method="POST">
                @csrf

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Parcel</label>
                        <select name="shipment_item_id" x-model="codeItemId" required
                                class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                            <template x-for="p in codePending" :key="p.shipment_item_id">
                                <option :value="p.shipment_item_id"
                                        x-text="(p.tracking_code || ('#' + p.shipment_item_id)) + (p.recipient_name ? ' — ' + p.recipient_name : '')"></option>
                            </template>
                        </select>
                    </div>

                    {{--
                        The issued code, shown so the desk can read it back to whoever
                        they are on the phone with. It is only in this payload because
                        the caller is an admin — the agent's endpoint never sends it.
                    --}}
                    <div x-show="selectedCode()" class="flex items-center justify-between rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Code issued</span>
                        <span class="text-lg font-black tracking-[0.2em] text-slate-900" x-text="selectedCode()"></span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Code from the customer</label>
                        <input type="text" name="code" x-model="codeValue" required inputmode="numeric" autocomplete="off"
                               placeholder="e.g. 4821"
                               class="w-full px-3 py-2.5 text-lg font-bold tracking-[0.2em] text-center border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Where did it come from?</label>
                        <select name="source" x-model="codeSource"
                                class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                            <option value="customer">The customer</option>
                            <option value="hub_agent">The hub agent</option>
                        </select>
                    </div>

                    <p x-show="codeError" x-text="codeError" class="text-xs font-bold text-red-600"></p>
                </div>

                <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3">
                    <button type="button" @click="codeModalOpen = false"
                            class="px-4 py-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="codeSaving"
                            class="px-5 py-2 text-sm font-bold text-white bg-sky-600 hover:bg-sky-700 rounded-xl transition-colors disabled:opacity-50">
                        <span x-show="!codeSaving">Confirm code</span>
                        <span x-show="codeSaving">Confirming…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

@push('scripts')
<script>
function commissionLedger() {
    return {
        modalOpen: false,
        selectedQuotaId: null,
        selectedAgentName: '',
        
        savingOverride: false,
        overrideError: '',

        // Pickup-code confirmation
        codeModalOpen: false,
        codeQuotaId: null,
        codeAgentName: '',
        codePending: [],
        codeItemId: null,
        codeValue: '',
        codeSource: 'customer',
        codeSaving: false,
        codeError: '',

        openCodeModal(id, name, pending) {
            this.codeQuotaId = id;
            this.codeAgentName = name;
            this.codePending = Array.isArray(pending) ? pending : [];
            this.codeItemId = this.codePending[0]?.shipment_item_id ?? null;
            this.codeValue = '';
            this.codeSource = 'customer';
            this.codeError = '';
            this.codeModalOpen = true;
        },

        /**
         * The code issued for the selected parcel.
         *
         * Present only because this page is admin-only — the agent's own endpoint
         * never sends the code, or the agent could simply read it back and retype it.
         */
        selectedCode() {
            const row = this.codePending.find(p => String(p.shipment_item_id) === String(this.codeItemId));
            return row?.pickup_code || '';
        },

        async submitCode(event) {
            const form = event.target;
            const quotaId = this.codeQuotaId;

            this.codeSaving = true;
            this.codeError = '';

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const post = () => fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            try {
                let res = await post();
                if (res.status === 419) res = await post();

                const json = await res.json().catch(() => ({}));

                if (!res.ok) {
                    // The server says exactly why — the code did not match, the
                    // attempts are exhausted, or the parcel has no code yet. Pass
                    // that through rather than a generic failure.
                    this.codeError = json.message || json.errors?.code?.[0] || 'Could not confirm that code.';
                    return;
                }

                /*
                 * Reloaded rather than patched in place.
                 *
                 * The override badge could be cloned from a <template> because its
                 * markup already existed in the row. This cell has no such source:
                 * the counts, the agent/admin split and the button are all
                 * server-rendered, and rebuilding them here would mean a second copy
                 * of that markup — the very drift the template comment warns about.
                 * A reload is the honest way to get the numbers right.
                 */
                window.location.reload();
            } catch (e) {
                this.codeError = 'Could not reach the server. Check your connection and try again.';
            } finally {
                this.codeSaving = false;
            }
        },

        openOverrideModal(id, name) {
            this.selectedQuotaId = id;
            this.selectedAgentName = name;
            this.overrideError = '';
            this.modalOpen = true;
        },

        /*
         * Submit over fetch and update the row in place.
         *
         * The normal form submit worked, but reloaded the entire ledger — on a
         * long list that drops the reader back at the top and loses their place,
         * for a change to one cell.
         *
         * The badge is cloned from the <template> already in the cell rather than
         * built here, so the markup keeps one home.
         */
        async submitOverride(event) {
            const form = event.target;
            const reason = (new FormData(form).get('override_reason') || '').toString().trim();
            const quotaId = this.selectedQuotaId;

            if (reason.length < 5) {
                this.overrideError = 'Give a reason of at least 5 characters — it is recorded against the override.';
                return;
            }

            this.savingOverride = true;
            this.overrideError = '';

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const post = () => fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            try {
                let res = await post();
                // Session rolled over under an open tab: re-read the token once.
                if (res.status === 419) res = await post();

                const json = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const errs = json.errors || {};
                    this.overrideError = errs.override_reason?.[0] || json.message || 'Could not authorize the override.';
                    return;
                }

                this.applyOverrideToRow(quotaId);
                this.modalOpen = false;
                form.reset();
            } catch (e) {
                this.overrideError = 'Could not reach the server. Check your connection and try again.';
            } finally {
                this.savingOverride = false;
            }
        },

        applyOverrideToRow(quotaId) {
            const cell = document.querySelector('[data-status-cell="' + quotaId + '"]');
            const template = cell?.querySelector('[data-unlocked-template]');
            const existing = cell?.querySelector('[data-badge]');

            if (template && existing) existing.replaceWith(template.content.cloneNode(true));

            const actionCell = document.querySelector('[data-action-cell="' + quotaId + '"]');
            if (actionCell) {
                // The button that opened this modal must stop being clickable —
                // there is nothing left to override on this row.
                actionCell.innerHTML = '<span class="text-xs font-medium text-slate-300 italic">No Action Needed</span>';
            }
        }
    }
}
</script>
@endpush