import { createRemoteTablePage } from '../shared/table-page.js';
import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-incoming-manifests-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-incoming-manifests-config', null);
    if (!config) {
        console.error('Invalid warehouse incoming manifests config JSON');
    }

    return config;
}

function registerWarehouseIncomingManifestsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.endpoint) return;

    const columns = [
        { key: 'manifest_number', label: 'Manifest #' },
        { key: 'origin_warehouse', label: 'Origin Warehouse' },
    ];

    if (config.showDestinationWarehouse) {
        columns.push({ key: 'destination_warehouse', label: 'Destination Warehouse' });
    }

    columns.push(
        { key: 'driver_name', label: 'Driver' },
        { key: 'status', label: 'Status' },
        { key: 'items_count', label: 'Items' },
        { key: 'arrived_at', label: 'Arrived At' },
        { key: 'received_at', label: 'Received At' },
        { key: 'actions', label: 'Actions', sortable: false },
    );

    const pageConfig = {
        endpoint: config.endpoint,
        defaultSort: 'created_at',
        exportFileName: 'warehouse-incoming-manifests',
        printTitle: 'Warehouse Incoming Manifests',
        statuses: config.statuses || [],
        columns,
    };

    Alpine.data('warehouseIncomingManifestsPage', () => {
        const page = createRemoteTablePage(pageConfig);

        return {
            ...page,
            statusBadgeClass(status) {
                switch ((status || '').toLowerCase()) {
                    case 'draft':
                        return 'border-slate-200/70 bg-slate-50 text-slate-700';
                    case 'assigned':
                        return 'border-blue-200/70 bg-blue-50 text-blue-700';
                    case 'loading':
                        return 'border-indigo-200/70 bg-indigo-50 text-indigo-700';
                    case 'in_transit':
                        return 'border-violet-200/70 bg-violet-50 text-violet-700';
                    case 'arrived':
                        return 'border-amber-200/70 bg-amber-50 text-amber-700';
                    case 'received':
                        return 'border-emerald-200/70 bg-emerald-50 text-emerald-700';
                    case 'cancelled':
                        return 'border-rose-200/70 bg-rose-50 text-rose-700';
                    default:
                        return 'border-slate-200/70 bg-slate-50 text-slate-700';
                }
            },
        };
    });
}

if (window.Alpine) {
    registerWarehouseIncomingManifestsPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseIncomingManifestsPage);
}
