@extends('warehouse.layouts.app')

@section('title', $pageTitle ?? 'Smart Call Allocation')
@section('page-title', $pageTitle ?? 'Smart Call Allocation')

@section('content')
<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6" 
     x-data="agentAllocationPage()" 
     x-init="init()"
     data-agents="{{ json_encode($agents ?? []) }}"
     data-tasks="{{ json_encode($unassignedTasks ?? []) }}"
     data-endpoint="{{ $assignEndpoint ?? '' }}">

    {{-- Alert Toast --}}
    <div x-show="notice.message" x-cloak x-transition class="fixed right-6 bottom-6 z-[200] flex items-center gap-3 rounded-2xl border px-5 py-3 text-sm font-semibold shadow-2xl"
         :class="notice.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'">
        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path x-show="notice.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            <path x-show="!notice.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span x-text="notice.message"></span>
        <button type="button" class="ml-4 opacity-60 hover:opacity-100" @click="notice.message = ''">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">{{ $pageTitle ?? 'Smart Call Allocation' }}</h1>
            <p class="text-slate-500 text-sm mt-1">{{ $pageSubtitle ?? 'Distribute follow-ups and monitor agent daily quotas.' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        
        {{-- ═══════════ LEFT: UNASSIGNED TASKS ═══════════ --}}
        <div class="xl:col-span-7 2xl:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col h-[calc(100vh-140px)]">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center border border-orange-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Unassigned Queue</h2>
                        <p class="text-xs text-slate-500 font-medium"><span x-text="filteredTasks.length"></span> pending tasks</p>
                    </div>
                </div>
                
                <div class="relative w-64">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" placeholder="Search customer or package..." class="w-full bg-white border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-2">
                <template x-if="filteredTasks.length === 0">
                    <div class="flex flex-col items-center justify-center h-full text-slate-400 space-y-3 py-12">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-500">Queue is empty!</p>
                    </div>
                </template>

                <div x-show="filteredTasks.length > 0" class="space-y-2">
                    <div class="px-3 py-2 flex items-center gap-3">
                        <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="w-4 h-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500 cursor-pointer">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Select All</span>
                    </div>

                    <template x-for="task in filteredTasks" :key="task.id">
                        <label class="flex items-start gap-4 p-4 rounded-xl border transition-all cursor-pointer group"
                               :class="selectedTasks.includes(task.id) ? 'bg-orange-50/50 border-orange-200' : 'bg-white border-slate-100 hover:border-slate-200 hover:bg-slate-50'">
                            
                            <div class="pt-1 shrink-0">
                                <input type="checkbox" :value="task.id" x-model="selectedTasks" class="w-4 h-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500 cursor-pointer">
                            </div>
                            
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-sm font-bold text-slate-900 truncate" x-text="task.customer"></h3>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold border uppercase tracking-wider" 
                                          :class="statusClass(task.status)" x-text="task.status"></span>
                                </div>
                                <div class="flex items-center gap-4 text-xs font-medium text-slate-500">
                                    <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> <span x-text="task.phone"></span></span>
                                    <span class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> <span class="truncate max-w-[150px]" x-text="task.package"></span></span>
                                </div>
                            </div>

                            <div class="shrink-0 text-right pt-1">
                                <p class="text-xs font-bold text-slate-400">Due</p>
                                <p class="text-sm font-black text-slate-800" x-text="'₵ ' + Number(task.amount_due).toFixed(2)"></p>
                            </div>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        {{-- ═══════════ RIGHT: CALL AGENTS ═══════════ --}}
        <div class="xl:col-span-5 2xl:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col h-[calc(100vh-140px)]">
            <div class="p-5 border-b border-slate-100 bg-[#FFFBF8] rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900">Active Agents</h2>
                    <span x-show="selectedTasks.length > 0" x-transition class="bg-orange-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-sm animate-pulse">
                        <span x-text="selectedTasks.length"></span> Ready to Assign
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1">Select tasks on the left to assign.</p>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <template x-for="agent in agents" :key="agent.id">
                    <div class="relative bg-white border border-slate-200 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-orange-200 transition-all group">
                        
                        {{-- Agent Info & Stats --}}
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 text-slate-600 font-bold uppercase text-sm">
                                    <span x-text="agent.name.charAt(0)"></span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900" x-text="agent.name"></h3>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs font-semibold text-slate-500">Assigned: <span class="text-slate-800" x-text="agent.assigned_today"></span></span>
                                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                        <span class="text-xs font-semibold text-emerald-600">Done: <span x-text="agent.completed_today"></span></span>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Smart Backlog Indicator --}}
                            <div class="text-right">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Backlog</p>
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-md px-1.5 text-xs font-black border"
                                      :class="agent.backlog > 10 ? 'bg-rose-50 text-rose-700 border-rose-200' : (agent.backlog > 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200')">
                                    <span x-text="agent.backlog"></span>
                                </span>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="w-full bg-slate-100 rounded-full h-1.5 mb-2 overflow-hidden">
                            <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" 
                                 :style="`width: ${agent.assigned_today > 0 ? (agent.completed_today / agent.assigned_today) * 100 : 0}%`"></div>
                        </div>

                        {{-- Overlay Assign Button (Appears when tasks are selected) --}}
                        <div x-show="selectedTasks.length > 0" x-transition 
                             class="absolute inset-0 bg-white/60 backdrop-blur-[2px] rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10">
                            <button type="button" @click="assignToAgent(agent)" :disabled="loading"
                                    class="bg-orange-600 hover:bg-orange-700 text-white shadow-lg shadow-orange-600/30 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transform transition-transform active:scale-95 disabled:opacity-50">
                                <span>Assign</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-md" x-text="selectedTasks.length"></span>
                                <span>Tasks</span>
                            </button>
                        </div>

                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function agentAllocationPage() {
    return {
        agents: [],
        allTasks: [],
        search: '',
        selectedTasks: [],
        selectAll: false,
        loading: false,
        assignEndpoint: '',
        notice: { success: true, message: '' },

        init() {
            const el = this.$root;
            this.agents = JSON.parse(el.dataset.agents || '[]');
            this.allTasks = JSON.parse(el.dataset.tasks || '[]');
            this.assignEndpoint = el.dataset.endpoint || '';
            
            // Watch select all box
            this.$watch('selectedTasks', (val) => {
                this.selectAll = (val.length === this.filteredTasks.length && this.filteredTasks.length > 0);
            });
        },

        get filteredTasks() {
            if (!this.search) return this.allTasks;
            const term = this.search.toLowerCase();
            return this.allTasks.filter(task => 
                task.customer.toLowerCase().includes(term) || 
                task.package.toLowerCase().includes(term) ||
                task.phone.includes(term)
            );
        },

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedTasks = this.filteredTasks.map(t => t.id);
            } else {
                this.selectedTasks = [];
            }
        },

        statusClass(status) {
            status = String(status || '').toLowerCase();
            if (status === 'pending') return 'bg-amber-50 text-amber-700 border-amber-200';
            if (status === 'failed' || status === 'unreachable') return 'bg-rose-50 text-rose-700 border-rose-200';
            return 'bg-slate-50 text-slate-700 border-slate-200';
        },

        toast(success, message) {
            this.notice = { success, message };
            if (success) setTimeout(() => { this.notice.message = ''; }, 4000);
        },

        async assignToAgent(agent) {
            if (this.selectedTasks.length === 0) return;
            
            this.loading = true;
            try {
                const res = await fetch(this.assignEndpoint, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                    },
                    body: JSON.stringify({
                        agent_id: agent.id,
                        task_ids: this.selectedTasks
                    })
                });
                
                const json = await res.json();
                
                if (res.ok && json.success) {
                    this.toast(true, `Successfully assigned ${this.selectedTasks.length} tasks to ${agent.name}!`);
                    
                    // Update Local State without reloading page
                    // 1. Increase agent's assigned_today and backlog
                    agent.assigned_today += this.selectedTasks.length;
                    agent.backlog += this.selectedTasks.length;
                    
                    // 2. Remove assigned tasks from the left panel
                    this.allTasks = this.allTasks.filter(t => !this.selectedTasks.includes(t.id));
                    
                    // 3. Clear selections
                    this.selectedTasks = [];
                    this.selectAll = false;
                    
                } else {
                    this.toast(false, json.message || 'Failed to assign tasks.');
                }
            } catch (error) {
                this.toast(false, 'Network error occurred.');
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush