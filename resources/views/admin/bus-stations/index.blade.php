@extends('admin.layouts.app')

@section('title', 'Bus Stations')
@section('breadcrumb-parent', 'Settings')
@section('breadcrumb-current', 'Bus Stations')

@php
$busStationConfig = [
    'dataUrl' => route('admin.bus-stations.data'),
    'storeUrl' => route('admin.bus-stations.store'),
    'updateUrlTemplate' => route('admin.bus-stations.update', ['busStation' => '__ID__']),
    'toggleUrlTemplate' => route('admin.bus-stations.toggle', ['busStation' => '__ID__']),
    'deleteUrlTemplate' => route('admin.bus-stations.destroy', ['busStation' => '__ID__']),
];
@endphp

@section('content')
<div x-data="busStations()" x-init="loadData()" data-bus-station-config='@json($busStationConfig)'>

    {{-- Page Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Bus Stations</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage bus station pickup and drop-off points.</p>
        </div>
        <button @@click="showAddModal = true"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Station
        </button>
    </div>

    {{-- Main Card --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">

        {{-- Loading State --}}
        <div x-show="loading" class="p-12 text-center">
            <svg class="animate-spin h-6 w-6 text-slate-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-2 text-sm text-slate-500">Loading stations...</p>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && stations.length === 0" x-cloak class="p-12 text-center">
            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
            </svg>
            <p class="text-sm font-medium text-slate-500">No bus stations yet</p>
            <p class="text-xs text-slate-400 mt-1">Click "Add Station" to create one.</p>
        </div>

        {{-- Table --}}
        <div x-show="!loading && stations.length > 0" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-200/50">
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Packages</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/80">
                        <template x-for="station in stations" :key="station.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                {{-- Name (inline editable) --}}
                                <td class="px-6 py-3.5">
                                    <template x-if="editId === station.id">
                                        <div class="flex items-center gap-2">
                                            <input x-model="editName" @@keydown.enter="updateStation()" @@keydown.escape="editId = null"
                                                   x-ref="editInput"
                                                   class="w-full max-w-xs px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-blue-400/50 focus:border-blue-400">
                                            <button @@click="updateStation()" class="p-1 text-emerald-600 hover:text-emerald-700" title="Save">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            </button>
                                            <button @@click="editId = null" class="p-1 text-slate-400 hover:text-slate-600" title="Cancel">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editId !== station.id">
                                        <span class="text-sm font-medium text-slate-900" x-text="station.name"></span>
                                    </template>
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-6 py-3.5">
                                    <span x-show="station.is_active"
                                          class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                    <span x-show="!station.is_active"
                                          class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                </td>

                                {{-- Packages Count --}}
                                <td class="px-6 py-3.5">
                                    <span class="text-sm text-slate-600" x-text="station.packages_count ?? 0"></span>
                                </td>

                                {{-- Created --}}
                                <td class="px-6 py-3.5">
                                    <span class="text-sm text-slate-500" x-text="station.created_at_human"></span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        {{-- Edit --}}
                                        <button @@click="startEdit(station)" class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                                        </button>
                                        {{-- Toggle --}}
                                        <button @@click="toggleStation(station)" class="p-1.5 rounded-lg transition-colors"
                                                :class="station.is_active ? 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50'"
                                                :title="station.is_active ? 'Deactivate' : 'Activate'">
                                            <svg x-show="station.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            <svg x-show="!station.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        {{-- Delete --}}
                                        <button @@click="deleteStation(station)" class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Station Modal --}}
    <div x-show="showAddModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @@click="showAddModal = false; newName = ''"></div>

        {{-- Modal --}}
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200/80"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @@keydown.escape.window="showAddModal = false; newName = ''">

            <div class="px-6 py-5 border-b border-slate-200/50">
                <h3 class="text-lg font-semibold text-slate-900">Add Bus Station</h3>
                <p class="text-sm text-slate-500 mt-0.5">Enter the name for the new station.</p>
            </div>

            <div class="p-6">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Station Name</label>
                <input x-model="newName" type="text" placeholder="e.g. Accra Central Bus Station"
                       @@keydown.enter="addStation()"
                       x-ref="addNameInput"
                       class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-blue-400/50 focus:border-blue-400 text-sm text-slate-900 placeholder-slate-400 transition-colors">
            </div>

            <div class="px-6 py-4 bg-slate-50/50 rounded-b-2xl flex items-center justify-end gap-3">
                <button @@click="showAddModal = false; newName = ''"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Cancel
                </button>
                <button @@click="addStation()"
                        :disabled="!newName.trim()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Add Station
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function busStations() {
    const el = document.querySelector('[data-bus-station-config]');
    const config = JSON.parse(el.getAttribute('data-bus-station-config'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    return {
        stations: [],
        loading: true,
        showAddModal: false,
        newName: '',
        editId: null,
        editName: '',

        async loadData() {
            this.loading = true;
            try {
                const res = await fetch(config.dataUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                this.stations = json.data || json;
            } catch (e) {
                console.error('Failed to load bus stations', e);
            } finally {
                this.loading = false;
            }
        },

        async addStation() {
            const name = this.newName.trim();
            if (!name) return;

            try {
                const res = await fetch(config.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ name })
                });

                if (!res.ok) {
                    const err = await res.json();
                    window.showToast?.(err.message || 'Failed to add station.', 'error');
                    return;
                }

                this.newName = '';
                this.showAddModal = false;
                window.showToast?.('Bus station added successfully.', 'success');
                await this.loadData();
            } catch (e) {
                console.error('Failed to add station', e);
                window.showToast?.('Something went wrong.', 'error');
            }
        },

        startEdit(station) {
            this.editId = station.id;
            this.editName = station.name;
            this.$nextTick(() => {
                this.$refs.editInput?.focus();
            });
        },

        async updateStation() {
            const name = this.editName.trim();
            if (!name || !this.editId) return;

            const url = config.updateUrlTemplate.replace('__ID__', this.editId);

            try {
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ name })
                });

                if (!res.ok) {
                    const err = await res.json();
                    window.showToast?.(err.message || 'Failed to update station.', 'error');
                    return;
                }

                this.editId = null;
                this.editName = '';
                window.showToast?.('Station updated successfully.', 'success');
                await this.loadData();
            } catch (e) {
                console.error('Failed to update station', e);
                window.showToast?.('Something went wrong.', 'error');
            }
        },

        async toggleStation(station) {
            const url = config.toggleUrlTemplate.replace('__ID__', station.id);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) {
                    const err = await res.json();
                    window.showToast?.(err.message || 'Failed to toggle station.', 'error');
                    return;
                }

                const action = station.is_active ? 'deactivated' : 'activated';
                window.showToast?.(`Station ${action} successfully.`, 'success');
                await this.loadData();
            } catch (e) {
                console.error('Failed to toggle station', e);
                window.showToast?.('Something went wrong.', 'error');
            }
        },

        async deleteStation(station) {
            if (!confirm(`Are you sure you want to delete "${station.name}"? This action cannot be undone.`)) {
                return;
            }

            const url = config.deleteUrlTemplate.replace('__ID__', station.id);

            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) {
                    const err = await res.json();
                    window.showToast?.(err.message || 'Failed to delete station.', 'error');
                    return;
                }

                window.showToast?.('Station deleted successfully.', 'success');
                await this.loadData();
            } catch (e) {
                console.error('Failed to delete station', e);
                window.showToast?.('Something went wrong.', 'error');
            }
        }
    };
}
</script>
@endpush
