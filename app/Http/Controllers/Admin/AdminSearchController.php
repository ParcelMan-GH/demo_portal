<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSearchController extends Controller
{
    /**
     * Global admin search across Shipments, Vendors, Drivers, and Invoices.
     * Returns top 5 results per category.
     *
     * GET /admin/search?q=
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $like = '%' . $q . '%';

        $shipments = Shipment::query()
            ->with('vendor:id,name,business_name')
            ->where(function ($query) use ($like) {
                $query->where('shipment_number', 'like', $like)
                    ->orWhereHas('vendor', fn($v) => $v->where('name', 'like', $like)
                        ->orWhere('business_name', 'like', $like));
            })
            ->latest()
            ->limit(5)
            ->get(['id', 'shipment_number', 'status', 'vendor_id'])
            ->map(fn($s) => [
                'id'     => $s->id,
                'label'  => $s->shipment_number,
                'sub'    => $s->vendor?->business_name ?? $s->vendor?->name ?? '—',
                'status' => ucwords(str_replace('_', ' ', $s->status->value ?? $s->status)),
                'url'    => route('admin.shipments.show', $s->id),
            ]);

        $vendors = Vendor::query()
            ->where('name', 'like', $like)
            ->orWhere('business_name', 'like', $like)
            ->orWhere('phone', 'like', $like)
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'business_name', 'phone'])
            ->map(fn($v) => [
                'id'     => $v->id,
                'label'  => $v->business_name ?? $v->name,
                'sub'    => $v->phone,
                'status' => null,
                'url'    => route('admin.vendors.show', $v->id),
            ]);

        $drivers = Driver::query()
            ->where('name', 'like', $like)
            ->orWhere('phone', 'like', $like)
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'phone', 'is_active'])
            ->map(fn($d) => [
                'id'     => $d->id,
                'label'  => $d->name,
                'sub'    => $d->phone,
                'status' => $d->is_active ? 'Active' : 'Inactive',
                'url'    => route('admin.drivers.show', $d->id),
            ]);

        $invoices = Invoice::query()
            ->with('shipment:id,shipment_number,vendor_id', 'shipment.vendor:id,name,business_name')
            ->where('invoice_number', 'like', $like)
            ->latest()
            ->limit(5)
            ->get(['id', 'invoice_number', 'status', 'shipment_id', 'total_amount'])
            ->map(fn($i) => [
                'id'     => $i->id,
                'label'  => $i->invoice_number,
                'sub'    => $i->shipment?->vendor?->business_name ?? $i->shipment?->shipment_number ?? '—',
                'status' => ucwords(str_replace('_', ' ', $i->status->value ?? $i->status)),
                'url'    => route('admin.invoices.show', $i->id),
            ]);

        $data = [];

        if ($shipments->isNotEmpty()) {
            $data['shipments'] = $shipments->values()->all();
        }
        if ($vendors->isNotEmpty()) {
            $data['vendors'] = $vendors->values()->all();
        }
        if ($drivers->isNotEmpty()) {
            $data['drivers'] = $drivers->values()->all();
        }
        if ($invoices->isNotEmpty()) {
            $data['invoices'] = $invoices->values()->all();
        }

        return response()->json(['data' => $data]);
    }
}
