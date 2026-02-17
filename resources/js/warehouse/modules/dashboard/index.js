function getDashboardConfig() {
    const el = document.querySelector('[data-warehouse-dashboard-config]');
    if (!el) return null;

    try {
        return JSON.parse(el.getAttribute('data-warehouse-dashboard-config'));
    } catch (error) {
        console.error('Invalid warehouse dashboard config JSON:', error);
        return null;
    }
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

