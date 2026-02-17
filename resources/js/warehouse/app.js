import Alpine from 'alpinejs';
import { initWarehouseLayout } from './modules/layout/index.js';

import './modules/dashboard/index.js';
import './modules/users/index.js';
import './modules/receipts/pending.js';
import './modules/pickups/received.js';
import './modules/items/received.js';

window.Alpine = Alpine;
initWarehouseLayout();
Alpine.start();
