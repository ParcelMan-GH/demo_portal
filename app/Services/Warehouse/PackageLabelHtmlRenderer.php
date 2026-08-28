<?php

namespace App\Services\Warehouse;

class PackageLabelHtmlRenderer
{
    public function renderPage(string $labelCards, string $title): string
    {
        return '<!doctype html><html><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>Labels - '.e($title).'</title>'
            .'<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>'
            .'<style>'.$this->css().'</style>'
            .'</head><body>'.$labelCards.'</body></html>';
    }

    private function css(): string
    {
        return <<<'CSS'
@page { size: 105mm 148mm; margin: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { width: 105mm; min-width: 105mm; background: #fff; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #0f172a;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.label {
    width: 105mm;
    min-height: 148mm;
    margin: 0;
    padding: 4mm;
    background: #fff;
    color: #0f172a;
    overflow: hidden;
    page-break-after: always;
    break-after: page;
}

/* ── Branded orange header ── */
.header-band {
    background: #ea580c;
    border-radius: 3mm;
    padding: 3.2mm 3.6mm 2.8mm;
}
.header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 3mm;
}
.logo-box {
    width: 9.5mm;
    height: 9.5mm;
    background: #fff;
    border-radius: 1.8mm;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}
.logo-box svg { width: 6.8mm; height: 6.8mm; }
.brand-name {
    font-size: 19px;
    line-height: 1.05;
    font-weight: 900;
    color: #fff;
    letter-spacing: 1.4px;
    white-space: nowrap;
}
.brand-sub {
    font-size: 8.5px;
    line-height: 1.2;
    font-weight: 800;
    color: rgba(255,255,255,0.95);
    letter-spacing: 3.4px;
    margin-top: 1px;
    white-space: nowrap;
}
.qr-wrap {
    background: #fff;
    border-radius: 2mm;
    padding: 1.4mm;
    flex: 0 0 auto;
}
.qr-code { width: 16mm; height: 16mm; }
.qr-code img,
.qr-code canvas { width: 16mm !important; height: 16mm !important; }
.status-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2mm;
    margin-top: 2.6mm;
}
.pill-doctype {
    background: rgba(255,255,255,0.22);
    color: #fff;
    font-size: 7.5px;
    font-weight: 900;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    border-radius: 999px;
    padding: 1mm 2.8mm;
}
.pill-status {
    background: #fff;
    color: #ea580c;
    font-size: 7.5px;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
    border-radius: 999px;
    padding: 1mm 2.8mm;
    display: inline-flex;
    align-items: center;
    gap: 1.6mm;
}
.pill-status .dot {
    width: 2mm;
    height: 2mm;
    border-radius: 50%;
    background: #ea580c;
}

/* ── From / To cards ── */
.addr-grid { display: flex; gap: 3mm; margin-top: 3mm; }
.addr-card {
    flex: 1;
    min-width: 0;
    background: #f1f5f9;
    border-radius: 2.5mm;
    padding: 2.8mm 3mm;
}
.addr-head { display: flex; align-items: center; gap: 1.4mm; }
.addr-head svg { width: 3.4mm; height: 3.4mm; color: #ea580c; flex: 0 0 auto; }
.addr-label {
    font-size: 8px;
    font-weight: 900;
    letter-spacing: 1.2px;
    color: #64748b;
    text-transform: uppercase;
}
.addr-name {
    font-size: 14px;
    line-height: 1.15;
    font-weight: 900;
    color: #0f172a;
    margin-top: 1.4mm;
    overflow-wrap: anywhere;
}
.addr-sub {
    font-size: 8px;
    font-weight: 700;
    color: #94a3b8;
    margin-top: 0.8mm;
    overflow-wrap: anywhere;
}
.addr-line {
    font-size: 9.5px;
    line-height: 1.3;
    font-weight: 700;
    color: #475569;
    margin-top: 0.8mm;
    overflow-wrap: anywhere;
}
.addr-phone {
    font-size: 9.5px;
    font-weight: 800;
    color: #0f172a;
    margin-top: 1.2mm;
    overflow-wrap: anywhere;
}

/* ── Details grid ── */
.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2.6mm;
    margin-top: 3mm;
}
.detail-box {
    min-width: 0;
    background: #f1f5f9;
    border-radius: 2.5mm;
    padding: 2.4mm 3mm;
}
.detail-label {
    font-size: 7.5px;
    font-weight: 900;
    letter-spacing: 1.2px;
    color: #64748b;
    text-transform: uppercase;
}
.detail-value {
    font-size: 11px;
    line-height: 1.2;
    font-weight: 900;
    color: #0f172a;
    margin-top: 0.8mm;
    overflow-wrap: anywhere;
}

/* ── Tracking ── */
.scan-to-track {
    display: flex;
    align-items: center;
    gap: 2mm;
    margin: 3.2mm 0 2.2mm;
}
.scan-to-track .dash { flex: 1; border-top: 1px dashed #cbd5e1; }
.scan-text {
    font-size: 7px;
    font-weight: 900;
    letter-spacing: 1.6px;
    color: #94a3b8;
    text-transform: uppercase;
    white-space: nowrap;
}
.barcode-card {
    background: #f1f5f9;
    border-radius: 2.5mm;
    padding: 3mm;
    text-align: center;
}
.barcode-svg svg { width: 100%; max-width: 100%; height: 20mm; }
.barcode-text {
    font-size: 15px;
    line-height: 1.2;
    font-weight: 900;
    font-family: 'Courier New', monospace;
    color: #0f172a;
    margin-top: 1.8mm;
    letter-spacing: 0.8px;
    overflow-wrap: anywhere;
}
.barcode-sub {
    font-size: 7px;
    font-weight: 800;
    letter-spacing: 1.4px;
    color: #94a3b8;
    text-transform: uppercase;
    margin-top: 0.6mm;
}
.label-count {
    font-size: 7.5px;
    font-weight: 900;
    letter-spacing: 1px;
    color: #64748b;
    margin-top: 1.4mm;
    text-transform: uppercase;
}

/* ── Footer ── */
.label-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2mm;
    margin-top: 3.4mm;
    background: #0f172a;
    color: #fff;
    border-radius: 2.5mm;
    padding: 2.2mm 3mm;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: 0.4px;
}
.foot-right { text-align: right; }
.foot-right .support-label {
    font-size: 6.5px;
    font-weight: 700;
    opacity: 0.7;
    letter-spacing: 1px;
    text-transform: uppercase;
}
script { display: none !important; }
@media screen {
    body { padding: 0; }
    .label { border: 1px solid #e2e8f0; }
}
@media print {
    html, body { width: 105mm; }
    .label {
        border: 0;
        margin: 0;
        page-break-after: always;
        break-after: page;
    }
}
CSS;
    }
}
