import { createRemoteTablePage } from '../shared/table-page.js';
import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-pending-receipts-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-pending-receipts-config', null);
    if (!config) {
        console.error('Invalid pending receipts config JSON');
    }

    return config;
}

function registerPendingReceiptsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.endpoint) return;

    const pageConfig = {
        endpoint: config.endpoint,
        defaultSort: 'assigned_at',
        exportFileName: 'warehouse-pending-receipts',
        printTitle: 'Warehouse Pending Receipts',
        statuses: config.statuses || [],
        columns: [
            { key: 'shipment_number', label: 'Shipment #', exportLabel: 'Shipment Number' },
            { key: 'driver_name', label: 'Driver', exportLabel: 'Driver Name' },
            { key: 'driver_phone', label: 'Driver Phone' },
            { key: 'status', label: 'Status' },
            { key: 'assigned_at', label: 'Assigned At' },
            { key: 'arrived_warehouse_at', label: 'Arrived Warehouse At' },
            { key: 'actions', label: 'Actions', sortable: false },
        ],
    };

    Alpine.data('warehousePendingReceiptsPage', () => {
        const page = createRemoteTablePage(pageConfig);

        return {
            ...page,
            statusBadgeClass(status) {
                switch ((status || '').toLowerCase()) {
                    case 'assigned':
                        return 'border-blue-200/60 bg-blue-50 text-blue-700';
                    case 'en_route':
                        return 'border-indigo-200/60 bg-indigo-50 text-indigo-700';
                    case 'arrived':
                        return 'border-amber-200/60 bg-amber-50 text-amber-700';
                    case 'picking_up':
                        return 'border-violet-200/60 bg-violet-50 text-violet-700';
                    case 'completed':
                        return 'border-emerald-200/60 bg-emerald-50 text-emerald-700';
                    case 'cancelled':
                        return 'border-rose-200/60 bg-rose-50 text-rose-700';
                    default:
                        return 'border-slate-200/50 bg-white/60 text-slate-700';
                }
            },
        };
    });
}

if (window.Alpine) {
    registerPendingReceiptsPage();
} else {
    document.addEventListener('alpine:init', registerPendingReceiptsPage);
}
