/**
 * Admin Panel JavaScript Entry Point
 * This file is bundled by Vite and includes all admin-specific functionality
 */

import Alpine from 'alpinejs';
import { BrowserMultiFormatReader, BarcodeFormat } from '@zxing/browser';
import { DecodeHintType } from '@zxing/library';
import { initAdminUtils } from './core/admin-utils.js';
import { initAdminLayout } from './modules/layout/index.js';

// Page-level modules
import './modules/users/index.js';
import './modules/users/show.js';
import './modules/users/audit-logs-table.js';
import './modules/vendors/index.js';
import './modules/shipments/index.js';
import './modules/drivers/index.js';
import './modules/roles/index.js';
import './modules/roles/create.js';
import './modules/roles/edit.js';
import './modules/roles/show.js';
import './modules/warehouses/index.js';
import './modules/pickups/index.js';
import '../warehouse/modules/manifests/incoming.js';
import '../warehouse/modules/manifests/incoming-show.js';

window.Alpine = Alpine;
window.ZXingBrowser = {
    BrowserMultiFormatReader,
    BarcodeFormat,
    DecodeHintType,
};
window.adminGlobalSearch = (searchUrl) => ({
    groups: ['shipments', 'packages', 'transactions', 'vendors', 'drivers'],
    groupLabels: {
        shipments: 'Shipments',
        packages: 'Packages',
        transactions: 'Transactions',
        vendors: 'Vendors',
        drivers: 'Drivers',
    },
    query: '',
    results: {},
    searching: false,
    open: false,
    mobileOpen: false,
    error: null,
    activeIndex: 0,
    _timer: null,

    init() {
        window.addEventListener('keydown', (event) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                this.focusSearch();
            }
        });
    },

    focusSearch() {
        if (window.innerWidth < 768) {
            this.openMobile();
            return;
        }

        this.$nextTick(() => this.$refs.desktopInput?.focus());
    },

    openMobile() {
        this.mobileOpen = true;
        this.open = true;
        this.$nextTick(() => this.$refs.mobileInput?.focus());
    },

    search() {
        clearTimeout(this._timer);
        this.error = null;

        if (this.query.trim().length < 2) {
            this.results = {};
            this.activeIndex = 0;
            this.open = this.mobileOpen;
            return;
        }

        this._timer = setTimeout(() => this.fetchResults(), 250);
    },

    async fetchResults() {
        this.searching = true;

        try {
            const response = await fetch(`${searchUrl}?q=${encodeURIComponent(this.query.trim())}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Search failed');
            }

            const payload = await response.json();
            this.results = payload.data || {};
            this.open = true;
            this.activeIndex = this.hasResults() ? 0 : -1;
        } catch (error) {
            this.results = {};
            this.open = true;
            this.activeIndex = -1;
            this.error = 'Search is unavailable. Please try again.';
        } finally {
            this.searching = false;
        }
    },

    close() {
        clearTimeout(this._timer);
        this.open = false;
        this.mobileOpen = false;
        this.query = '';
        this.results = {};
        this.error = null;
        this.activeIndex = 0;
    },

    flatResults() {
        return this.groups.flatMap((group) => (this.results[group] || []).map((item, index) => ({
            group,
            index,
            item,
        })));
    },

    hasResults() {
        return this.flatResults().length > 0;
    },

    groupOffset(group) {
        let offset = 0;

        for (const current of this.groups) {
            if (current === group) {
                return offset;
            }

            offset += (this.results[current] || []).length;
        }

        return offset;
    },

    isActive(group, index) {
        return this.activeIndex === this.groupOffset(group) + index;
    },

    next() {
        const total = this.flatResults().length;

        if (!total) {
            return;
        }

        this.open = true;
        this.activeIndex = (this.activeIndex + 1 + total) % total;
    },

    previous() {
        const total = this.flatResults().length;

        if (!total) {
            return;
        }

        this.open = true;
        this.activeIndex = (this.activeIndex - 1 + total) % total;
    },

    openActive() {
        const active = this.flatResults()[this.activeIndex] || this.flatResults()[0];

        if (active?.item?.url) {
            window.location.href = active.item.url;
        }
    },
});
initAdminUtils();
initAdminLayout();
Alpine.start();

console.log('Admin JS loaded');
