@php
    $issues = $manifest->loadingExceptions->sortByDesc('created_at')->values();
    $issueStatusClass = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
        'accepted' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $reasonLabels = [
        'label_damaged' => 'Label damaged',
        'label_missing' => 'Label missing',
        'camera_cannot_read' => 'Camera cannot read',
        'item_present_no_label' => 'Item present, no label',
        'other' => 'Other',
    ];
@endphp

@if($issues->isNotEmpty())
<div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
    <div class="px-6 py-5 border-b border-slate-200/50">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Transport Scan Issues</h3>
                <p class="text-xs text-slate-500 mt-0.5">Rider-reported scan problems with proof photos.</p>
            </div>
        </div>
    </div>

    <div class="px-6 py-5">
        <div class="rounded-xl border border-slate-200/60 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px] divide-y divide-slate-200/60 text-xs">
                    <thead class="bg-slate-50/60">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Target</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Reason</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Rider</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Proof</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2.5 text-right text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/70">
                        @foreach($issues as $issue)
                            @php
                                $target = $issue->container
                                    ? $issue->container->container_code
                                    : ($issue->manifestItem?->shipmentItem?->description ?? 'Manifest item');
                                $statusClass = $issueStatusClass[$issue->status] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <p class="text-xs font-semibold text-slate-900">{{ $target }}</p>
                                    @if($issue->manifestItem?->shipmentItem?->tracking_code)
                                        <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $issue->manifestItem->shipmentItem->tracking_code }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-xs font-semibold text-slate-700">{{ $reasonLabels[$issue->reason] ?? str($issue->reason)->replace('_', ' ')->title() }}</p>
                                    @if($issue->note)
                                        <p class="text-[10px] text-slate-500 mt-0.5 max-w-[260px]">{{ $issue->note }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-xs font-medium text-slate-700">{{ $issue->driver?->name ?? 'Rider' }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $issue->created_at?->format('M d, Y H:i') }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ $issue->proof_photo_url }}" target="_blank" class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 text-[10px] font-semibold text-slate-700">View Photo</a>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                                        {{ str($issue->status)->title() }}
                                    </span>
                                    @if($issue->auto_accepted)
                                        <p class="text-[10px] text-slate-400 mt-1">Auto</p>
                                    @elseif($issue->reviewedBy)
                                        <p class="text-[10px] text-slate-400 mt-1">by {{ $issue->reviewedBy->name }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($issue->status === 'pending')
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" @@click="reviewScanIssue({{ $issue->id }}, true)" :disabled="actionLoading" class="text-[10px] font-semibold text-emerald-700 hover:underline disabled:opacity-50">Accept</button>
                                            <button type="button" @@click="reviewScanIssue({{ $issue->id }}, false)" :disabled="actionLoading" class="text-[10px] font-semibold text-rose-700 hover:underline disabled:opacity-50">Reject</button>
                                        </div>
                                    @else
                                        <span class="text-[10px] text-slate-400">Reviewed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
