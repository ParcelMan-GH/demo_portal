@extends('admin.layouts.app')

@section('title', 'Walk-in Shipments')
@section('breadcrumb-parent', 'Operations')
@section('breadcrumb-current', 'Walk-ins')

@section('content')

@php
    // Generate a unique session ID for this specific desktop tab
    $uploadSessionId = \Illuminate\Support\Str::random(16);
    
    // Build mobile route using IP address
    $mobileUrl = url("/mobile-camera/{$uploadSessionId}");
    
    // Generate the SVG QR Code
    $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)
                    ->color(15, 23, 42) // Slate 900
                    ->generate($mobileUrl);

    $walkinConfig = [
        'vendorLookupUrl' => route('warehouse.walkin.vendor-lookup') ?? '',
        'vendorCreateUrl' => route('warehouse.walkin.vendor-create') ?? '',
        'storeUrl' => route('warehouse.walkin.store') ?? '',
        'printLabelsUrl' => route('warehouse.walkin.print-labels') ?? '',
        'locationSearchUrl' => route('warehouse.locations.search') ?? '',
        'transferWarehouses' => $transferWarehouses ?? [],
        'uploadSessionId' => $uploadSessionId,
        'debug' => (bool) config('app.debug'),
    ];
@endphp

<div x-data="walkinShipment()" x-init="init()" data-walkin-config='@json($walkinConfig)' class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 relative">

    {{-- ═══════════ INDEX VIEW (DASHBOARD) ═══════════ --}}
    <div x-show="!showWizard" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
        
        {{-- Header & Actions --}}
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">
                    @php $hour = now()->hour; @endphp
                    {{ $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening') }}, {{ Auth::guard('admin')->user()?->name ?? 'Admin' }}
                </h1>
                <p class="text-slate-500 mt-1">It's {{ now()->format('l, M j, Y') }}.</p>
            </div>
            <button @click="resetForm(); showWizard = true; step = 1" class="px-6 py-3 bg-[#ea580c] hover:bg-[#c2410c] text-white text-sm font-semibold rounded-xl shadow-sm transition-all active:scale-95 flex items-center gap-2 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Walk-ins
            </button>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            {{-- 1. Total Order --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm text-slate-500">Total Order (Walk-ins)</span>
                    <a href="#" class="text-xs text-orange-600 hover:text-orange-700 font-medium">See All</a>
                </div>
                <div class="text-4xl font-normal text-slate-900 mb-6">{{ number_format($totalWalkinsMonth ?? 0) }}</div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">vs last month</span>
                    <span class="{{ ($totalWalkinsChange ?? 0) >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
                        {{ ($totalWalkinsChange ?? 0) > 0 ? '+' : '' }}{{ $totalWalkinsChange ?? 0 }}%
                    </span>
                </div>
            </div>

            {{-- 2. Today Order --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm text-slate-500">Today Order (Walk-ins)</span>
                    <a href="#" class="text-xs text-orange-600 hover:text-orange-700 font-medium">See All</a>
                </div>
                <div class="text-4xl font-normal text-slate-900 mb-6">{{ number_format($todayWalkins ?? 0) }}</div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">vs yesterday</span>
                    <span class="{{ ($todayWalkinsChange ?? 0) >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
                        {{ ($todayWalkinsChange ?? 0) > 0 ? '+' : '' }}{{ $todayWalkinsChange ?? 0 }}%
                    </span>
                </div>
            </div>

            {{-- 3. Amount Made --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm text-slate-500">Amount Made (Walk-ins)</span>
                    <a href="#" class="text-xs text-orange-600 hover:text-orange-700 font-medium">See All</a>
                </div>
                <div class="text-4xl font-normal text-slate-900 mb-6">GH₵ {{ number_format($amountMadeMonth ?? 0, 2) }}</div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">vs last month</span>
                    <span class="{{ ($amountMadeChange ?? 0) >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
                        {{ ($amountMadeChange ?? 0) > 0 ? '+' : '' }}{{ $amountMadeChange ?? 0 }}%
                    </span>
                </div>
            </div>

            {{-- 4. On Time Delivery --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm text-slate-500">On Time Delivery</span>
                    <a href="#" class="text-xs text-orange-600 hover:text-orange-700 font-medium">See All</a>
                </div>
                <div class="text-4xl font-normal text-slate-900 mb-6">{{ $onTimeDeliveryRate ?? '100' }}%</div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">vs last month</span>
                    <span class="{{ ($onTimeChange ?? 0) >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
                        {{ ($onTimeChange ?? 0) > 0 ? '+' : '' }}{{ $onTimeChange ?? 0 }}%
                    </span>
                </div>
            </div>
        </div>

        {{-- Recent Activities Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col">
            <div class="px-6 py-4 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-semibold text-slate-900">Recent Activities</h2>
                </div>
            </div>

            <div class="overflow-x-auto min-h-[300px]">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-[#FFF8F3] border-y border-orange-100/50">
                        <tr>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-600">Tracking Code</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-600">Vendor/Sender</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-600">Amount of packages</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-600">Status</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-600">Total Price</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-600">Time</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-600 text-center">More</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="(order, idx) in recentOrders" :key="'index-' + (order.id || idx)">
                            <tr class="hover:bg-slate-50/50 transition-colors group cursor-pointer" @click="navigateToPackage(order)">
                                <td class="px-6 py-4 text-sm font-mono text-orange-600 font-semibold" x-text="order.shipment_number || ('#' + order.id)"></td>
                                <td class="px-6 py-4 text-sm text-slate-900" x-text="order.vendor?.name || 'Walk-in Vendor'"></td>
                                <td class="px-6 py-4 text-sm text-slate-900" x-text="(order.items_count || order.items?.length || 1) + ' package(s)'"></td>
                                <td class="px-6 py-4 text-sm text-slate-900" x-text="order.status || 'At Warehouse'"></td>
                                <td class="px-6 py-4 text-sm lowercase font-mono text-emerald-600 font-bold" x-text="'gh¢ ' + (parseFloat(order.total_fee || 0)).toFixed(2)"></td>
                                <td class="px-6 py-4 text-sm text-slate-900" x-text="order.time_formatted || '—'"></td>
                                
                                {{-- 3-Dot Action Menu --}}
                                <td class="px-6 py-4 text-center relative" x-data="{ open: false }" @click.outside="open = false" @click.stop>
                                    <button type="button" @click.stop="open = !open" class="text-slate-400 hover:text-slate-900 transition-colors p-1.5 rounded-lg hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-orange-500/20">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M5 12a2 2 0 110-4 2 2 0 010 4zm7 0a2 2 0 110-4 2 2 0 010 4zm7 0a2 2 0 110-4 2 2 0 010 4z"/>
                                        </svg>
                                    </button>

                                    <div x-show="open" 
                                         x-cloak 
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute right-6 top-12 z-[100] w-36 bg-white rounded-xl shadow-xl border border-slate-100 py-1 text-left divide-y divide-slate-100">
                                        
                                        <div class="py-1">
                                            <!-- View Action -->
                                            <button type="button" @click.stop="open = false; navigateToPackage(order)" class="w-full px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-orange-50 hover:text-[#ea580c] flex items-center gap-2.5 transition-colors group">
                                                <svg class="w-4 h-4 text-slate-400 group-hover:text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                View
                                            </button>

                                            <!-- Edit Action -->
                                            <button type="button" @click.stop="open = false; editPackage(order)" class="w-full px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-orange-50 hover:text-[#ea580c] flex items-center gap-2.5 transition-colors group">
                                                <svg class="w-4 h-4 text-slate-400 group-hover:text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Edit
                                            </button>
                                        </div>

                                        <div class="py-1">
                                            <!-- Print Action -->
                                            <button type="button" @click.stop="open = false; printPackageLabel(order)" class="w-full px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-orange-50 hover:text-[#ea580c] flex items-center gap-2.5 transition-colors group">
                                                <svg class="w-4 h-4 text-slate-400 group-hover:text-[#ea580c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                </svg>
                                                Print
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="recentOrders.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-sm">No walk-in shipments recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{-- ═══════════ END INDEX VIEW ═══════════ --}}


    {{-- ═══════════ FLAT CREATION / EDITING FORM ═══════════ --}}
    <div x-show="showWizard" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="max-w-5xl mx-auto min-h-screen bg-transparent">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-10 pt-4">
            <h1 class="text-[28px] font-medium text-slate-900 tracking-tight" x-text="editingOrderId ? ('Edit Walk-in Order #' + editingOrderId) : 'Add New Order'"></h1>
            <div class="flex items-center gap-3">
                <button @click="resetForm(); showWizard = false" class="px-6 py-2.5 bg-white border border-[#ea580c] text-[#ea580c] rounded-lg text-sm font-medium hover:bg-orange-50 transition-colors shadow-sm">
                    Cancel
                </button>
                <button @click="handleSaveOrder()" :disabled="submitting" class="px-6 py-2.5 bg-[#ea580c] text-white rounded-lg text-sm font-medium hover:bg-[#c2410c] transition-colors shadow-md shadow-orange-500/20 disabled:opacity-50 flex items-center gap-2">
                    <span x-show="!submitting" x-text="editingOrderId ? 'Update Order' : 'Save Order'"></span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>

        <!-- Global Error Banner -->
        <div x-show="submitError || vendorError" x-cloak class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm flex flex-col gap-1">
            <span x-show="vendorError" x-text="vendorError" class="font-semibold"></span>
            <span x-show="submitError" x-text="submitError" class="font-semibold"></span>
        </div>

        <!-- Vendor Details Section -->
        <div class="mb-12">
            <h2 class="text-xl font-medium text-slate-800 mb-6">Vendor Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                <div>
                    <label class="block text-sm text-slate-600 mb-2">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="newVendor.name" placeholder="John Doe" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-2">Business Name (Optional)</label>
                    <input type="text" x-model="newVendor.business_name" placeholder="John Doe Ventures" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-2">Phone <span class="text-rose-500">*</span></label>
                    <input type="tel" maxlength="10" x-model="vendorPhone" @input="normalizeVendorPhoneInput()" placeholder="050 893 6615" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-2">Email (Optional)</label>
                    <input type="email" x-model="newVendor.email" placeholder="john.doe@gmail.com" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                </div>
            </div>
        </div>

        <!-- Packages Details Section -->
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-medium text-slate-800">Packages Details</h2>
            </div>

            <div class="space-y-10">
                <template x-for="(item, idx) in items" :key="item.key">
                    <div class="relative">
                        
                        <!-- Upload Box / Image Thumbnails Display -->
                        <div class="w-full min-h-[176px] border border-slate-200 border-dashed rounded-xl bg-[#FAFAFA] p-4 mb-6 transition-colors flex flex-col items-center justify-center relative">
                            
                            <!-- Thumbnail Previews (If photos exist) -->
                            <template x-if="item.photos && item.photos.length > 0">
                                <div class="w-full">
                                    <div class="flex flex-wrap items-center gap-3 mb-3">
                                        <template x-for="(photo, photoIdx) in item.photos" :key="photoIdx">
                                            <div class="relative group w-24 h-24 rounded-lg overflow-hidden border border-slate-200 shadow-sm bg-white">
                                                <img :src="photo.preview || photo" class="w-full h-full object-cover">
                                                <button type="button" @click="removePhoto(item, photoIdx)" class="absolute top-1 right-1 bg-slate-900/70 hover:bg-rose-600 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                                <span x-show="photo.from_mobile" class="absolute bottom-1 left-1 right-1 bg-orange-600/90 text-[9px] text-white text-center rounded px-1 font-semibold truncate">Phone</span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <label class="px-3 py-1.5 bg-white text-slate-700 border border-slate-300 rounded-md text-xs font-bold cursor-pointer hover:bg-slate-50 transition-colors shadow-sm">
                                            + Add More Local
                                            <input type="file" class="hidden" accept="image/*" multiple @change="handleItemPhotos($event, item)">
                                        </label>
                                        <button type="button" @click="openQrModal(idx)" class="px-3 py-1.5 bg-[#FDF3EE] text-[#ea580c] border border-[#FDF3EE] hover:border-orange-200 rounded-md text-xs font-bold transition-colors shadow-sm">
                                            + Connect Phone Camera
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty Upload State -->
                            <template x-if="!item.photos || item.photos.length === 0">
                                <div class="flex flex-col items-center gap-2 z-10">
                                    <svg class="w-10 h-10 text-[#64748b] mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span class="text-sm font-semibold text-[#64748b]">No photos attached yet</span>
                                    
                                    <div class="flex items-center gap-3 mt-1">
                                        <label class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-md text-xs font-bold cursor-pointer hover:bg-slate-50 transition-colors shadow-sm">
                                            Upload File
                                            <input type="file" class="hidden" accept="image/*" multiple @change="handleItemPhotos($event, item)">
                                        </label>
                                        <span class="text-xs text-slate-400 font-medium">OR</span>
                                        <button type="button" @click="openQrModal(idx)" class="px-4 py-2 bg-[#FDF3EE] text-[#ea580c] border border-[#FDF3EE] hover:border-orange-200 rounded-md text-xs font-bold transition-colors shadow-sm">
                                            Connect Phone Camera
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Form Grid 1 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Package Type <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="item.description" placeholder="e.g. Shoes" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Quantity <span class="text-rose-500">*</span></label>
                                <input type="number" x-model="item.quantity" placeholder="1" min="1" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Delivery Fee (Optional)</label>
                                <input type="number" x-model="item.delivery_fee" placeholder="0.00" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                            </div>
                        </div>

                        <!-- Form Grid 2 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Recipient Name <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="item.delivery.recipient_name" placeholder="Jane Doe" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-600 mb-2">Recipient Phone <span class="text-rose-500">*</span></label>
                                <input type="tel" maxlength="10" x-model="item.delivery.recipient_phone" @input="normalizeDeliveryPhoneInput(item.delivery)" placeholder="050 893 6615" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                            </div>
                            <div class="relative" @click.outside="item.delivery._showDropdown = false">
                                <label class="block text-sm text-slate-600 mb-2">Delivery Location <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="item.delivery.locationQuery" @input="searchLocation(item.delivery)" @focus="item.delivery.locationResults?.length && (item.delivery._showDropdown = true)" placeholder="Search location" class="w-full bg-[#FAFAFA] border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-1 focus:ring-orange-500/50 transition-all placeholder-slate-300">
                                <!-- Location Dropdown -->
                                <div x-show="item.delivery._showDropdown" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                    <template x-for="loc in item.delivery.locationResults" :key="loc.id">
                                        <button type="button" @click="selectLocation(item.delivery, loc)" class="block w-full text-left px-4 py-2.5 hover:bg-orange-50 text-sm text-slate-700 border-b border-slate-50 last:border-0" x-text="loc.display"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Remove button -->
                        <div class="absolute -right-4 top-[180px] h-full" x-show="items.length > 1">
                            <button type="button" @click="removeItem(idx)" title="Remove Package" class="p-2 text-slate-300 hover:text-red-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            
            <div class="mt-6 mb-20">
                <button @click="addItem()" class="text-[#ea580c] hover:text-[#c2410c] font-medium text-sm flex items-center gap-1.5 transition-colors">
                    <span class="text-lg leading-none">+</span> Add Another Package
                </button>
            </div>
        </div>
    </div>
    {{-- ═══════════ END FLAT CREATION FORM ═══════════ --}}

    {{-- ═══════════ MOBILE QR HANDOFF MODAL ═══════════ --}}
    <template x-teleport="body">
        <div x-show="qrModalOpen" x-cloak class="fixed inset-0 z-[150] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" x-transition.opacity>
            <div @click.away="closeQrModal()" class="bg-white rounded-[2rem] p-8 max-w-sm w-full text-center shadow-2xl relative" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100">
                <button @click="closeQrModal()" class="absolute top-5 right-5 p-2 text-slate-400 hover:text-slate-700 bg-slate-50 rounded-full transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                
                <div class="w-16 h-16 bg-orange-100 text-[#ea580c] rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-inner">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                
                <h3 class="text-xl font-bold text-slate-900 mb-2">Connect Camera</h3>
                <p class="text-sm text-slate-500 mb-6 leading-relaxed">Point your phone's camera at this QR code to capture photos for <strong class="text-slate-700">Package <span x-text="activePackageIndex + 1"></span></strong>.</p>
                
                <div class="inline-block p-4 bg-white border-2 border-slate-100 rounded-2xl shadow-sm mb-6 relative">
                    <img x-show="qrPreview" :src="qrPreview" class="w-[200px] h-[200px] object-cover rounded-xl" alt="Uploaded photo">
                    <div x-show="!qrPreview">
                        {!! $qrCodeSvg !!}
                    </div>
                    <div x-show="qrPreview" class="absolute top-2 right-2 bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded-full">Received ✓</div>
                </div>
                
                <div x-show="!qrPreview" class="flex items-center justify-center gap-2.5 text-sm text-emerald-600 font-medium bg-emerald-50 py-2.5 px-4 rounded-xl inline-flex w-full">
                    <span class="relative flex h-2.5 w-2.5">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    Listening for mobile uploads...
                </div>
                <div x-show="qrPreview" class="flex items-center justify-center gap-2.5 text-sm text-emerald-600 font-medium bg-emerald-50 py-2.5 px-4 rounded-xl inline-flex w-full">
                    Photo received — you can close this window.
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
@include('shared.walkin-response-helpers')

function walkinShipment() {
    return {
        showWizard: false, 
        editingOrderId: null,

        config: {},
        transferWarehouses: [],
        vendorPhone: '',
        vendorId: null,
        vendorData: null,
        newVendor: { name: '', business_name: '', phone: '', email: '' },
        vendorError: '',
        
        items: [],
        itemSeed: 0,
        submitting: false,
        submitError: '',
        recentOrders: @json($recentWalkins ?? []),

        /* ---- WEBSOCKET HANDOFF ---- */
        qrModalOpen: false,
        qrPreview: null,
        qrPollTimer: null,
        activePackageIndex: 0,

        init() {
            this.config = JSON.parse(this.$el.dataset.walkinConfig);
            this.transferWarehouses = Array.isArray(this.config.transferWarehouses) ? this.config.transferWarehouses : [];
            this.items = [this.makeItem()];

            // Set up Laravel Echo WebSocket Listener
            if (typeof window.Echo !== 'undefined') {
                window.Echo.channel(`walkin-uploads.${this.config.uploadSessionId}`)
                    .listen('.PhotoUploaded', (e) => {
                        this.handleReceivedPhoto(e.temp_path);
                    });
            }
        },

        handleReceivedPhoto(tempPath) {
            if (!tempPath) return;
            const targetItem = this.items[this.activePackageIndex];
            if (!targetItem) return;

            // 1. Save mobile temp path for submission
            targetItem.mobilePhotos = targetItem.mobilePhotos || [];
            if (targetItem.mobilePhotos.includes(tempPath)) return; // dedupe
            targetItem.mobilePhotos.push(tempPath);

            // 2. Create preview object for UI thumbnails
            targetItem.photos = targetItem.photos || [];
            targetItem.photos.push({
                file: null,
                preview: `/storage/${tempPath}`,
                from_mobile: true,
                temp_path: tempPath
            });

            // 3. Show the received photo in place of the QR, then close
            this.qrPreview = `/storage/${tempPath}`;
            this.stopQrPolling();
            setTimeout(() => { this.qrModalOpen = false; }, 1200);
        },

        startQrPolling() {
            this.stopQrPolling();
            this.qrPollTimer = setInterval(() => this.checkForMobilePhotos(), 2500);
        },

        stopQrPolling() {
            if (this.qrPollTimer) {
                clearInterval(this.qrPollTimer);
                this.qrPollTimer = null;
            }
        },

        async checkForMobilePhotos() {
            try {
                const res = await fetch(`/mobile-camera/${this.config.uploadSessionId}/photos`, {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                (Array.isArray(data.photos) ? data.photos : []).forEach((path) => {
                    this.handleReceivedPhoto(path);
                });
            } catch (e) {
                // transient network error — keep polling
            }
        },

        closeQrModal() {
            this.stopQrPolling();
            this.qrModalOpen = false;
        },

        resetForm() {
            this.editingOrderId = null;
            this.vendorPhone = '';
            this.vendorId = null;
            this.vendorData = null;
            this.newVendor = { name: '', business_name: '', phone: '', email: '' };
            this.vendorError = '';
            this.submitError = '';
            this.items = [this.makeItem()];
        },

        openQrModal(index) {
            this.activePackageIndex = index;
            this.qrPreview = null;
            this.qrModalOpen = true;
            this.startQrPolling();
        },

        makeDelivery() {
            return {
                recipient_name: '', recipient_phone: '', locationQuery: '',
                locationResults: [], locationError: '', selectedLocation: null, _showDropdown: false,
                region_id: '', district_id: '', town: '', instructions: '',
            };
        },

        makeItem() {
            this.itemSeed += 1;
            return {
                key: `package-${Date.now()}-${this.itemSeed}`,
                description: '', quantity: 1, delivery_fee: '', delivery_method: 'direct',
                forward_to_warehouse_id: '', delivery: this.makeDelivery(), 
                photos: [], mobilePhotos: [],
            };
        },

        addItem() {
            this.items.push(this.makeItem());
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        handleItemPhotos(event, item) {
            const files = Array.from(event.target.files || []);
            item.photos = item.photos || [];
            
            files.forEach(file => {
                item.photos.push({
                    file: file,
                    preview: URL.createObjectURL(file),
                    from_mobile: false
                });
            });
        },

        removePhoto(item, index) {
            const removed = item.photos[index];
            if (removed && removed.from_mobile) {
                item.mobilePhotos = (item.mobilePhotos || []).filter(path => path !== removed.temp_path);
            }
            item.photos.splice(index, 1);
        },

        /* ---- PHONE FORMATTING ---- */
        normalizePhoneValue(value) { return String(value || '').replace(/\D/g, '').slice(0, 10); },

        normalizeVendorPhoneInput() {
            this.vendorPhone = this.normalizePhoneValue(this.vendorPhone);
            this.vendorError = '';
            
            if (this.vendorPhone.length === 10) {
                this.lookupVendorBackground();
            }
        },

        normalizeDeliveryPhoneInput(delivery) {
            if (!delivery) return;
            delivery.recipient_phone = this.normalizePhoneValue(delivery.recipient_phone);
            this.submitError = '';
        },

        async lookupVendorBackground() {
            if(this.vendorPhone.length !== 10) return;
            try {
                const res = await fetch(this.config.vendorLookupUrl + '?phone=' + encodeURIComponent(this.vendorPhone), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const response = await window.ParcelmanWalkinResponse.parse(res, { debug: this.config.debug });
                if (response.json && response.json.found) {
                    this.vendorId = response.json.vendor.id;
                    this.vendorData = response.json.vendor;
                    
                    this.newVendor.name = this.vendorData.name;
                    this.newVendor.business_name = this.vendorData.business_name;
                    this.vendorError = '';
                } else {
                    this.vendorId = null;
                }
            } catch (e) {}
        },

        async handleSaveOrder() {
            this.submitError = '';
            this.vendorError = '';

            if(!this.newVendor.name) { 
                this.vendorError = "Vendor Full Name is required."; 
                return; 
            }
            if(!this.vendorPhone || this.vendorPhone.length < 10) { 
                this.vendorError = "Vendor Phone must be exactly 10 digits."; 
                return; 
            }

            for(let i=0; i<this.items.length; i++) {
                let item = this.items[i];
                if(!item.description || !item.quantity || !item.delivery.recipient_name || !item.delivery.recipient_phone || !item.delivery.locationQuery) {
                    this.submitError = `Please complete all required fields (Type, Qty, Recipient Name, Phone, and Location) for Package ${i+1}.`;
                    return;
                }
                if(item.delivery.recipient_phone.length < 10) {
                    this.submitError = `Package ${i+1} Recipient Phone must be exactly 10 digits.`;
                    return;
                }
            }

            this.submitting = true;

            if(!this.vendorId) {
                this.newVendor.phone = this.vendorPhone;
                try {
                    const res = await fetch(this.config.vendorCreateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.newVendor),
                    });
                    const response = await window.ParcelmanWalkinResponse.parse(res, { debug: this.config.debug });
                    
                    if (!res.ok || !response.json) {
                        this.vendorError = response.message || "Failed to register vendor account.";
                        this.submitting = false;
                        return;
                    }
                    this.vendorId = response.json.vendor.id;
                } catch(e) {
                    this.vendorError = "Network error while registering vendor.";
                    this.submitting = false;
                    return;
                }
            }

            await this.submitShipment();
        },

        searchLocation(target) {
            const query = target.locationQuery.trim();
            if (query.length < 2) {
                target.locationResults = []; target._showDropdown = false;
                return;
            }
            clearTimeout(target._timeout);
            target._timeout = setTimeout(async () => {
                try {
                    const res = await fetch(this.config.locationSearchUrl + '?q=' + encodeURIComponent(query), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    const response = await window.ParcelmanWalkinResponse.parse(res, { debug: this.config.debug });
                    if (res.ok && response.json) {
                        target.locationResults = response.json.locations || [];
                        target._showDropdown = target.locationResults.length > 0;
                    }
                } catch (error) {}
            }, 250);
        },

        selectLocation(target, location) {
            target.selectedLocation = location;
            target.locationQuery = location.display;
            target.locationResults = [];
            target._showDropdown = false;
            target.region_id = location.region?.id || '';
            target.district_id = location.district?.id || '';
            target.town = location.name || location.display;
        },

        async submitShipment() {
            const items = this.items.map(item => ({
                description: item.description,
                quantity: item.quantity,
                delivery_fee: parseFloat(item.delivery_fee) || 0,
                delivery_method: item.delivery_method,
                delivery: {
                    recipient_name: item.delivery.recipient_name,
                    recipient_phone: item.delivery.recipient_phone,
                    region_id: item.delivery.region_id || null,
                    district_id: item.delivery.district_id || null,
                    town: item.delivery.town || item.delivery.locationQuery,
                },
            }));

            const formData = new FormData();
            formData.append('vendor_id', this.vendorId);
            formData.append('fulfillment_type', 'warehouse');
            formData.append('delivery_preference', 'deliver');
            formData.append('destination_mode', 'per_item');
            formData.append('items_json', JSON.stringify(items));

            if (this.editingOrderId) {
                formData.append('shipment_id', this.editingOrderId);
                formData.append('_method', 'PUT');
            }
            
            this.items.forEach((item, index) => {
                // Attach local files
                (item.photos || []).forEach(p => {
                    if(!p.from_mobile && p.file) formData.append(`item_photos[${index}][]`, p.file);
                });
                
                // Attach mobile paths received via WebSockets
                (item.mobilePhotos || []).forEach(path => {
                    formData.append(`mobile_photos[${index}][]`, path);
                });
            });

            try {
                const res = await fetch(this.config.storeUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                
                const response = await window.ParcelmanWalkinResponse.parse(res, { debug: this.config.debug });
                
                if (!res.ok || !response.json) {
                    this.submitError = response.message || "Failed to submit order.";
                    this.submitting = false;
                    return;
                }
                
                window.location.reload();

            } catch (error) {
                this.submitError = 'Network error while creating shipment. Please try again.';
                this.submitting = false;
            }
        },

        /* ---- VIEW, EDIT & PRINT ACTIONS ---- */
        navigateToPackage(order) {
            if (!order) return;

            // 1. Prefer the shipment/order detail page (shows every package in the order)
            if (order.id) {
                window.location.href = `/admin/operations/shipments/${order.id}`;
                return;
            }

            // 2. Otherwise search the package tracking list by shipment number
            if (order.shipment_number) {
                window.location.href = `/admin/operations/package-tracking?search=${encodeURIComponent(order.shipment_number)}`;
                return;
            }

            // 3. Fallback: Open in-page details/edit wizard
            this.editPackage(order);
        },

        editPackage(order) {
            if (!order) return;

            this.editingOrderId = order.id || null;

            // 1. Populate Vendor Details
            this.vendorId = order.vendor?.id || order.vendor_id || null;
            this.vendorPhone = order.vendor?.phone || '';
            this.newVendor = {
                name: order.vendor?.name || '',
                business_name: order.vendor?.business_name || '',
                phone: order.vendor?.phone || '',
                email: order.vendor?.email || ''
            };

            // 2. Populate Package Items
            const rawItems = order.items || order.packages || [];
            if (Array.isArray(rawItems) && rawItems.length > 0) {
                this.items = rawItems.map((item, idx) => ({
                    key: `package-edit-${item.id || idx}-${Date.now()}`,
                    description: item.description || item.package_type || '',
                    quantity: item.quantity || 1,
                    delivery_fee: item.delivery_fee || item.price || 0,
                    delivery_method: item.delivery_method || 'direct',
                    forward_to_warehouse_id: item.forward_to_warehouse_id || '',
                    delivery: {
                        recipient_name: item.delivery?.recipient_name || item.recipient_name || '',
                        recipient_phone: item.delivery?.recipient_phone || item.recipient_phone || '',
                        locationQuery: item.delivery?.town || item.location || item.destination || '',
                        locationResults: [],
                        _showDropdown: false,
                        region_id: item.delivery?.region_id || '',
                        district_id: item.delivery?.district_id || '',
                        town: item.delivery?.town || item.location || '',
                    },
                    photos: (item.photos || []).map(p => ({
                        file: null,
                        preview: typeof p === 'string' ? (p.startsWith('http') || p.startsWith('/') ? p : `/storage/${p}`) : (p.url || p.preview || ''),
                        from_mobile: false
                    })),
                    mobilePhotos: []
                }));
            } else {
                this.items = [this.makeItem()];
            }

            // 3. Open Creation/Editing Wizard
            this.showWizard = true;
            this.step = 1;
        },

        async printPackageLabel(order) {
            if (!order.id) return;
            const targetId = order.package_id || order.items?.[0]?.id || order.id;

            try {
                const res = await fetch(this.config.printLabelsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        package_id: targetId,
                        packages: [{ shipment_item_id: targetId, label_count: 1 }]
                    }),
                });

                const json = await res.json().catch(() => ({}));

                if (res.ok && json.success !== false) {
                    const html = json.data?.label_html || json.label_html || json.html;
                    if (html) {
                        const popup = window.open('', '_blank', 'width=900,height=650');
                        if (popup) {
                            popup.document.open();
                            popup.document.write(html);
                            popup.document.close();
                        }
                    } else if (json.data?.redirect_url || json.redirect_url) {
                        window.open(json.data?.redirect_url || json.redirect_url, '_blank');
                    } else {
                        window.print();
                    }
                } else {
                    alert(json.message || 'Error generating print label.');
                }
            } catch (err) {
                console.error('Print request error:', err);
                alert('Network error while requesting print label.');
            }
        },
    };
}
</script>
@endpush