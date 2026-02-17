import { createRemoteTablePage } from '../shared/table-page.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-received-items-config]');
    if (!container) return null;

    try {
        return JSON.parse(container.getAttribute('data-warehouse-received-items-config') || '{}');
    } catch (error) {
        console.error('Invalid received items config JSON:', error);
        return null;
    }
}

function registerReceivedItemsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.endpoint) return;

    const pageConfig = {
        endpoint: config.endpoint,
        defaultSort: 'confirmed_at',
        exportFileName: 'warehouse-received-items',
        printTitle: 'Warehouse Received Items',
        columns: [
            { key: 'shipment_number', label: 'Shipment #', exportLabel: 'Shipment Number' },
            { key: 'item_description', label: 'Item' },
            { key: 'expected_quantity', label: 'Expected Qty' },
            { key: 'confirmed_quantity', label: 'Confirmed Qty' },
            { key: 'driver_name', label: 'Driver' },
            { key: 'confirmed_at', label: 'Confirmed At' },
            { key: 'notes', label: 'Notes', sortable: false },
        ],
    };

    Alpine.data('warehouseReceivedItemsPage', () => createRemoteTablePage(pageConfig));
}

if (window.Alpine) {
    registerReceivedItemsPage();
} else {
    document.addEventListener('alpine:init', registerReceivedItemsPage);
}
