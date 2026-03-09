<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DriversExport;
use App\Http\Controllers\Controller;
use App\Models\SortBatch;
use App\Models\Warehouse;
use App\Support\GenericPdfExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminSortBatchController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code']);

        $dispatchModes = [
            ['value' => SortBatch::DISPATCH_TRANSFER,       'label' => 'Transfer'],
            ['value' => SortBatch::DISPATCH_LOCAL_DELIVERY, 'label' => 'Local Delivery'],
        ];

        $statuses = [
            ['value' => SortBatch::STATUS_OPEN,   'label' => 'Open'],
            ['value' => SortBatch::STATUS_SEALED, 'label' => 'Sealed'],
        ];

        return view('admin.sort-batches.index', compact('warehouses', 'dispatchModes', 'statuses'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = SortBatch::with([
            'originWarehouse',
            'destinationWarehouse',
            'createdBy',
            'transportManifest',
            'deliveryRun',
        ])->withCount('activeItems');

        if ($search = $request->get('search')) {
            $query->where('batch_number', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dispatchMode = $request->get('dispatch_mode')) {
            $query->where('dispatch_mode', $dispatchMode);
        }

        if ($originWarehouseId = $request->get('origin_warehouse_id')) {
            $query->where('origin_warehouse_id', $originWarehouseId);
        }

        $sortBy        = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts  = ['batch_number', 'status', 'dispatch_mode', 'sealed_at', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $batches = $query->paginate($perPage);

        return response()->json([
            'data' => $batches->map(function (SortBatch $batch) {
                return [
                    'id'                     => $batch->id,
                    'batch_number'           => $batch->batch_number,
                    'status'                 => $batch->status,
                    'dispatch_mode'          => $batch->dispatch_mode,
                    'dispatch_mode_label'    => $batch->dispatch_mode === SortBatch::DISPATCH_TRANSFER
                        ? 'Transfer'
                        : 'Local Delivery',
                    'origin_warehouse'       => $batch->originWarehouse
                        ? ['id' => $batch->originWarehouse->id, 'name' => $batch->originWarehouse->name, 'code' => $batch->originWarehouse->code]
                        : null,
                    'destination_warehouse'  => $batch->destinationWarehouse
                        ? ['id' => $batch->destinationWarehouse->id, 'name' => $batch->destinationWarehouse->name, 'code' => $batch->destinationWarehouse->code]
                        : null,
                    'items_count'            => $batch->active_items_count,
                    'created_by_name'        => $batch->createdBy?->name,
                    'sealed_at'              => $batch->sealed_at?->format('Y-m-d H:i:s'),
                    'created_at'             => $batch->created_at->format('Y-m-d H:i:s'),
                    'has_manifest'           => $batch->transportManifest !== null,
                    'manifest_number'        => $batch->transportManifest?->manifest_number,
                    'manifest_status'        => $batch->transportManifest?->status,
                    'manifest_id'            => $batch->transportManifest?->id,
                    'has_delivery_run'       => $batch->deliveryRun !== null,
                    'run_number'             => $batch->deliveryRun?->run_number,
                    'run_status'             => $batch->deliveryRun?->status,
                    'run_id'                 => $batch->deliveryRun?->id,
                ];
            }),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'from'         => $batches->firstItem() ?? 0,
                'to'           => $batches->lastItem() ?? 0,
                'total'        => $batches->total(),
                'last_page'    => $batches->lastPage(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $query = SortBatch::with(['originWarehouse', 'destinationWarehouse', 'createdBy'])
            ->withCount('activeItems');

        if ($search = $request->get('search')) {
            $query->where('batch_number', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dispatchMode = $request->get('dispatch_mode')) {
            $query->where('dispatch_mode', $dispatchMode);
        }

        if ($originWarehouseId = $request->get('origin_warehouse_id')) {
            $query->where('origin_warehouse_id', $originWarehouseId);
        }

        $batches = $query->orderBy('created_at', 'desc')->get();

        $rows = $batches->map(fn(SortBatch $b) => [
            'Batch #'       => $b->batch_number,
            'From'          => $b->originWarehouse?->name ?? '—',
            'To'            => $b->destinationWarehouse?->name ?? '—',
            'Mode'          => $b->dispatch_mode === SortBatch::DISPATCH_TRANSFER ? 'Transfer' : 'Local Delivery',
            'Items'         => $b->active_items_count,
            'Status'        => ucfirst($b->status),
            'Created By'    => $b->createdBy?->name ?? '—',
            'Sealed At'     => $b->sealed_at?->format('Y-m-d H:i:s') ?? '—',
            'Created At'    => $b->created_at->format('Y-m-d H:i:s'),
        ])->values()->toArray();

        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return Excel::download(new DriversExport($rows), 'sort_batches_' . date('Y-m-d_His') . '.xlsx');
        }

        if ($format === 'pdf') {
            return GenericPdfExporter::download($rows, 'sort_batches_' . date('Y-m-d_His') . '.pdf', 'Sort Batches');
        }

        return response()->json(['data' => $rows]);
    }

    public function show(SortBatch $batch): View
    {
        $batch->load([
            'originWarehouse',
            'destinationWarehouse',
            'createdBy',
            'sealedBy',
            'activeItems.shipmentItem.shipment',
            'transportManifest',
            'deliveryRun',
        ]);

        return view('admin.sort-batches.show', compact('batch'));
    }
}
