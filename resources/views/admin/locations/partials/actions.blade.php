<div class="inline-flex items-center gap-1">
    <button type="button" @click="openEditModal('{{ $type }}', item)" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
    <button type="button" @click="toggleItem('{{ $type }}', item)" class="rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold" :class="item.is_active ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100'" x-text="item.is_active ? 'Disable' : 'Enable'"></button>
    <button type="button" @click="deleteItem('{{ $type }}', item)" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
</div>
