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
.brand { min-width: 0; flex: 1; }
.brand-name {
    font-size: 18px;
    line-height: 1.1;
    font-weight: 900;
    letter-spacing: 1.4px;
    color: #000;
}
.brand-sub {
    font-size: 9px;
    line-height: 1.25;
    font-weight: 800;
    letter-spacing: 2.4px;
    color: #222;
    margin-top: 1px;
}
.qr-container { flex: 0 0 auto; }
.qr-code { width: 15mm; height: 15mm; }
.qr-code img,
.qr-code canvas { width: 15mm !important; height: 15mm !important; }
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
.address-phone {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.25;
    font-weight: 900;
    color: #000;
    margin-top: 1mm;
    letter-spacing: 0.2px;
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
.address-block-to .address-phone {
    font-size: 19px;
    line-height: 1.2;
    margin-top: 1.8mm;
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
