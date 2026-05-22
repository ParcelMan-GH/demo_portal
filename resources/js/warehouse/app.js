import Alpine from 'alpinejs';
import { BrowserMultiFormatReader, BarcodeFormat } from '@zxing/browser';
import { DecodeHintType } from '@zxing/library';
import { initWarehouseLayout } from './modules/layout/index.js';

import './modules/dashboard/index.js';
import '../admin/modules/users/index.js';
import '../admin/modules/users/show.js';
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
window.ZXingBrowser = {
    BrowserMultiFormatReader,
    BarcodeFormat,
    DecodeHintType,
};
initWarehouseLayout();
Alpine.start();
