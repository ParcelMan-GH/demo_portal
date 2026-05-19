@props([
    'label',
    'model',
    'placeholder' => '',
    'required' => false,
    'className' => '',
    'help' => null,
])

<div>
    <label class="mb-2 block text-sm font-semibold text-slate-700">
        {{ $label }} @if($required)<span class="text-rose-500">*</span>@endif
    </label>
    <input
        x-model="{{ $model }}"
        type="text"
        placeholder="{{ $placeholder }}"
        class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 {{ $className }}"
    >
    @if($help)
        <p class="mt-2 text-xs font-semibold text-slate-500">{{ $help }}</p>
    @endif
</div>
