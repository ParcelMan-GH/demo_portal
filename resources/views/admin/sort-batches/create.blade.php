@extends('admin.layouts.app')

@section('title', 'New Sort Batch')
@section('breadcrumb-parent', 'Sort Batches')
@section('breadcrumb-current', 'New Batch')

@section('content')

<div class="space-y-6" x-data="createSortBatchPage()">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">New Sort Batch</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Create the batch, then select packages on the sorting page.</p>
                </div>
            </div>
            <a href="{{ route('admin.sort-batches.index') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
        </div>

        <form @@submit.prevent="submitCreateBatch()" class="p-6">
            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6">
                <div class="space-y-5">
                    <div x-show="createBatchError" x-cloak class="flex items-start gap-2 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3">
                        <svg class="w-4 h-4 text-rose-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-rose-700" x-text="createBatchError"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Origin Warehouse <span class="text-rose-500">*</span></label>
                        <select x-model="newBatch.origin_warehouse_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500/30 focus:border-slate-400 transition-colors">
                            <option value="">Select origin warehouse...</option>
                            @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}{{ $wh->code ? ' (' . $wh->code . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Dispatch Mode <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                   :class="newBatch.dispatch_mode === 'transfer' ? 'border-slate-700 bg-slate-50' : 'border-slate-200 bg-white hover:border-slate-300'">
                                <input type="radio" value="transfer" x-model="newBatch.dispatch_mode" class="sr-only">
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                     :class="newBatch.dispatch_mode === 'transfer' ? 'border-slate-700' : 'border-slate-300'">
                                    <div class="w-2 h-2 rounded-full bg-slate-700 transition-all" :class="newBatch.dispatch_mode === 'transfer' ? 'opacity-100' : 'opacity-0'"></div>
                                </div>
                                <div>
                                    <span class="block text-sm font-bold" :class="newBatch.dispatch_mode === 'transfer' ? 'text-slate-900' : 'text-slate-600'">Transfer</span>
                                    <span class="block text-xs text-slate-500 mt-0.5">Move packages to another warehouse.</span>
                                </div>
                            </label>
                            <label class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                   :class="newBatch.dispatch_mode === 'local_delivery' ? 'border-slate-700 bg-slate-50' : 'border-slate-200 bg-white hover:border-slate-300'">
                                <input type="radio" value="local_delivery" x-model="newBatch.dispatch_mode" class="sr-only">
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                     :class="newBatch.dispatch_mode === 'local_delivery' ? 'border-slate-700' : 'border-slate-300'">
                                    <div class="w-2 h-2 rounded-full bg-slate-700 transition-all" :class="newBatch.dispatch_mode === 'local_delivery' ? 'opacity-100' : 'opacity-0'"></div>
                                </div>
                                <div>
                                    <span class="block text-sm font-bold" :class="newBatch.dispatch_mode === 'local_delivery' ? 'text-slate-900' : 'text-slate-600'">Local Delivery</span>
                                    <span class="block text-xs text-slate-500 mt-0.5">Deliver from the selected warehouse.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div x-show="newBatch.dispatch_mode === 'transfer'" x-transition>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Destination Warehouse <span class="text-rose-500">*</span></label>
                        <select x-model="newBatch.destination_warehouse_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500/30 focus:border-slate-400 transition-colors">
                            <option value="">Select destination warehouse...</option>
                            @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}{{ $wh->code ? ' (' . $wh->code . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                        <textarea x-model="newBatch.notes" rows="4" placeholder="Optional notes about this batch..."
                                  class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500/30 focus:border-slate-400 transition-colors resize-none"></textarea>
                    </div>
                </div>

                <aside class="bg-slate-50 rounded-2xl border border-slate-200 p-5 h-fit">
                    <h2 class="text-sm font-bold text-slate-900">Next Step</h2>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">After this batch is created, you will be taken to its sorting workspace to search warehouse packages and add them directly.</p>
                    <div class="mt-5 flex flex-col gap-2">
                        <button type="submit" :disabled="createBatchLoading"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-sm font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                            <svg x-show="!createBatchLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <svg x-show="createBatchLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Create Batch
                        </button>
                        <a href="{{ route('admin.sort-batches.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-white transition-colors">
                            Cancel
                        </a>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function createSortBatchPage() {
    return {
        createBatchLoading: false,
        createBatchError: '',
        newBatch: {
            origin_warehouse_id: '',
            dispatch_mode: 'local_delivery',
            destination_warehouse_id: '',
            notes: '',
        },

        async submitCreateBatch() {
            this.createBatchError = '';
            if (!this.newBatch.origin_warehouse_id) {
                this.createBatchError = 'Please select an origin warehouse.';
                return;
            }
            if (this.newBatch.dispatch_mode === 'transfer' && !this.newBatch.destination_warehouse_id) {
                this.createBatchError = 'Please select a destination warehouse for transfer mode.';
                return;
            }

            this.createBatchLoading = true;
            try {
                const body = {
                    origin_warehouse_id: this.newBatch.origin_warehouse_id,
                    dispatch_mode: this.newBatch.dispatch_mode,
                    notes: this.newBatch.notes || null,
                };
                if (this.newBatch.dispatch_mode === 'transfer') {
                    body.destination_warehouse_id = this.newBatch.destination_warehouse_id;
                }

                const resp = await fetch('{{ route('admin.sort-batches.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(body),
                });
                const json = await resp.json();

                if (json.success && json.data?.batch) {
                    window.location.href = '{{ route('admin.sort-batches.show', ['batch' => '__ID__']) }}'.replace('__ID__', json.data.batch.id);
                    return;
                }

                this.createBatchError = json.message || 'Failed to create sort batch.';
            } catch (e) {
                this.createBatchError = 'An unexpected error occurred.';
            } finally {
                this.createBatchLoading = false;
            }
        },
    };
}
</script>
@endpush
