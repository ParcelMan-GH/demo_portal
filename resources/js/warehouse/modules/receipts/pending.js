import { createRemoteTablePage } from '../shared/table-page.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-pending-receipts-config]');
    if (!container) return null;

    try {
        return JSON.parse(container.getAttribute('data-warehouse-pending-receipts-config') || '{}');
    } catch (error) {
        console.error('Invalid pending receipts config JSON:', error);
        return null;
    }
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
            { key: 'target_warehouse', label: 'Target Warehouse', sortable: false },
        ],
    };

    Alpine.data('warehousePendingReceiptsPage', () => createRemoteTablePage(pageConfig));
}

if (window.Alpine) {
    registerPendingReceiptsPage();
} else {
    document.addEventListener('alpine:init', registerPendingReceiptsPage);
}
