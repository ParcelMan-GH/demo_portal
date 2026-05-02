<div
    x-show="createContainerModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4"
>
    <div
        @@click.outside="createContainerModalOpen = false"
        x-transition
        class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
    >
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Add Transport Container</h3>
            <p class="text-xs text-slate-500 mt-1">Create a box, sack, carton, crate, or loose grouping for this manifest.</p>
        </div>

        <div class="px-5 py-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Container Type</label>
                <select
                    x-model="containerForm.container_type"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                >
                    <option value="box">Box</option>
                    <option value="sack">Sack</option>
                    <option value="carton">Carton</option>
                    <option value="crate">Crate</option>
                    <option value="loose">Loose</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Notes</label>
                <textarea
                    x-model="containerForm.notes"
                    rows="3"
                    placeholder="Optional packing notes"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                ></textarea>
            </div>
        </div>

        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <button type="button" @@click="createContainerModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
            <button
                type="button"
                @@click="submitCreateContainer()"
                :disabled="actionLoading"
                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold transition-colors"
            >
                <span x-show="!actionLoading">Create Container</span>
                <span x-show="actionLoading">Creating...</span>
            </button>
        </div>
    </div>
</div>
