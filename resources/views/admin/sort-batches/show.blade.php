@extends('admin.layouts.app')

@section('title', $batch->batch_number)
@section('breadcrumb-parent', 'Sort Batches')
@section('breadcrumb-current', $batch->batch_number)

@section('content')

<div class="space-y-6" x-data="sortBatchShow()" x-init="init()">

    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="sbgrid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#sbgrid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <!-- Top Row: Back Button + Actions -->
                <div class="flex items-center justify-between mb-6">
                    <a href="{{ route('admin.sort-batches.index') }}" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back to Sort Batches</span>
                    </a>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                        <!-- Add Items -->
                        <button type="button" @@click="openAddItemsModal()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/30 text-blue-200 text-xs font-semibold transition-all backdrop-blur-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Items
                        </button>
                        <!-- Seal Batch -->
                        <button type="button" @@click="sealBatch()" :disabled="actionLoading"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-semibold transition-all backdrop-blur-sm disabled:opacity-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Seal Batch
                        </button>
                        @endif

                        @if($batch->status === \App\Models\SortBatch::STATUS_SEALED)
                        <!-- Reopen Batch -->
                        <button type="button" @@click="reopenBatch()" :disabled="actionLoading"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 border border-amber-400/30 text-amber-200 text-xs font-semibold transition-all backdrop-blur-sm disabled:opacity-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Reopen
                        </button>

                        @if($batch->dispatch_mode === \App\Models\SortBatch::DISPATCH_LOCAL_DELIVERY && !$batch->deliveryRun)
                        <!-- Create Delivery Run -->
                        <button type="button" @@click="createDeliveryRun()" :disabled="actionLoading"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-400/30 text-emerald-200 text-xs font-semibold transition-all backdrop-blur-sm disabled:opacity-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Create Delivery Run
                        </button>
                        @endif
                        @endif
                    </div>
                </div>

                <!-- Main Row: Profile LEFT, Summary RIGHT -->
                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <!-- LEFT: Batch Info -->
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center shadow-xl shadow-blue-500/30 ring-4 ring-white/10">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </div>

                        <div class="space-y-1.5 min-w-0">
                            <h1 class="text-2xl font-bold text-white">{{ $batch->batch_number }}</h1>

                            <!-- Status + Mode Badges -->
                            <div class="flex flex-wrap items-center gap-2">
                                @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30">Open</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-500/30 text-slate-300 ring-1 ring-slate-400/30">Sealed</span>
                                @endif

                                @if($batch->dispatch_mode === \App\Models\SortBatch::DISPATCH_TRANSFER)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-300 ring-1 ring-blue-400/30">Transfer</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 ring-1 ring-amber-400/30">Local Delivery</span>
                                @endif
                            </div>

                            <!-- Warehouse Route -->
                            <div class="flex items-center gap-2 text-sm text-slate-300">
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <span>{{ $batch->originWarehouse?->name ?? '—' }}</span>
                                @if($batch->destinationWarehouse)
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span>{{ $batch->destinationWarehouse->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Summary Cards -->
                    <div class="lg:ml-auto grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white/5 rounded-xl px-4 py-3 border border-white/10 min-w-[120px]">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ $batch->active_items_count }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Items</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white/5 rounded-xl px-4 py-3 border border-white/10 min-w-[120px]">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-violet-500/20 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white leading-none truncate max-w-[100px]">{{ $batch->createdBy?->name ?? '—' }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Created By</p>
                                </div>
                            </div>
                        </div>

                        @if($batch->transportManifest)
                        <div class="bg-white/5 rounded-xl px-4 py-3 border border-white/10 min-w-[120px]">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white leading-none">Manifest</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Linked</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="bg-white/5 rounded-xl px-4 py-3 border border-white/10 min-w-[120px]">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-slate-500/20 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-white leading-none">{{ $batch->created_at->format('M d') }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Created</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Feedback -->
    <div x-show="actionMessage" x-cloak x-transition
         class="flex items-center gap-3 px-5 py-3 rounded-2xl border text-sm font-medium"
         :class="actionSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'">
        <svg x-show="actionSuccess" class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <svg x-show="!actionSuccess" class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span x-text="actionMessage"></span>
        <button type="button" @@click="actionMessage = ''" class="ml-auto text-current opacity-50 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Tabs Section -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex min-h-[520px]">

        <!-- Sidebar Nav -->
        <aside class="w-52 flex-shrink-0 bg-white border-r border-slate-100 flex flex-col py-4 px-2.5">

            <!-- Section: Batch -->
            <p class="px-1.5 mb-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Batch</p>

            <!-- Overview -->
            <button @@click="activeTab = 'overview'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'overview' ? 'bg-sky-50 ring-1 ring-sky-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'overview' ? 'bg-sky-500 shadow-sm shadow-sky-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'overview' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'overview' ? 'font-bold text-sky-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Overview</span>
            </button>

            <!-- Batch Items -->
            <button @@click="activeTab = 'items'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'items' ? 'bg-blue-50 ring-1 ring-blue-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'items' ? 'bg-blue-500 shadow-sm shadow-blue-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'items' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <span class="flex-1 text-xs transition-colors" :class="activeTab === 'items' ? 'font-bold text-blue-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Batch Items</span>
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'items' ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-500'">{{ $batch->active_items_count }}</span>
            </button>

            <!-- Divider: Logistics -->
            <div class="flex items-center gap-2 mt-3 mb-1.5 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Logistics</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <!-- Transport -->
            <button @@click="activeTab = 'transport'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'transport' ? 'bg-emerald-50 ring-1 ring-emerald-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'transport' ? 'bg-emerald-500 shadow-sm shadow-emerald-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'transport' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="flex-1 text-xs transition-colors" :class="activeTab === 'transport' ? 'font-bold text-emerald-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Transport</span>
                @if($batch->transportManifest)
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'transport' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'">1</span>
                @endif
            </button>

            <!-- Delivery -->
            <button @@click="activeTab = 'delivery'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg transition-all duration-150 text-left"
                :class="activeTab === 'delivery' ? 'bg-amber-50 ring-1 ring-amber-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'delivery' ? 'bg-amber-500 shadow-sm shadow-amber-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'delivery' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="flex-1 text-xs transition-colors" :class="activeTab === 'delivery' ? 'font-bold text-amber-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Delivery Run</span>
                @if($batch->deliveryRun)
                <span class="inline-flex items-center justify-center min-w-[1.125rem] h-4 px-1 text-[9px] font-bold rounded-full transition-all"
                    :class="activeTab === 'delivery' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-500'">1</span>
                @endif
            </button>

        </aside>

        <!-- Tab Content Area -->
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">

            <!-- ═══════════════════════════════════════ -->
            <!-- OVERVIEW TAB                            -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'overview'" x-cloak>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

                    <!-- Batch Details Card -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-slate-900">Batch Details</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Core information about this sort batch</p>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-xs font-medium text-slate-500">Batch Number</span>
                                <span class="text-xs font-semibold text-slate-900 font-mono">{{ $batch->batch_number }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-xs font-medium text-slate-500">Status</span>
                                @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Open
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Sealed
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-xs font-medium text-slate-500">Dispatch Mode</span>
                                @if($batch->dispatch_mode === \App\Models\SortBatch::DISPATCH_TRANSFER)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-700">Transfer</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700">Local Delivery</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-xs font-medium text-slate-500">Items Count</span>
                                <span class="text-xs font-semibold text-slate-900">{{ $batch->active_items_count }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-xs font-medium text-slate-500">Created At</span>
                                <span class="text-xs font-semibold text-slate-900">{{ $batch->created_at->format('d M Y, H:i') }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-xs font-medium text-slate-500">Created By</span>
                                <span class="text-xs font-semibold text-slate-900">{{ $batch->createdBy?->name ?? '—' }}</span>
                            </div>
                            @if($batch->status === \App\Models\SortBatch::STATUS_SEALED)
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-xs font-medium text-slate-500">Sealed By</span>
                                <span class="text-xs font-semibold text-slate-900">{{ $batch->sealedBy?->name ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-xs font-medium text-slate-500">Sealed At</span>
                                <span class="text-xs font-semibold text-slate-900">{{ $batch->sealed_at?->format('d M Y, H:i') ?? '—' }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Warehouse Route Card -->
                    <div class="space-y-5">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <div class="mb-4">
                                <h3 class="text-sm font-bold text-slate-900">Warehouse Route</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Origin and destination warehouses</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <!-- Origin -->
                                <div class="flex-1 bg-slate-50 rounded-xl p-4 border border-slate-100">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                            </svg>
                                        </div>
                                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Origin</span>
                                    </div>
                                    <p class="text-sm font-bold text-slate-900">{{ $batch->originWarehouse?->name ?? '—' }}</p>
                                    @if($batch->originWarehouse?->code)
                                        <p class="text-[10px] text-slate-500 mt-0.5 font-mono">{{ $batch->originWarehouse->code }}</p>
                                    @endif
                                </div>

                                <!-- Arrow -->
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Destination -->
                                <div class="flex-1 bg-slate-50 rounded-xl p-4 border border-slate-100">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Destination</span>
                                    </div>
                                    <p class="text-sm font-bold text-slate-900">{{ $batch->destinationWarehouse?->name ?? '—' }}</p>
                                    @if($batch->destinationWarehouse?->code)
                                        <p class="text-[10px] text-slate-500 mt-0.5 font-mono">{{ $batch->destinationWarehouse->code }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Notes Card -->
                        @if($batch->notes)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <h3 class="text-sm font-bold text-slate-900 mb-2">Notes</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ $batch->notes }}</p>
                        </div>
                        @endif

                        <!-- Quick Links Card -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                            <h3 class="text-sm font-bold text-slate-900 mb-3">Quick Links</h3>
                            <div class="space-y-2">
                                <button @@click="activeTab = 'items'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-100 hover:border-blue-100 transition-colors group">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-700 group-hover:text-blue-700">View Batch Items</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-400 group-hover:text-blue-500">{{ $batch->active_items_count }} items</span>
                                </button>

                                @if($batch->transportManifest)
                                <button @@click="activeTab = 'transport'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-100 hover:border-emerald-100 transition-colors group">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-700 group-hover:text-emerald-700">Transport Manifest</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-400 group-hover:text-emerald-500">{{ $batch->transportManifest->manifest_number }}</span>
                                </button>
                                @endif

                                @if($batch->deliveryRun)
                                <button @@click="activeTab = 'delivery'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-amber-50 border border-slate-100 hover:border-amber-100 transition-colors group">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-700 group-hover:text-amber-700">Delivery Run</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-400 group-hover:text-amber-500">{{ $batch->deliveryRun->run_number }}</span>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- BATCH ITEMS TAB                         -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'items'" x-cloak>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <!-- Header -->
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Batch Items</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    <span x-text="itemsMeta.total"></span> <span x-text="itemsMeta.total === 1 ? 'item' : 'items'"></span> in this batch
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                            <button type="button" @@click="openAddItemsModal()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Items
                            </button>
                            @endif
                            <div class="relative">
                                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" x-model="itemsSearch" @@input="onItemsSearch()" placeholder="Search items..."
                                       class="pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 outline-none w-52 bg-slate-50/50">
                            </div>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div x-show="itemsLoading" class="flex items-center justify-center py-16">
                        <svg class="w-6 h-6 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Empty state -->
                    <div x-show="!itemsLoading && itemsLoaded && items.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-400">
                        <svg class="w-10 h-10 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="text-sm font-medium" x-text="itemsSearch ? 'No items match your search' : 'No items in this batch'"></p>
                        <p class="text-xs text-slate-400 mt-1" x-show="!itemsSearch">Items will appear here once they are added during sorting.</p>
                    </div>

                    <!-- Table -->
                    <div x-show="!itemsLoading && items.length > 0" class="overflow-x-auto">
                        <table class="w-full min-w-[800px] text-xs">
                            <thead class="bg-slate-50/70 border-b border-slate-200/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-12">#</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Shipment</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Tracking Code</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Recipient</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Destination</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Added By</th>
                                    @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                    <th class="px-4 py-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider w-12"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100/70">
                                <template x-for="item in items" :key="item.id">
                                    <tr class="hover:bg-slate-50/70 group">
                                        <td class="px-4 py-2.5 whitespace-nowrap text-slate-400 font-medium" x-text="item.row_number"></td>
                                        <td class="px-4 py-2.5 whitespace-nowrap">
                                            <template x-if="item.shipment_id">
                                                <div>
                                                    <a :href="shipmentUrl(item.shipment_id)" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline" x-text="item.shipment_number"></a>
                                                    <span x-show="item.vendor_name" class="block text-[10px] text-slate-400 mt-0.5" x-text="item.vendor_name"></span>
                                                </div>
                                            </template>
                                            <template x-if="!item.shipment_id">
                                                <span class="text-slate-400">—</span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap">
                                            <template x-if="item.tracking_code">
                                                <span class="font-mono text-[11px] text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded" x-text="item.tracking_code"></span>
                                            </template>
                                            <template x-if="!item.tracking_code">
                                                <span class="text-slate-400">—</span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2.5 text-slate-700 max-w-[200px] truncate" x-text="item.description || '—'"></td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-[10px] font-semibold text-slate-700" x-text="item.quantity || '—'"></span>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap">
                                            <div class="text-xs font-medium text-slate-900" x-text="item.delivery_recipient_name || '—'"></div>
                                            <div x-show="item.delivery_recipient_phone" class="text-[10px] text-slate-500" x-text="item.delivery_recipient_phone"></div>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="item.delivery_town || '—'"></td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="item.added_by || '—'"></td>
                                        @if($batch->status === \App\Models\SortBatch::STATUS_OPEN)
                                        <td class="px-4 py-2.5 whitespace-nowrap text-center">
                                            <button type="button" @@click="removeItem(item)"
                                                    :disabled="actionLoading"
                                                    class="w-6 h-6 rounded-lg flex items-center justify-center text-slate-300 hover:bg-rose-50 hover:text-rose-500 transition-colors opacity-0 group-hover:opacity-100 disabled:opacity-30">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </td>
                                        @endif
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div x-show="!itemsLoading && itemsMeta.last_page > 1" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between">
                        <p class="text-[11px] text-slate-500">
                            Showing <span class="font-semibold text-slate-700" x-text="itemsMeta.from"></span>
                            to <span class="font-semibold text-slate-700" x-text="itemsMeta.to"></span>
                            of <span class="font-semibold text-slate-700" x-text="itemsMeta.total"></span> items
                        </p>
                        <div class="flex items-center gap-1">
                            <button @@click="goItemsPage(itemsMeta.current_page - 1)" :disabled="itemsMeta.current_page <= 1"
                                    class="px-2.5 py-1.5 text-[11px] font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                Prev
                            </button>
                            <template x-for="p in itemsMeta.last_page" :key="p">
                                <template x-if="itemsMeta.last_page <= 7 || p === 1 || p === itemsMeta.last_page || Math.abs(p - itemsMeta.current_page) <= 1">
                                    <button @@click="goItemsPage(p)"
                                            :class="p === itemsMeta.current_page ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                                            class="px-2.5 py-1.5 text-[11px] font-medium rounded-lg border transition-colors min-w-[32px]"
                                            x-text="p"></button>
                                </template>
                            </template>
                            <button @@click="goItemsPage(itemsMeta.current_page + 1)" :disabled="itemsMeta.current_page >= itemsMeta.last_page"
                                    class="px-2.5 py-1.5 text-[11px] font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- TRANSPORT TAB                           -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'transport'" x-cloak>
                @if($batch->transportManifest)
                @php $manifest = $batch->transportManifest; @endphp
                <div class="space-y-5">
                    <!-- Manifest Info Card -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-start justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Transport Manifest</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $manifest->manifest_number }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.transport-manifests.show', $manifest->id) }}"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors shadow-sm">
                                View Manifest
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @php
                                $manifestStatusColors = [
                                    'draft' => 'bg-slate-100 text-slate-700', 'assigned' => 'bg-blue-100 text-blue-700',
                                    'loading' => 'bg-amber-100 text-amber-700', 'in_transit' => 'bg-violet-100 text-violet-700',
                                    'arrived' => 'bg-indigo-100 text-indigo-700', 'received' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-rose-100 text-rose-700',
                                ];
                            @endphp
                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $manifestStatusColors[$manifest->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst(str_replace('_', ' ', $manifest->status)) }}
                                </span>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Driver</div>
                                <p class="text-sm font-semibold text-slate-900">{{ $manifest->assignedDriver?->name ?? 'Not assigned' }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Created At</div>
                                <p class="text-sm font-semibold text-slate-900">{{ $manifest->created_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No Transport Manifest</p>
                        <p class="text-xs text-slate-400 mt-1">This batch has not been assigned to a transport manifest yet.</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!-- DELIVERY RUN TAB                        -->
            <!-- ═══════════════════════════════════════ -->
            <div x-show="activeTab === 'delivery'" x-cloak>
                @if($batch->deliveryRun)
                @php $run = $batch->deliveryRun; @endphp
                <div class="space-y-5">
                    <!-- Delivery Run Info Card -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-start justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Delivery Run</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $run->run_number }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.delivery-runs.show', $run->id) }}"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold transition-colors shadow-sm">
                                View Delivery Run
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @php
                                $runStatusColors = [
                                    'draft' => 'bg-slate-100 text-slate-700', 'assigned' => 'bg-blue-100 text-blue-700',
                                    'out_for_delivery' => 'bg-amber-100 text-amber-700', 'partially_delivered' => 'bg-violet-100 text-violet-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700', 'cancelled' => 'bg-rose-100 text-rose-700',
                                ];
                            @endphp
                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $runStatusColors[$run->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst(str_replace('_', ' ', $run->status)) }}
                                </span>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Driver</div>
                                <p class="text-sm font-semibold text-slate-900">{{ $run->assignedDriver?->name ?? 'Not assigned' }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Created At</div>
                                <p class="text-sm font-semibold text-slate-900">{{ $run->created_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No Delivery Run</p>
                        @if($batch->status === \App\Models\SortBatch::STATUS_SEALED && $batch->dispatch_mode === \App\Models\SortBatch::DISPATCH_LOCAL_DELIVERY)
                        <p class="text-xs text-slate-400 mt-1 mb-4">This sealed local-delivery batch is ready for a delivery run.</p>
                        <button type="button" @@click="createDeliveryRun()" :disabled="actionLoading"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold transition-colors shadow-sm disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Create Delivery Run
                        </button>
                        @else
                        <p class="text-xs text-slate-400 mt-1">This batch has not been assigned to a delivery run yet.</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Add Items                                                          --}}
{{-- ════════════════════════════════════════════════════════════════════════ --}}
<div x-show="addItemsModalOpen"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @@keydown.escape.window="addItemsModalOpen = false"
     class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="addItemsModalOpen = false"></div>
    <div x-show="addItemsModalOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
         class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl shadow-slate-900/30 overflow-hidden flex flex-col max-h-[90vh]">

        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-white">Add Items to Batch</h3>
                    <p class="text-blue-200 text-xs mt-0.5">Select eligible items to sort into this batch</p>
                </div>
            </div>
            <button type="button" @@click="addItemsModalOpen = false" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-white flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Loading overlay -->
        <div x-show="addItemsModalLoading" x-cloak x-transition.opacity.duration.100ms
             class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-20 flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
        </div>

        <!-- Search bar -->
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50/60 flex-shrink-0">
            <div class="flex items-center justify-between gap-3">
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model.debounce.400ms="eligibleSearch" @@input="loadEligibleItems()"
                           placeholder="Search shipment, tracking, recipient…"
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-300 transition-colors">
                </div>
                <span x-show="selectedEligibleIds.length > 0"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 ring-1 ring-blue-200">
                    <span x-text="selectedEligibleIds.length + ' selected'"></span>
                    <button type="button" @@click="selectedEligibleIds = []" class="hover:text-blue-900">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </span>
            </div>
        </div>

        <!-- Items table -->
        <div class="flex-1 overflow-y-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-50/80 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 w-10"><span class="sr-only">Select</span></th>
                        <th class="px-3 py-2 text-left text-[10px] uppercase tracking-wider font-bold text-slate-500">Shipment / Item</th>
                        <th class="px-3 py-2 text-left text-[10px] uppercase tracking-wider font-bold text-slate-500">Destination</th>
                        <th class="px-3 py-2 text-center text-[10px] uppercase tracking-wider font-bold text-slate-500">Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="item in eligibleItems" :key="item.warehouse_receipt_item_id">
                        <tr class="group hover:bg-blue-50/40 transition-colors cursor-pointer"
                            :class="selectedEligibleIds.includes(item.warehouse_receipt_item_id) ? 'bg-blue-50/60' : ''"
                            @@click="selectedEligibleIds.includes(item.warehouse_receipt_item_id)
                                ? selectedEligibleIds = selectedEligibleIds.filter(id => id !== item.warehouse_receipt_item_id)
                                : selectedEligibleIds.push(item.warehouse_receipt_item_id)">
                            <td class="px-3 py-2.5">
                                <div class="w-4 h-4 rounded border-2 flex items-center justify-center transition-all"
                                     :class="selectedEligibleIds.includes(item.warehouse_receipt_item_id) ? 'bg-blue-600 border-blue-600' : 'border-slate-300 group-hover:border-blue-400'">
                                    <svg x-show="selectedEligibleIds.includes(item.warehouse_receipt_item_id)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </td>
                            <td class="px-3 py-2.5">
                                <p class="font-bold text-slate-900" x-text="item.shipment_number"></p>
                                <p class="text-slate-500 mt-0.5" x-text="item.item_description"></p>
                                <span class="text-[10px] text-slate-400 font-mono" x-text="item.tracking_code || '—'"></span>
                            </td>
                            <td class="px-3 py-2.5">
                                <p class="font-medium text-slate-700 truncate" x-text="item.destination?.recipient_name || '—'"></p>
                                <p class="text-[10px] text-slate-400 truncate" x-text="item.destination?.town || ''"></p>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 font-bold text-slate-700" x-text="item.received_quantity"></span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!addItemsModalLoading && eligibleItems.length === 0">
                        <td colspan="4" class="px-4 py-10 text-center">
                            <p class="text-xs font-medium text-slate-400">No eligible items available</p>
                            <p class="text-[10px] text-slate-300 mt-0.5">All warehouse receipt items may already be sorted</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between flex-shrink-0">
            <p class="text-[10px] text-slate-400">
                <span x-text="eligibleItems.length + ' item(s) available'"></span>
            </p>
            <div class="flex items-center gap-2">
                <button type="button" @@click="addItemsModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">Cancel</button>
                <button type="button"
                        @@click="addSelectedItems()"
                        :disabled="selectedEligibleIds.length === 0 || addItemsModalLoading"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-sm font-semibold text-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add <span x-text="selectedEligibleIds.length > 0 ? selectedEligibleIds.length + ' Item(s)' : 'Selected'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function sortBatchShow() {
    return {
        activeTab: 'overview',

        // Action state
        actionLoading: false,
        actionMessage: '',
        actionSuccess: true,

        // Items tab state
        items: [],
        itemsMeta: { total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        itemsSearch: '',
        itemsLoading: false,
        itemsLoaded: false,
        _searchTimeout: null,
        _itemsDataUrl: @json(route('admin.sort-batches.items-data', $batch->id)),
        _eligibleItemsUrl: @json(route('admin.sort-batches.eligible-items', $batch->id)),
        _addItemsUrl: @json(route('admin.sort-batches.add-items', $batch->id)),
        _removeItemUrl: @json(route('admin.sort-batches.remove-item', ['batch' => $batch->id, 'shipmentItem' => '__ITEM__'])),
        _sealUrl: @json(route('admin.sort-batches.seal', $batch->id)),
        _reopenUrl: @json(route('admin.sort-batches.reopen', $batch->id)),
        _createRunUrl: @json(route('admin.sort-batches.create-delivery-run', $batch->id)),
        _shipmentShowUrl: @json(route('admin.shipments.show', '__ID__')),
        _deliveryRunShowUrl: @json(route('admin.delivery-runs.show', '__ID__')),

        // Add Items modal state
        addItemsModalOpen: false,
        addItemsModalLoading: false,
        eligibleItems: [],
        eligibleSearch: '',
        selectedEligibleIds: [],

        init() {
            this.$watch('activeTab', (val) => {
                if (val === 'items' && !this.itemsLoaded) this.fetchItems(1);
            });
        },

        // ─── Items Tab ────────────────────────────────────────────────────────

        fetchItems(page) {
            this.itemsLoading = true;
            const params = new URLSearchParams({
                page: page || this.itemsMeta.current_page,
                per_page: this.itemsMeta.per_page,
            });
            if (this.itemsSearch) params.set('search', this.itemsSearch);

            fetch(this._itemsDataUrl + '?' + params.toString())
                .then(r => r.json())
                .then(json => {
                    this.items = json.data;
                    this.itemsMeta = json.meta;
                    this.itemsLoading = false;
                    this.itemsLoaded = true;
                })
                .catch(() => { this.itemsLoading = false; });
        },

        onItemsSearch() {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => this.fetchItems(1), 300);
        },

        goItemsPage(p) {
            if (p < 1 || p > this.itemsMeta.last_page) return;
            this.fetchItems(p);
        },

        shipmentUrl(id) {
            return this._shipmentShowUrl.replace('__ID__', id);
        },

        // ─── Add Items Modal ──────────────────────────────────────────────────

        openAddItemsModal() {
            this.eligibleSearch = '';
            this.selectedEligibleIds = [];
            this.eligibleItems = [];
            this.addItemsModalOpen = true;
            this.loadEligibleItems();
        },

        loadEligibleItems() {
            this.addItemsModalLoading = true;
            const params = new URLSearchParams({ per_page: 100 });
            if (this.eligibleSearch) params.set('search', this.eligibleSearch);
            fetch(this._eligibleItemsUrl + '?' + params.toString())
                .then(r => r.json())
                .then(json => {
                    this.eligibleItems = json.data || [];
                    this.addItemsModalLoading = false;
                })
                .catch(() => { this.addItemsModalLoading = false; });
        },

        async addSelectedItems() {
            if (!this.selectedEligibleIds.length) return;
            this.addItemsModalLoading = true;
            try {
                const resp = await fetch(this._addItemsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ warehouse_receipt_item_ids: this.selectedEligibleIds }),
                });
                const json = await resp.json();
                if (json.success) {
                    this.addItemsModalOpen = false;
                    this.selectedEligibleIds = [];
                    this.showAction(true, json.message || 'Items added successfully.');
                    // Reload the items tab
                    this.itemsLoaded = false;
                    if (this.activeTab === 'items') this.fetchItems(1);
                    else { this.activeTab = 'items'; }
                    // Reload the page after a short delay so counts/status update
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showAction(false, json.message || 'Failed to add items.');
                    this.addItemsModalLoading = false;
                }
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
                this.addItemsModalLoading = false;
            }
        },

        // ─── Remove Item ──────────────────────────────────────────────────────

        async removeItem(item) {
            if (!confirm(`Remove "${item.description || item.tracking_code || 'this item'}" from the batch?`)) return;
            this.actionLoading = true;
            const removeUrl = this._removeItemUrl.replace('__ITEM__', item.shipment_item_id);
            try {
                const resp = await fetch(removeUrl, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, json.message || (json.success ? 'Item removed.' : 'Failed to remove item.'));
                if (json.success) {
                    setTimeout(() => window.location.reload(), 800);
                }
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        // ─── Seal / Reopen ────────────────────────────────────────────────────

        async sealBatch() {
            if (!confirm('Seal this sort batch? No more items can be added after sealing.')) return;
            this.actionLoading = true;
            try {
                const resp = await fetch(this._sealUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, json.message || (json.success ? 'Batch sealed.' : 'Failed to seal batch.'));
                if (json.success) setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        async reopenBatch() {
            if (!confirm('Reopen this sort batch? Items can be added or removed again.')) return;
            this.actionLoading = true;
            try {
                const resp = await fetch(this._reopenUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, json.message || (json.success ? 'Batch reopened.' : 'Failed to reopen.'));
                if (json.success) setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        // ─── Create Delivery Run ──────────────────────────────────────────────

        async createDeliveryRun() {
            if (!confirm('Create a delivery run from this sealed batch? This cannot be undone.')) return;
            this.actionLoading = true;
            try {
                const resp = await fetch(this._createRunUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const json = await resp.json();
                this.showAction(json.success, json.message || (json.success ? 'Delivery run created.' : 'Failed to create delivery run.'));
                if (json.success) {
                    // Navigate to the delivery run if we have an id
                    const runId = json.data?.run?.id;
                    if (runId) {
                        setTimeout(() => {
                            window.location.href = this._deliveryRunShowUrl.replace('__ID__', runId);
                        }, 800);
                    } else {
                        setTimeout(() => window.location.reload(), 800);
                    }
                }
            } catch (e) {
                this.showAction(false, 'An unexpected error occurred.');
            } finally {
                this.actionLoading = false;
            }
        },

        // ─── Helpers ──────────────────────────────────────────────────────────

        showAction(success, message) {
            this.actionSuccess = success;
            this.actionMessage = message;
            if (success) {
                setTimeout(() => { this.actionMessage = ''; }, 5000);
            }
        },
    };
}
</script>
@endpush
