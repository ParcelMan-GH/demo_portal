export function createReceivingWorkspaceState() {
    return {
        receivingItems() {
            if (Array.isArray(this.receiving?.packages)) return this.receiving.packages;
            if (Array.isArray(this.items)) return this.items;
            if (Array.isArray(this.shipment?.items)) return this.shipment.items;
            return [];
        },

        receivingExpectedQuantity(item) {
            const expected = Number(item?.expected_quantity ?? item?.driver_confirmed_quantity ?? item?.vendor_quantity ?? item?.quantity ?? 0);
            return Number.isFinite(expected) ? expected : 0;
        },

        receivingObservedQuantity(item) {
            const received = Number(item?.received_quantity ?? 0);
            const damaged = Number(item?.damaged_quantity ?? 0);

            return (Number.isFinite(received) ? received : 0) + (Number.isFinite(damaged) ? damaged : 0);
        },

        receivingPendingQuantity(item) {
            return Math.max(this.receivingExpectedQuantity(item) - this.receivingObservedQuantity(item), 0);
        },

        receivingPackageIsReceived(item) {
            return Number(item?.received_quantity ?? 0) > 0;
        },

        receivingPackageHasReceipt(item) {
            if (item?.receipt_item_id) return true;
            if (item?.barcode_value) return true;
            if (Array.isArray(item?.photos) && item.photos.length > 0) return true;
            if (Number(item?.received_quantity ?? 0) > 0) return true;
            if (Number(item?.damaged_quantity ?? 0) > 0) return true;
            return Boolean(item?.discrepancy_type && item.discrepancy_type !== 'none');
        },

        receivingPackageCount() {
            return this.receivingItems().length;
        },

        receivingReceivedPackageCount() {
            return this.receivingItems().filter((item) => this.receivingPackageIsReceived(item)).length;
        },

        receivingPendingPackageCount() {
            return Math.max(this.receivingPackageCount() - this.receivingReceivedPackageCount(), 0);
        },

        receivingReceivedUnits() {
            return this.receivingItems().reduce((total, item) => {
                const received = Number(item?.received_quantity ?? 0);
                return total + (Number.isFinite(received) ? received : 0);
            }, 0);
        },

        receivingExpectedUnits() {
            return this.receivingItems().reduce((total, item) => total + this.receivingExpectedQuantity(item), 0);
        },

        receivingPendingUnits() {
            return this.receivingItems().reduce((total, item) => total + this.receivingPendingQuantity(item), 0);
        },

        receivingAllPackagesReceived() {
            const items = this.receivingItems();
            if (items.length === 0) return false;
            return items.every((item) => this.receivingPackageHasReceipt(item));
        },

        pickupVehicleSummary() {
            if (this.shipment?.pickup_vehicle_summary) {
                return this.shipment.pickup_vehicle_summary;
            }

            const rows = Array.isArray(this.shipment?.pickup_vehicles)
                ? this.shipment.pickup_vehicles
                : (Array.isArray(this.shipment?.pickup_vehicle_requests) ? this.shipment.pickup_vehicle_requests : []);

            const labels = rows
                .map((row) => {
                    const quantity = Number(row?.quantity || 0);
                    const name = row?.name || row?.vehicle_name || row?.vehicle_name_snapshot || row?.vehicle_type?.name || 'Vehicle';
                    if (!quantity || !name) return null;
                    return `${quantity} ${name}`;
                })
                .filter(Boolean);

            return labels.length ? labels.join(', ') : '';
        },

        discrepancyCount() {
            return this.receivingItems().filter((item) => (item.discrepancy_type || 'none') !== 'none').length;
        },

        receivingPackageActionLabel(item) {
            return this.receivingPackageIsReceived(item) ? 'Edit' : 'Receive';
        },

        receivingPackageStatusLabel(item) {
            const discrepancy = item?.discrepancy_type || 'none';
            if (discrepancy !== 'none') return discrepancy.replace(/_/g, ' ');
            if (this.receivingPackageIsReceived(item)) return 'Received';
            return this.receivingPendingQuantity(item) > 0 ? 'Pending' : 'Ready';
        },

        receivingPackageStatusClass(item) {
            if (this.receivingPackageIsReceived(item)) {
                return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            }

            return this.receivingPendingQuantity(item) > 0
                ? 'bg-amber-50 text-amber-700 border-amber-200'
                : 'bg-slate-50 text-slate-600 border-slate-200';
        },

        receivingPackageStatusTextClass(item) {
            const discrepancy = item?.discrepancy_type || 'none';
            if (discrepancy !== 'none') return 'text-amber-700';
            if (this.receivingPackageIsReceived(item)) return 'text-emerald-700';
            return this.receivingPendingQuantity(item) > 0 ? 'text-amber-700' : 'text-slate-400';
        },

        receivingConditionLabel(condition) {
            switch (condition || 'ok') {
                case 'damaged':
                    return 'Damaged';
                case 'partial':
                    return 'Partial';
                default:
                    return 'Good';
            }
        },

        receivingConditionClass(condition) {
            switch (condition || 'ok') {
                case 'damaged':
                    return 'bg-rose-50 text-rose-700 border-rose-200';
                case 'partial':
                    return 'bg-amber-50 text-amber-700 border-amber-200';
                default:
                    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            }
        },

        receivingConditionTextClass(condition) {
            switch (condition || 'ok') {
                case 'damaged':
                    return 'text-rose-700';
                case 'partial':
                    return 'text-amber-700';
                default:
                    return 'text-emerald-700';
            }
        },
    };
}
