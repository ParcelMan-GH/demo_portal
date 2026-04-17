<div x-data="busStationsTab()" x-init="loadData()">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-sm font-bold text-slate-900">Bus Stations</h3>
            <p class="text-xs text-slate-500 mt-0.5">Manage bus stations for out-of-town package handoffs</p>
        </div>
        <button @@click="showAdd = true" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-semibold rounded-xl transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Station
        </button>
    </div>

    {{-- Add form --}}
    <div x-show="showAdd" x-cloak class="mb-4 p-4 bg-blue-50 rounded-xl border border-blue-200">
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Station Name</label>
                <input type="text" x-model="newName" placeholder="e.g. Circle Bus Station" @@keydown.enter="addStation()" @@keydown.escape="showAdd = false; newName = ''"
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none">
            </div>
            <button @@click="addStation()" :disabled="!newName.trim() || saving" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg disabled:opacity-50 transition-colors">Add</button>
            <button @@click="showAdd = false; newName = ''" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-700">Cancel</button>
        </div>
    </div>

    {{-- Table --}}
    <div x-show="!loading && stations.length > 0">
        <div class="divide-y divide-slate-100">
            <template x-for="station in stations" :key="station.id">
                <div class="flex items-center justify-between py-3 px-1">
                    <div class="flex items-center gap-3">
                        <template x-if="editId !== station.id">
                            <span class="text-sm font-medium text-slate-900" x-text="station.name"></span>
                        </template>
                        <template x-if="editId === station.id">
                            <input type="text" x-model="editName" @@keydown.enter="updateStation(station)" @@keydown.escape="editId = null"
                                   class="px-2 py-1 text-sm border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none w-64">
                        </template>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                              :class="station.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                              x-text="station.is_active ? 'Active' : 'Inactive'"></span>
                        <span class="text-[10px] text-slate-400" x-text="(station.packages_count || 0) + ' packages'"></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <template x-if="editId === station.id">
                            <div class="flex gap-1">
                                <button @@click="updateStation(station)" class="px-2 py-1 text-[10px] font-semibold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">Save</button>
                                <button @@click="editId = null" class="px-2 py-1 text-[10px] text-slate-500 hover:text-slate-700">Cancel</button>
                            </div>
                        </template>
                        <template x-if="editId !== station.id">
                            <div class="flex gap-1">
                                <button @@click="editId = station.id; editName = station.name" class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors" title="Edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @@click="toggleStation(station)" class="p-1.5 rounded-lg transition-colors" :class="station.is_active ? 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50'" :title="station.is_active ? 'Deactivate' : 'Activate'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                </button>
                                <button @@click="deleteStation(station)" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="!loading && stations.length === 0" class="text-center py-10 text-slate-400">
        <p class="text-sm">No bus stations yet. Click "Add Station" to create one.</p>
    </div>
    <div x-show="loading" class="text-center py-10"><svg class="w-5 h-5 animate-spin text-slate-400 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></div>
</div>

@push('scripts')
<script>
function busStationsTab() {
    return {
        stations: [], loading: false, saving: false,
        showAdd: false, newName: '',
        editId: null, editName: '',

        csrfToken() { return document.querySelector('meta[name="csrf-token"]').content; },

        async loadData() {
            this.loading = true;
            try {
                const r = await fetch('{{ route("admin.bus-stations.data") }}', { headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                this.stations = j.data || [];
            } catch (e) {}
            this.loading = false;
        },

        async addStation() {
            if (!this.newName.trim()) return;
            this.saving = true;
            try {
                const r = await fetch('{{ route("admin.bus-stations.store") }}', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify({ name: this.newName.trim() })
                });
                const j = await r.json();
                if (j.success) { this.newName = ''; this.showAdd = false; this.loadData(); window.showToast?.(j.message, 'success'); }
                else window.showToast?.(j.message || 'Failed', 'error');
            } catch (e) { window.showToast?.('Error', 'error'); }
            this.saving = false;
        },

        async updateStation(station) {
            if (!this.editName.trim()) return;
            try {
                const url = '{{ route("admin.bus-stations.update", ["busStation" => "__ID__"]) }}'.replace('__ID__', station.id);
                const r = await fetch(url, {
                    method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify({ name: this.editName.trim() })
                });
                const j = await r.json();
                if (j.success) { station.name = this.editName.trim(); this.editId = null; window.showToast?.(j.message, 'success'); }
                else window.showToast?.(j.message || 'Failed', 'error');
            } catch (e) { window.showToast?.('Error', 'error'); }
        },

        async toggleStation(station) {
            try {
                const url = '{{ route("admin.bus-stations.toggle", ["busStation" => "__ID__"]) }}'.replace('__ID__', station.id);
                const r = await fetch(url, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.success) { station.is_active = j.is_active; window.showToast?.(j.message, 'success'); }
            } catch (e) { window.showToast?.('Error', 'error'); }
        },

        async deleteStation(station) {
            if (!confirm('Delete "' + station.name + '"?')) return;
            try {
                const url = '{{ route("admin.bus-stations.destroy", ["busStation" => "__ID__"]) }}'.replace('__ID__', station.id);
                const r = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.success) { this.stations = this.stations.filter(s => s.id !== station.id); window.showToast?.(j.message, 'success'); }
                else window.showToast?.(j.message || 'Failed', 'error');
            } catch (e) { window.showToast?.('Error', 'error'); }
        },
    };
}
</script>
@endpush
