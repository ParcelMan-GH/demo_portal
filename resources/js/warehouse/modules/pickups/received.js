import { createRemoteTablePage } from '../shared/table-page.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-received-pickups-config]');
    if (!container) return null;

    try {
        return JSON.parse(container.getAttribute('data-warehouse-received-pickups-config') || '{}');
    } catch (error) {
        console.error('Invalid received pickups config JSON:', error);
        return null;
    }
}

function registerReceivedPickupsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.endpoint) return;

    const pageConfig = {
        endpoint: config.endpoint,
        defaultSort: 'received_at',
        exportFileName: 'warehouse-received-pickups',
        printTitle: 'Warehouse Received Pickups',
        statuses: config.statuses || [],
        columns: [
            { key: 'shipment_number', label: 'Shipment #', exportLabel: 'Shipment Number' },
            { key: 'driver_name', label: 'Driver', exportLabel: 'Driver Name' },
            { key: 'driver_phone', label: 'Driver Phone' },
            { key: 'status', label: 'Status' },
            { key: 'assigned_at', label: 'Assigned At' },
            { key: 'arrived_warehouse_at', label: 'Arrived Warehouse At' },
            { key: 'received_at', label: 'Received At' },
            { key: 'receive_notes', label: 'Receive Notes', sortable: false },
        ],
    };

    Alpine.data('warehouseReceivedPickupsPage', () => createRemoteTablePage(pageConfig));
}

if (window.Alpine) {
    registerReceivedPickupsPage();
} else {
    document.addEventListener('alpine:init', registerReceivedPickupsPage);
}
