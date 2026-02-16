/**
 * Admin Panel JavaScript Entry Point
 * This file is bundled by Vite and includes all admin-specific functionality
 */

import Alpine from 'alpinejs';
import { initAdminUtils } from './core/admin-utils.js';
import { initAdminLayout } from './modules/layout/index.js';

// Page-level modules
import './modules/users/index.js';
import './modules/vendors/index.js';
import './modules/shipments/index.js';
import './modules/drivers/index.js';
import './modules/warehouses/index.js';

window.Alpine = Alpine;
initAdminUtils();
initAdminLayout();
Alpine.start();

console.log('Admin JS loaded');
