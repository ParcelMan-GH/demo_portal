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
@page { size: 80mm 120mm; margin: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { width: 80mm; min-width: 80mm; background: #fff; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.label {
    width: 80mm;
    margin: 0;
    padding: 3.2mm;
    background: #fff;
    color: #000;
    overflow: hidden;
    page-break-after: always;
    break-after: page;
}
.label-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 3mm;
    padding-bottom: 2.6mm;
    border-bottom: 1.5px solid #000;
}
.brandbar {
    background: #111827;
    color: #fff;
    border-radius: 2.5mm;
    padding: 2.6mm 3mm 2.2mm;
    margin-bottom: 3mm;
}
.brandbar-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 3mm;
}
.brandbar .brand-name {
    font-size: 17px;
    line-height: 1.1;
    font-weight: 900;
    letter-spacing: 1.3px;
    color: #fff;
}
.brandbar .brand-sub {
    font-size: 8px;
    line-height: 1.25;
    font-weight: 800;
    letter-spacing: 2.6px;
    color: rgba(255,255,255,0.92);
    margin-top: 1px;
}
.qr-container { flex: 0 0 auto; background: #fff; border-radius: 1.8mm; padding: 1.2mm; }
.qr-code { width: 14mm; height: 14mm; }
.qr-code img,
.qr-code canvas { width: 14mm !important; height: 14mm !important; }
.brandbar-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1.8mm;
}
.doctype {
    font-size: 7.5px;
    font-weight: 900;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.85);
}
.status-chip {
    font-size: 7.5px;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
    background: #fff;
    color: #111827;
    border-radius: 999px;
    padding: 0.8mm 2.4mm;
}
.divider { height: 1.5px; background: #000; margin: 2.8mm 0; }
.addresses { display: block; }
.address-block { margin: 0; }
.address-label {
    font-size: 10px;
    line-height: 1.25;
    font-weight: 900;
    color: #000;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 1.2mm;
}
.address-name {
    font-size: 15px;
    line-height: 1.18;
    font-weight: 900;
    color: #000;
    overflow-wrap: anywhere;
}
.address-detail {
    font-size: 13px;
    line-height: 1.25;
    font-weight: 700;
    color: #000;
    margin-top: 1mm;
    overflow-wrap: anywhere;
}
.address-block-to {
    padding: 1.5mm 0 0;
}
.address-block-to .address-label {
    font-size: 11px;
    margin-bottom: 1.6mm;
}
.address-block-to .address-name {
    font-size: 23px;
    line-height: 1.12;
}
.address-block-to .address-detail {
    font-size: 17px;
    line-height: 1.2;
    margin-top: 1.8mm;
}
.address-divider { height: 1.5px; background: #000; margin: 2.8mm 0; }
.pkg-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2.2mm 2.8mm;
}
.pkg-row { min-width: 0; }
.pkg-label {
    display: block;
    color: #000;
    font-weight: 900;
    text-transform: uppercase;
    font-size: 9px;
    line-height: 1.25;
    letter-spacing: 0.8px;
}
.pkg-value {
    display: block;
    color: #000;
    font-weight: 800;
    font-size: 13px;
    line-height: 1.25;
    margin-top: 0.6mm;
    overflow-wrap: anywhere;
}
.pkg-bold {
    font-size: 14px;
    font-weight: 900;
}
.barcode-section {
    padding-top: 2.4mm;
    text-align: center;
    border-top: 1.5px solid #000;
}
.barcode-svg { width: 100%; margin: 0 auto; }
.barcode-svg svg { width: 100%; max-width: 100%; height: 18mm; }
.barcode-text {
    font-size: 18px;
    line-height: 1.2;
    font-weight: 900;
    font-family: 'Courier New', monospace;
    color: #000;
    margin-top: 1.6mm;
    letter-spacing: 0.8px;
    overflow-wrap: anywhere;
}
.barcode-sub {
    font-size: 7px;
    line-height: 1.2;
    font-weight: 800;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #555;
    margin-top: 0.6mm;
}
.label-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2.6mm;
    background: #111827;
    color: #fff;
    border-radius: 2mm;
    padding: 1.6mm 2.6mm;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: 0.6px;
}
script { display: none !important; }
@media screen {
    body { padding: 0; }
    .label { border: 1px solid #000; }
}
@media print {
    html, body { width: 80mm; }
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
