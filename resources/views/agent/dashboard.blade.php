@extends('warehouse.layouts.app')

@section('title', $pageTitle ?? 'My Call Queue')
@section('page-title', $pageTitle ?? 'My Call Queue')

@section('content')
<div class="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6">
    
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">{{ $pageTitle }}</h1>
        <p class="text-slate-500 text-sm mt-1">Temporary web interface for payment approvals. Mobile app in development.</p>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 p-4 rounded-xl flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Error Message (Prevents the crash you were seeing!) --}}
    @if(session('error'))
        <div class="bg-red-50 text-red-700 border border-red-200 p-4 rounded-xl flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-bold text-sm">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Tasks Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @forelse($tasks as $task)
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
                
                {{-- Customer Info --}}
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-black text-lg text-slate-900">{{ $task->shipmentItem->delivery_recipient_name ?? 'Unknown Customer' }}</h3>
                        <a href="tel:{{ $task->shipmentItem->delivery_recipient_phone ?? '' }}" class="text-orange-600 font-bold text-sm hover:underline flex items-center gap-1 mt-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $task->shipmentItem->delivery_recipient_phone ?? 'No Phone' }}
                        </a>
                    </div>
                    <span class="bg-amber-100 text-amber-800 text-[10px] font-black uppercase px-2.5 py-1 rounded-full tracking-wider">
                        Pending
                    </span>
                </div>

                {{-- Destination Context --}}
                <div class="bg-slate-50 rounded-xl p-3 mb-5 border border-slate-100">
                    <div class="text-xs text-slate-500 mb-1 font-semibold uppercase tracking-wide">Destination</div>
                    <div class="font-bold text-slate-800 text-sm">
                        {{ $task->shipmentItem->delivery_town ?? 'Unknown Town' }}
                    </div>
                </div>

                {{-- Approval Button --}}
                <form action="{{ route('agent.tasks.approve', $task->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-xl transition-colors shadow-md flex items-center justify-center gap-2 text-sm">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Approve Payment
                    </button>
                </form>
            </div>
        @empty
            <div class="col-span-full bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-12 text-center flex flex-col items-center">
                <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <h3 class="text-slate-900 font-bold text-lg">Your queue is clear!</h3>
                <p class="text-slate-500 text-sm mt-1">You have no pending payments to approve.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection