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

                            {{-- Status Badge --}}
                            <td class="px-6 py-4 text-center">
                                @if($ledger['has_cleared_list'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Cleared
                                    </span>
                                @elseif($ledger['is_unlocked'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200" title="Overridden by {{ $ledger['overridden_by'] }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        Override Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        Locked
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-right">
                                @if(!$ledger['has_cleared_list'] && !$ledger['is_unlocked'])
                                    <button @click="openOverrideModal({{ $ledger['id'] }}, '{{ addslashes($ledger['agent_name']) }}')" 
                                            class="text-xs font-bold text-orange-600 hover:text-orange-700 bg-orange-50 hover:bg-orange-100 px-3 py-1.5 rounded-lg transition-colors">
                                        Override Lock
                                    </button>
                                @else
                                    <span class="text-xs font-medium text-slate-300 italic">No Action Needed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
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
            
            <form :action="`/admin/agents/ledger/${selectedQuotaId}/override`" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block text-[11px] font-extrabold text-slate-400 uppercase tracking-wider mb-2">Reason for Override (Required)</label>
                    <textarea name="override_reason" required minlength="5" rows="3" 
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500" 
                              placeholder="e.g., Excused due to medical emergency..."></textarea>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-orange-600 hover:bg-orange-700 shadow-lg shadow-orange-500/30 rounded-xl transition-all">Authorize & Unlock</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function commissionLedger() {
    return {
        modalOpen: false,
        selectedQuotaId: null,
        selectedAgentName: '',
        
        openOverrideModal(id, name) {
            this.selectedQuotaId = id;
            this.selectedAgentName = name;
            this.modalOpen = true;
        }
    }
}
</script>
@endpush