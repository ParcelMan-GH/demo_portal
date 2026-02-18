import { parseJsonAttribute } from '../../core/config.js';

function getDashboardConfig() {
    const el = document.querySelector('[data-warehouse-dashboard-config]');
    if (!el) return null;

    const config = parseJsonAttribute(el, 'data-warehouse-dashboard-config', null);
    if (!config) {
        console.error('Invalid warehouse dashboard config JSON');
    }

    return config;
}

function buildWarehouseDashboard(config) {
    return {
        warehouseName: config.warehouse_name || 'Warehouse',
        init() {},
    };
}

function registerWarehouseDashboard() {
    if (!window.Alpine) return;
    const config = getDashboardConfig();
    if (!config) return;

    window.warehouseDashboardPage = () => buildWarehouseDashboard(config);
    Alpine.data('warehouseDashboardPage', window.warehouseDashboardPage);
}

if (window.Alpine) {
    registerWarehouseDashboard();
} else {
    document.addEventListener('alpine:init', registerWarehouseDashboard);
}
