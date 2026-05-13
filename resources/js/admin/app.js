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
initAdminUtils();
initAdminLayout();
Alpine.start();

console.log('Admin JS loaded');
