import Alpine from 'alpinejs';
import { initWarehouseLayout } from './modules/layout/index.js';

import './modules/dashboard/index.js';
import '../admin/modules/users/index.js';
import '../admin/modules/users/audit-logs-table.js';
import './modules/receipts/pending.js';
import './modules/receipts/show.js';
import './modules/pickups/received.js';
import './modules/items/received.js';
import './modules/sorting/index.js';
import './modules/manifests/transport.js';
import './modules/manifests/incoming.js';
import './modules/manifests/incoming-show.js';
import './modules/deliveries/runs.js';

window.Alpine = Alpine;
initWarehouseLayout();
Alpine.start();
