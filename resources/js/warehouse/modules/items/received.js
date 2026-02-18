import { createRemoteTablePage } from '../shared/table-page.js';
import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-received-items-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-received-items-config', null);
    if (!config) {
        console.error('Invalid received items config JSON');
    }

    return config;
}

function registerReceivedItemsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.endpoint) return;

    const pageConfig = {
        endpoint: config.endpoint,
        defaultSort: 'received_at',
        exportFileName: 'warehouse-received-items',
        printTitle: 'Warehouse Received Items',
        columns: [
            { key: 'shipment_number', label: 'Shipment #', exportLabel: 'Shipment Number' },
            { key: 'item_description', label: 'Item' },
            { key: 'expected_quantity', label: 'Expected Qty' },
            { key: 'received_quantity', label: 'Received Qty' },
            { key: 'damaged_quantity', label: 'Damaged Qty' },
            { key: 'driver_name', label: 'Driver' },
            { key: 'received_at', label: 'Received At' },
            { key: 'discrepancy_type', label: 'Discrepancy' },
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
