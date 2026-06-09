<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parcelman — Rider & Warehouse Flow Guide</title>
<style>
  /* ── Reset & Base ─────────────────────────────── */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 13px;
    line-height: 1.65;
    color: #1e293b;
    background: #fff;
    max-width: 900px;
    margin: 0 auto;
    padding: 48px 56px;
  }

  /* ── Typography ───────────────────────────────── */
  h1 { font-size: 26px; font-weight: 700; color: #0f172a; letter-spacing: -0.5px; }
  h2 { font-size: 17px; font-weight: 700; color: #0f172a; margin: 32px 0 10px; }
  h3 { font-size: 14px; font-weight: 600; color: #334155; margin: 20px 0 8px; }
  h4 { font-size: 13px; font-weight: 600; color: #475569; margin: 16px 0 6px; }
  p  { margin-bottom: 10px; color: #334155; }
  a  { color: #2563eb; text-decoration: none; }
  a:hover { text-decoration: underline; }
  ul, ol { padding-left: 20px; margin-bottom: 10px; }
  li { margin-bottom: 4px; color: #334155; }
  strong { color: #0f172a; }

  /* ── Cover ────────────────────────────────────── */
  .cover {
    border-bottom: 3px solid #2563eb;
    padding-bottom: 24px;
    margin-bottom: 32px;
  }
  .cover-subtitle {
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
    font-weight: 500;
  }
  .cover-meta {
    margin-top: 16px;
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
  }
  .cover-meta-item {
    background: #f1f5f9;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 12px;
  }
  .cover-meta-item strong { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 2px; }
  .cover-meta-item span { color: #2563eb; font-weight: 500; }

  /* ── Table of Contents ────────────────────────── */
  .toc {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px 24px;
    margin-bottom: 36px;
  }
  .toc-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #94a3b8;
    margin-bottom: 12px;
  }
  .toc-list { list-style: none; padding: 0; }
  .toc-list li { margin-bottom: 5px; }
  .toc-list a { color: #334155; font-size: 12.5px; }
  .toc-list a:hover { color: #2563eb; }
  .toc-list .toc-phase { font-weight: 600; color: #0f172a; }

  /* ── Phase Sections ───────────────────────────── */
  .phase {
    border-left: 4px solid #2563eb;
    margin: 36px 0 28px;
    padding-left: 18px;
  }
  .phase-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
  }
  .phase-badge {
    background: #2563eb;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    padding: 3px 9px;
    border-radius: 20px;
    white-space: nowrap;
  }
  .phase-title {
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
  }
  .phase-who {
    font-size: 11.5px;
    color: #64748b;
    margin-bottom: 12px;
  }
  .phase-who strong { color: #334155; }

  /* Phase accent colours */
  .phase-1 { border-color: #7c3aed; }
  .phase-1 .phase-badge { background: #7c3aed; }
  .phase-2 { border-color: #0891b2; }
  .phase-2 .phase-badge { background: #0891b2; }
  .phase-3 { border-color: #059669; }
  .phase-3 .phase-badge { background: #059669; }
  .phase-4 { border-color: #d97706; }
  .phase-4 .phase-badge { background: #d97706; }
  .phase-5 { border-color: #dc2626; }
  .phase-5 .phase-badge { background: #dc2626; }

  /* ── Callouts ─────────────────────────────────── */
  .callout {
    border-radius: 8px;
    padding: 10px 14px;
    margin: 12px 0;
    font-size: 12.5px;
  }
  .callout-note   { background: #eff6ff; border-left: 3px solid #3b82f6; color: #1d4ed8; }
  .callout-tip    { background: #f0fdf4; border-left: 3px solid #22c55e; color: #15803d; }
  .callout-warn   { background: #fffbeb; border-left: 3px solid #f59e0b; color: #92400e; }

  /* ── Tables ───────────────────────────────────── */
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    margin: 12px 0 18px;
  }
  thead th {
    background: #f1f5f9;
    text-align: left;
    padding: 8px 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
  }
  tbody td {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
    color: #334155;
  }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr:hover td { background: #f8fafc; }
  .td-required { color: #dc2626; font-weight: 600; font-size: 11px; }
  .td-optional { color: #64748b; font-size: 11px; }

  /* ── Code ─────────────────────────────────────── */
  code {
    font-family: 'Cascadia Code', 'Fira Code', 'Consolas', monospace;
    font-size: 11.5px;
    background: #f1f5f9;
    color: #0f172a;
    padding: 1px 5px;
    border-radius: 4px;
  }
  pre {
    background: #0f172a;
    color: #e2e8f0;
    font-family: 'Cascadia Code', 'Fira Code', 'Consolas', monospace;
    font-size: 11.5px;
    line-height: 1.7;
    padding: 16px 18px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 10px 0 16px;
  }
  pre code { background: none; color: inherit; padding: 0; font-size: inherit; }
  .pre-method { color: #93c5fd; font-weight: 600; }
  .pre-url    { color: #6ee7b7; }
  .pre-key    { color: #fcd34d; }
  .pre-value  { color: #a5b4fc; }
  .pre-string { color: #86efac; }
  .pre-comment { color: #94a3b8; font-style: italic; }

  /* ── Endpoint Block ───────────────────────────── */
  .endpoint {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    margin: 18px 0;
    overflow: hidden;
  }
  .endpoint-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
  }
  .method-badge {
    font-size: 10.5px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 5px;
    letter-spacing: .04em;
  }
  .method-get  { background: #dcfce7; color: #15803d; }
  .method-post { background: #dbeafe; color: #1d4ed8; }
  .endpoint-url {
    font-family: 'Cascadia Code', 'Fira Code', 'Consolas', monospace;
    font-size: 12px;
    color: #0f172a;
    font-weight: 500;
  }
  .endpoint-body { padding: 12px 14px; }
  .endpoint-desc { font-size: 12.5px; color: #475569; margin-bottom: 8px; }

  /* ── Step List ────────────────────────────────── */
  .steps { counter-reset: step; list-style: none; padding: 0; margin: 10px 0; }
  .steps li {
    counter-increment: step;
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    align-items: flex-start;
  }
  .steps li::before {
    content: counter(step);
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    background: #2563eb;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    margin-top: 1px;
  }

  /* ── Status Badge ─────────────────────────────── */
  .status {
    display: inline-block;
    font-size: 10.5px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 20px;
    letter-spacing: .03em;
  }
  .s-pending    { background: #fef3c7; color: #92400e; }
  .s-active     { background: #dbeafe; color: #1e40af; }
  .s-success    { background: #dcfce7; color: #166534; }
  .s-failed     { background: #fee2e2; color: #991b1b; }
  .s-neutral    { background: #f1f5f9; color: #475569; }

  /* ── Status Flow Diagram ──────────────────────── */
  .status-flow {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
    margin: 10px 0;
    font-size: 11.5px;
  }
  .sf-arrow { color: #94a3b8; font-size: 13px; }

  /* ── Divider ──────────────────────────────────── */
  hr { border: none; border-top: 1px solid #e2e8f0; margin: 28px 0; }

  /* ── Reference Section ────────────────────────── */
  .ref-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px 22px;
    margin: 20px 0;
  }
  .ref-section h3 { margin-top: 0; }

  /* ── Flow Diagram ─────────────────────────────── */
  .flow-diagram {
    background: #0f172a;
    color: #e2e8f0;
    font-family: 'Cascadia Code', 'Fira Code', 'Consolas', monospace;
    font-size: 11px;
    line-height: 1.8;
    padding: 20px 24px;
    border-radius: 10px;
    margin: 16px 0;
    overflow-x: auto;
    white-space: pre;
  }

  /* ── Print ────────────────────────────────────── */
  @media print {
    body { padding: 24px 36px; font-size: 11.5px; }
    .phase { break-inside: avoid; }
    .endpoint { break-inside: avoid; }
    pre { break-inside: avoid; }
    table { break-inside: avoid; }
    h2 { break-before: auto; }
    a { color: #1d4ed8; }
  }

  @page {
    margin: 20mm 18mm;
    size: A4;
  }
</style>
</head>
<body>

<!-- ── COVER ───────────────────────────────────────────── -->
<div class="cover">
  <h1>Parcelman — Rider &amp; Warehouse Flow Guide</h1>
  <div class="cover-subtitle">For Mobile Developers · v1 API</div>
  <div class="cover-meta">
    <div class="cover-meta-item">
      <strong>API Base URL</strong>
      <span>https://parcelman.qixapps.com/api/v1</span>
    </div>
    <div class="cover-meta-item">
      <strong>Admin / Warehouse Login</strong>
      <span>https://parcelman.qixapps.com/admin</span>
    </div>
    <div class="cover-meta-item">
      <strong>Last Updated</strong>
      <span>February 2026</span>
    </div>
  </div>
</div>

<!-- ── DEMO CREDENTIALS ────────────────────────────────── -->
<h2>Demo Credentials</h2>
<table>
  <thead><tr><th>Role</th><th>Email</th><th>Password</th><th>Notes</th></tr></thead>
  <tbody>
    <tr><td>Main Warehouse</td><td><code>main.warehouse@parcelman.test</code></td><td><code>password</code></td><td></td></tr>
    <tr><td>Kumasi Warehouse</td><td><code>main.warehouse@parcelman.test</code></td><td><code>password</code></td><td></td></tr>
    <tr><td>Rider</td><td><code>rider@example.com</code></td><td><code>password</code></td><td>All 3 capabilities: Pickup, Transport, Delivery</td></tr>
  </tbody>
</table>

<!-- ── TABLE OF CONTENTS ───────────────────────────────── -->
<div class="toc">
  <div class="toc-title">Contents</div>
  <ul class="toc-list">
    <li><a href="#overview">Overview &amp; Item Lifecycle</a></li>
    <li><a href="#phase1"><span class="toc-phase">Phase 1 — Rider Pickup</span></a></li>
    <li><a href="#phase2"><span class="toc-phase">Phase 2 — Warehouse Receiving</span></a></li>
    <li><a href="#phase3"><span class="toc-phase">Phase 3 — Sorting</span></a></li>
    <li><a href="#phase4"><span class="toc-phase">Phase 4 — Transport Manifest</span></a></li>
    <li><a href="#phase5"><span class="toc-phase">Phase 5 — Delivery Run</span></a></li>
    <li><a href="#quickref">Quick Reference</a></li>
    <li><a href="#rules">Key Rules</a></li>
  </ul>
</div>

<!-- ── OVERVIEW ────────────────────────────────────────── -->
<h2 id="overview">Overview &amp; Item Lifecycle</h2>

<p>A shipment item follows this path from the moment a rider collects it from a vendor all the way to final delivery at the recipient's door. The flow branches at the warehouse — items can go to local delivery <strong>or</strong> be transported to another warehouse first.</p>

<div class="flow-diagram">VENDOR submits shipment
       │
       ▼
┌──────────────────────────────────────────┐
│  PHASE 1 · PICKUP                        │  ← Rider API
│  Rider collects items from vendor       │
└──────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────┐
│  PHASE 2 · WAREHOUSE RECEIVING           │  ← Warehouse Portal only
│  Staff inspect, record &amp; print labels    │
└──────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────┐
│  PHASE 3 · SORTING                       │  ← Warehouse Portal only
│  Staff group items into batches          │
└──────────────────────────────────────────┘
       │
       ├─── local_delivery ──────────────────────────────┐
       │                                                  ▼
       │                             ┌────────────────────────────────────┐
       │                             │  PHASE 5 · DELIVERY RUN            │  ← Rider API
       │                             │  Rider delivers to recipients     │
       │                             └────────────────────────────────────┘
       │
       └─── transfer ─────────────────────┐
                                          ▼
                   ┌──────────────────────────────────────────┐
                   │  PHASE 4 · TRANSPORT MANIFEST            │  ← Rider API
                   │  Rider moves items to another warehouse  │
                   └──────────────────────────────────────────┘
                                          │
                                          ▼
                             Destination warehouse receives
                                          │
                                          ▼
                                 Back to Phase 3 (sorting)
                                 at the destination warehouse</div>

<h3>Item Status Progression</h3>
<div class="status-flow">
  <span class="status s-neutral">pending</span><span class="sf-arrow">→</span>
  <span class="status s-active">picked_up</span><span class="sf-arrow">→</span>
  <span class="status s-active">at_warehouse</span><span class="sf-arrow">→</span>
  <span class="status s-active">sorted</span><span class="sf-arrow">→</span>
  <span class="status s-active">in_transit</span><span class="sf-arrow">→</span>
  <span class="status s-active">at_destination</span><span class="sf-arrow">→</span>
  <span class="status s-active">out_for_delivery</span><span class="sf-arrow">→</span>
  <span class="status s-success">delivered</span>
</div>

<hr>

<!-- ── PHASE 1 ─────────────────────────────────────────── -->
<div class="phase phase-1" id="phase1">
  <div class="phase-header">
    <span class="phase-badge">Phase 1</span>
    <span class="phase-title">Rider Pickup</span>
  </div>
  <div class="phase-who"><strong>Who:</strong> Rider (mobile app) &nbsp;·&nbsp; <strong>What:</strong> Rider collects items from the vendor's location and brings them to the target warehouse.</div>
  <p>Pickup assignments are created by the system and assigned to the rider. Each assignment includes the pickup address, vendor contact, items with expected quantities, and the target warehouse.</p>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-url">/api/v1/driver/pickups</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">List assigned pickups. Response includes pickup location, vendor contact, target warehouse, and items with <code>shipment_item_id</code> and <code>expected_quantity</code>.</p>
    <table>
      <thead><tr><th>Query Param</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>status[]</code></td><td><code>assigned</code> · <code>en_route</code> · <code>arrived</code> · <code>picking_up</code> · <code>completed</code> · <code>cancelled</code></td></tr>
        <tr><td><code>search</code></td><td>Shipment number or address</td></tr>
        <tr><td><code>limit</code> / <code>offset</code></td><td>Pagination</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-url">/api/v1/driver/pickups/{assignment}</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Full assignment details — items with images, vendor, pickup location (region, district, town, landmark, coordinates).</p>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/pickups/{assignment}/en-route</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Mark rider is heading to the vendor. No body required.</p>
    <div class="status-flow"><span class="status s-neutral">assigned</span><span class="sf-arrow">→</span><span class="status s-active">en_route</span></div>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/pickups/{assignment}/arrive</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Record GPS arrival at the vendor's location.</p>
    <pre><code>{
  <span class="pre-key">"latitude"</span>:  <span class="pre-value">5.6037</span>,
  <span class="pre-key">"longitude"</span>: <span class="pre-value">-0.1870</span>
}</code></pre>
    <div class="status-flow"><span class="status s-active">en_route</span><span class="sf-arrow">→</span><span class="status s-active">arrived</span></div>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/pickups/{assignment}/items/{shipment_item_id}/confirm</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Confirm quantity and photos for one item. Call once per item (can be called again to update).</p>
    <table>
      <thead><tr><th>Field</th><th>Required</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>confirmed_quantity</code></td><td><span class="td-required">Required</span></td><td>Units rider is physically taking</td></tr>
        <tr><td><code>notes</code></td><td><span class="td-optional">Optional</span></td><td>Condition or discrepancy notes</td></tr>
        <tr><td><code>photos[]</code></td><td><span class="td-optional">Optional</span></td><td>Up to 10 photos, max 10 MB each</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/pickups/{assignment}/confirm-pickup</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Finalise the entire pickup once all items are confirmed. <strong>This action is permanent.</strong></p>
    <pre><code>{
  <span class="pre-key">"latitude"</span>:  <span class="pre-value">5.6037</span>,
  <span class="pre-key">"longitude"</span>: <span class="pre-value">-0.1870</span>,
  <span class="pre-key">"notes"</span>:     <span class="pre-string">"All items collected"</span>
}</code></pre>
    <p><strong>What happens automatically:</strong></p>
    <ul>
      <li>All item statuses → <code>picked_up</code></li>
      <li>Each item gets a unique <code>tracking_code</code> generated (used for scanning in all later steps)</li>
      <li>Assignment status → <code>completed</code>, rider status → <code>available</code></li>
    </ul>
    <div class="callout callout-tip">The rider now physically takes the items to the target warehouse.</div>
  </div>
</div>

<hr>

<!-- ── PHASE 2 ─────────────────────────────────────────── -->
<div class="phase phase-2" id="phase2">
  <div class="phase-header">
    <span class="phase-badge">Phase 2</span>
    <span class="phase-title">Warehouse Receiving</span>
  </div>
  <div class="phase-who"><strong>Who:</strong> Warehouse staff (web portal) &nbsp;·&nbsp; <strong>What:</strong> Staff inspect items, record quantities and condition, print barcode labels, finalize receipt.</div>
  <div class="callout callout-note">No rider API calls in this phase. The rider drops off the items and leaves.</div>
</div>

<p><strong>Portal URL:</strong> <a href="https://parcelman.qixapps.com/warehouse/receipts/pending">https://parcelman.qixapps.com/warehouse/receipts/pending</a></p>

<ol class="steps">
  <li><strong>Pending Receipts</strong> — lists all completed pickups waiting to be received at this warehouse.</li>
  <li><strong>Click View</strong> on the relevant pickup to open the receiving detail page.</li>
  <li><strong>Receiving tab</strong> — for each item, staff enter received quantity, damaged quantity, condition (<code>ok</code> / <code>damaged</code> / <code>partial</code>), notes, and photos.</li>
  <li><strong>Save item</strong> — a barcode is auto-generated. Staff print the label and <strong>physically attach it to the item/package</strong>. This barcode is what gets scanned in all subsequent steps.</li>
  <li><strong>Finalize Receipt</strong> — locks the receipt. Any quantity discrepancies must be acknowledged first. Item statuses → <code>at_warehouse</code>. Items are now ready for sorting.</li>
</ol>

<hr>

<!-- ── PHASE 3 ─────────────────────────────────────────── -->
<div class="phase phase-3" id="phase3">
  <div class="phase-header">
    <span class="phase-badge">Phase 3</span>
    <span class="phase-title">Sorting</span>
  </div>
  <div class="phase-who"><strong>Who:</strong> Warehouse staff (web portal) &nbsp;·&nbsp; <strong>What:</strong> Staff group items into batches and decide the next step.</div>
  <div class="callout callout-note">No rider API calls in this phase.</div>
</div>

<p><strong>Portal URL:</strong> <a href="https://parcelman.qixapps.com/warehouse/sorting">https://parcelman.qixapps.com/warehouse/sorting</a></p>

<ol class="steps">
  <li><strong>Create Batch</strong> — choose dispatch mode:
    <ul>
      <li><code>local_delivery</code> — items will be delivered to recipients from this warehouse → goes to Phase 5</li>
      <li><code>transfer</code> — items need to move to another warehouse first → goes to Phase 4</li>
    </ul>
  </li>
  <li><strong>Add Items</strong> — select items with status <code>at_warehouse</code>.</li>
  <li><strong>Seal Batch</strong> — confirms the grouping. Item statuses → <code>sorted</code>.</li>
</ol>

<div class="callout callout-tip">Nothing happens automatically after sealing. A staff member explicitly creates a Transport Manifest (transfer) or Delivery Run (local delivery) from the sealed batch.</div>

<hr>

<!-- ── PHASE 4 ─────────────────────────────────────────── -->
<div class="phase phase-4" id="phase4">
  <div class="phase-header">
    <span class="phase-badge">Phase 4</span>
    <span class="phase-title">Transport Manifest (Warehouse to Warehouse)</span>
  </div>
  <div class="phase-who"><strong>Who:</strong> Warehouse staff (web portal) + Rider (mobile app) &nbsp;·&nbsp; <strong>What:</strong> Items are loaded onto a vehicle and transported to the destination warehouse.</div>
</div>

<h3>Warehouse Side — Origin</h3>
<p><strong>Portal URL:</strong> <a href="https://parcelman.qixapps.com/warehouse/manifests/transport">https://parcelman.qixapps.com/warehouse/manifests/transport</a></p>

<ol class="steps">
  <li><strong>Create Manifest</strong> from the sealed transfer batch. Manifest status → <code>draft</code></li>
  <li><strong>Assign Rider</strong> (must have transport capability). Status → <code>assigned</code>, rider status → <code>busy</code></li>
  <li><strong>Dispatch</strong> when the vehicle is ready to leave. Status → <code>in_transit</code>, item statuses → <code>in_transit</code></li>
</ol>

<h3>Rider Side — Load &amp; Travel</h3>

<div class="callout callout-tip"><strong>Start Loading is optional.</strong> Scan Load works directly from <code>assigned</code> status and auto-advances the manifest to <code>loading</code> on the first scan.</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-url">/api/v1/driver/transports</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">List transport manifests. Filters: <code>status[]</code>, <code>search</code>, <code>limit</code>, <code>offset</code>.</p>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-url">/api/v1/driver/transports/{manifest}</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Full manifest details — origin/destination warehouse, all items with <code>tracking_code</code>, expected quantities, and line statuses.</p>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/transports/{manifest}/start-loading</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Optional. Mark rider has started loading. No body required.</p>
    <div class="status-flow"><span class="status s-active">assigned</span><span class="sf-arrow">→</span><span class="status s-active">loading</span></div>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/transports/{manifest}/scan-load</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Scan one item's barcode as it is loaded onto the vehicle. Call once per item. The <code>tracking_code</code> is the barcode printed during warehouse receiving (Phase 2) — <strong>not</strong> the shipment number.</p>
    <pre><code>{
  <span class="pre-key">"tracking_code"</span>: <span class="pre-string">"TRK5PNQ13E"</span>
}</code></pre>
    <div class="callout callout-note">Auto-advances manifest from <code>assigned</code> → <code>loading</code> on the first scan.</div>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/transports/{manifest}/depart</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">All items loaded. Rider leaves the origin warehouse. No body required.</p>
    <div class="status-flow"><span class="status s-active">loading</span><span class="sf-arrow">→</span><span class="status s-active">in_transit</span></div>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/transports/{manifest}/arrive</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Rider arrives at the destination warehouse. No body required. The destination warehouse can now see this manifest in their Incoming tab.</p>
    <div class="status-flow"><span class="status s-active">in_transit</span><span class="sf-arrow">→</span><span class="status s-active">arrived</span></div>
  </div>
</div>

<h3>Warehouse Side — Destination</h3>
<p><strong>Portal URL:</strong> <a href="https://parcelman.qixapps.com/warehouse/manifests/incoming">https://parcelman.qixapps.com/warehouse/manifests/incoming</a></p>

<ol class="steps">
  <li><strong>Find the arrived manifest</strong> and click View.</li>
  <li><strong>Scan each item in</strong> — enter received quantity per line. System flags discrepancies: <code>short</code>, <code>excess</code>, <code>damaged</code>.</li>
  <li><strong>Finalize Receipt</strong> — manifest status → <code>received</code>, item statuses → <code>at_warehouse</code> (at the destination warehouse), rider status → <code>available</code>.</li>
</ol>

<div class="callout callout-tip">Items are now back at <code>at_warehouse</code> at the destination warehouse and go through <strong>Sorting (Phase 3) again</strong> there.</div>

<hr>

<!-- ── PHASE 5 ─────────────────────────────────────────── -->
<div class="phase phase-5" id="phase5">
  <div class="phase-header">
    <span class="phase-badge">Phase 5</span>
    <span class="phase-title">Delivery Run (Final Delivery to Recipient)</span>
  </div>
  <div class="phase-who"><strong>Who:</strong> Warehouse staff (web portal) + Rider (mobile app) &nbsp;·&nbsp; <strong>What:</strong> Items are grouped into a delivery run and delivered to recipients.</div>
</div>

<h3>Warehouse Side — Create &amp; Dispatch</h3>
<p><strong>Portal URL:</strong> <a href="https://parcelman.qixapps.com/warehouse/deliveries/runs">https://parcelman.qixapps.com/warehouse/deliveries/runs</a></p>

<ol class="steps">
  <li><strong>Create Delivery Run</strong> from a sealed <code>local_delivery</code> batch. The system groups items by recipient/address into <strong>stops</strong> (one stop = one delivery address), generates a unique 6-digit verification code per stop, and sends it to the recipient by SMS. Run status → <code>draft</code></li>
  <li><strong>Assign Rider</strong> (must have delivery capability). Run status → <code>assigned</code>, codes re-sent to recipients, rider status → <code>busy</code></li>
  <li><strong>Dispatch</strong> when rider leaves with the items. Run status → <code>out_for_delivery</code></li>
</ol>

<h3>Rider Side — Make Deliveries</h3>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-url">/api/v1/driver/deliveries</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">List delivery runs. Filters: <code>status[]</code>, <code>search</code>, <code>limit</code>, <code>offset</code>.</p>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-get">GET</span>
    <span class="endpoint-url">/api/v1/driver/deliveries/{run}</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Full run details with all stops and their items. <strong>Call this before starting deliveries</strong> to cache the complete stop list.</p>
    <pre><code>{
  <span class="pre-key">"success"</span>: <span class="pre-value">true</span>,
  <span class="pre-key">"data"</span>: {
    <span class="pre-key">"delivery"</span>: {
      <span class="pre-key">"id"</span>: <span class="pre-value">3</span>,
      <span class="pre-key">"run_number"</span>: <span class="pre-string">"DR-2026-AC01-0001"</span>,
      <span class="pre-key">"status"</span>: <span class="pre-string">"out_for_delivery"</span>,
      <span class="pre-key">"warehouse"</span>: {
        <span class="pre-key">"name"</span>: <span class="pre-string">"Main Warehouse"</span>,
        <span class="pre-key">"address"</span>: <span class="pre-string">"123 Ring Road, Accra"</span>,
        <span class="pre-key">"latitude"</span>: <span class="pre-string">"5.60391200"</span>,
        <span class="pre-key">"longitude"</span>: <span class="pre-string">"-0.18690900"</span>,
        <span class="pre-key">"contact_phone"</span>: <span class="pre-string">"+233201234567"</span>
      },
      <span class="pre-key">"stops"</span>: [
        {
          <span class="pre-key">"id"</span>: <span class="pre-value">9</span>,
          <span class="pre-key">"recipient_name"</span>:  <span class="pre-string">"Ama Mensah"</span>,
          <span class="pre-key">"recipient_phone"</span>: <span class="pre-string">"+233241234567"</span>,
          <span class="pre-key">"status"</span>: <span class="pre-string">"pending"</span>,
          <span class="pre-key">"location"</span>: {
            <span class="pre-key">"region"</span>:         <span class="pre-string">"Greater Accra"</span>,
            <span class="pre-key">"district"</span>:        <span class="pre-string">"Accra Metropolitan"</span>,
            <span class="pre-key">"town"</span>:            <span class="pre-string">"Osu"</span>,
            <span class="pre-key">"landmark"</span>:        <span class="pre-string">"Near Oxford Street"</span>,
            <span class="pre-key">"gh_post_address"</span>: <span class="pre-string">"GA-144-2020"</span>,
            <span class="pre-key">"latitude"</span>:        <span class="pre-string">"5.5558"</span>,
            <span class="pre-key">"longitude"</span>:       <span class="pre-string">"-0.1845"</span>
          },
          <span class="pre-key">"verification"</span>: {
            <span class="pre-key">"code_sent_at"</span>:    <span class="pre-string">"2026-02-18T10:30:00Z"</span>,
            <span class="pre-key">"code_expires_at"</span>: <span class="pre-string">"2026-02-19T10:30:00Z"</span>,
            <span class="pre-key">"attempts"</span>:        <span class="pre-value">0</span>,
            <span class="pre-key">"max_attempts"</span>:    <span class="pre-value">5</span>
          },
          <span class="pre-key">"items"</span>: [
            {
              <span class="pre-key">"shipment_item_id"</span>:   <span class="pre-value">14</span>,
              <span class="pre-key">"shipment_number"</span>:    <span class="pre-string">"PCM-2026-00014"</span>,
              <span class="pre-key">"description"</span>:        <span class="pre-string">"LED TV 50-inch"</span>,
              <span class="pre-key">"tracking_code"</span>:      <span class="pre-string">"TRK5PNQ13E"</span>,
              <span class="pre-key">"expected_quantity"</span>:  <span class="pre-value">1</span>,
              <span class="pre-key">"delivered_quantity"</span>: <span class="pre-value">0</span>,
              <span class="pre-key">"status"</span>:             <span class="pre-string">"pending"</span>
            }
          ]
        }
      ]
    }
  }
}</code></pre>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/deliveries/{run}/stops/{stop}/arrive</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Mark arrival at the delivery address. No body required. <em>This step is optional</em> — if skipped, <code>arrived_at</code> is backfilled automatically when the rider confirms.</p>
    <div class="status-flow"><span class="status s-pending">pending</span><span class="sf-arrow">→</span><span class="status s-active">arrived</span></div>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/deliveries/{run}/stops/{stop}/confirm</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Confirm delivery. The recipient gives the rider their 6-digit SMS code. Submit it with GPS, a proof photo, and delivered quantities for every item at the stop.</p>
    <p><strong>Content-Type:</strong> <code>multipart/form-data</code> (required for photo upload)</p>
    <table>
      <thead><tr><th>Field</th><th>Required</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>verification_code</code></td><td><span class="td-required">Required</span></td><td>6-digit code the recipient received by SMS</td></tr>
        <tr><td><code>latitude</code></td><td><span class="td-required">Required</span></td><td>Delivery GPS latitude</td></tr>
        <tr><td><code>longitude</code></td><td><span class="td-required">Required</span></td><td>Delivery GPS longitude</td></tr>
        <tr><td><code>proof_photo</code></td><td><span class="td-required">Required</span></td><td>Proof image, max 12 MB</td></tr>
        <tr><td><code>items[N][shipment_item_id]</code></td><td><span class="td-required">Required</span></td><td>From <code>stop.items[].shipment_item_id</code></td></tr>
        <tr><td><code>items[N][delivered_quantity]</code></td><td><span class="td-required">Required</span></td><td>How many units were handed over</td></tr>
        <tr><td><code>items[N][notes]</code></td><td><span class="td-optional">Optional</span></td><td>Per-item note</td></tr>
      </tbody>
    </table>

    <p><strong>Every item at the stop must be included.</strong> Read the list from <code>stop.items[]</code> in the delivery run response.</p>

    <table>
      <thead><tr><th>delivered_quantity value</th><th>Item status outcome</th></tr></thead>
      <tbody>
        <tr><td>Equal to <code>expected_quantity</code></td><td><span class="status s-success">delivered</span> ✓</td></tr>
        <tr><td>Less than <code>expected_quantity</code></td><td><span class="status s-pending">partial</span> — some units missing</td></tr>
        <tr><td><code>0</code></td><td><span class="status s-failed">failed</span> — could not deliver</td></tr>
      </tbody>
    </table>

    <div class="callout callout-tip">For sealed/bagged packages the rider cannot open, send <code>expected_quantity</code> as-is.</div>

    <p><strong>After confirmation:</strong></p>
    <ul>
      <li>Stop status → <code>delivered</code></li>
      <li>All stops done → Run → <code>completed</code>, rider freed</li>
      <li>Some stops remain → Run → <code>partially_delivered</code></li>
      <li>Partial/failed items return to the warehouse queue for re-delivery</li>
    </ul>

    <pre><code><span class="pre-comment">// JSON equivalent of the multipart body (for reference)</span>
{
  <span class="pre-key">"verification_code"</span>: <span class="pre-string">"483219"</span>,
  <span class="pre-key">"latitude"</span>:          <span class="pre-string">"5.5558"</span>,
  <span class="pre-key">"longitude"</span>:         <span class="pre-string">"-0.1845"</span>,
  <span class="pre-key">"proof_photo"</span>:       <span class="pre-string">"&lt;file&gt;"</span>,
  <span class="pre-key">"items"</span>: [
    { <span class="pre-key">"shipment_item_id"</span>: <span class="pre-value">14</span>, <span class="pre-key">"delivered_quantity"</span>: <span class="pre-value">1</span>, <span class="pre-key">"notes"</span>: <span class="pre-string">"Handed to recipient"</span> },
    { <span class="pre-key">"shipment_item_id"</span>: <span class="pre-value">15</span>, <span class="pre-key">"delivered_quantity"</span>: <span class="pre-value">0</span>, <span class="pre-key">"notes"</span>: <span class="pre-string">"Damaged, could not deliver"</span> }
  ]
}</code></pre>
  </div>
</div>

<div class="endpoint">
  <div class="endpoint-header">
    <span class="method-badge method-post">POST</span>
    <span class="endpoint-url">/api/v1/driver/deliveries/{run}/stops/{stop}/fail</span>
  </div>
  <div class="endpoint-body">
    <p class="endpoint-desc">Mark stop as failed. Items return to the warehouse queue. Run continues with remaining stops.</p>
    <pre><code>{
  <span class="pre-key">"reason"</span>: <span class="pre-string">"recipient_not_available"</span>,
  <span class="pre-key">"notes"</span>:  <span class="pre-string">"Called 3 times, no answer."</span>
}</code></pre>
    <table>
      <thead><tr><th>Field</th><th>Required</th><th>Description</th></tr></thead>
      <tbody>
        <tr><td><code>reason</code></td><td><span class="td-required">Required</span></td><td>Max 255 chars</td></tr>
        <tr><td><code>notes</code></td><td><span class="td-optional">Optional</span></td><td>Visible to warehouse staff, max 2000 chars</td></tr>
      </tbody>
    </table>
    <div class="status-flow"><span class="status s-active">arrived</span><span class="sf-arrow">→</span><span class="status s-failed">failed</span></div>
  </div>
</div>

<div class="callout callout-note" style="margin-top:16px;">
  <strong>Resend Verification Code</strong> — if a recipient didn't get their code, warehouse staff resend it from the portal: <em>Deliveries → Runs → [select run] → [select stop] → Resend Code</em>. No rider action needed — just retry Confirm Stop once the recipient has their new code.
</div>

<hr>

<!-- ── QUICK REFERENCE ─────────────────────────────────── -->
<h2 id="quickref">Quick Reference</h2>

<div class="ref-section">
  <h3>Pickup Endpoints</h3>
  <table>
    <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
    <tbody>
      <tr><td><span class="method-badge method-get">GET</span></td><td><code>/api/v1/driver/pickups</code></td><td>List assigned pickups</td></tr>
      <tr><td><span class="method-badge method-get">GET</span></td><td><code>/api/v1/driver/pickups/{assignment}</code></td><td>Get pickup details</td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/pickups/{assignment}/en-route</code></td><td>Mark en route to vendor</td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/pickups/{assignment}/arrive</code></td><td>Arrive at vendor location</td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/pickups/{assignment}/items/{item}/confirm</code></td><td>Confirm item quantity &amp; photos</td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/pickups/{assignment}/confirm-pickup</code></td><td>Finalise entire pickup</td></tr>
    </tbody>
  </table>
</div>

<div class="ref-section">
  <h3>Transport Endpoints</h3>
  <table>
    <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
    <tbody>
      <tr><td><span class="method-badge method-get">GET</span></td><td><code>/api/v1/driver/transports</code></td><td>List transport manifests</td></tr>
      <tr><td><span class="method-badge method-get">GET</span></td><td><code>/api/v1/driver/transports/{manifest}</code></td><td>Get manifest details</td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/transports/{manifest}/start-loading</code></td><td>Start loading <em>(optional)</em></td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/transports/{manifest}/scan-load</code></td><td>Scan item barcode as loaded</td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/transports/{manifest}/depart</code></td><td>Depart origin warehouse</td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/transports/{manifest}/arrive</code></td><td>Arrive at destination warehouse</td></tr>
    </tbody>
  </table>
</div>

<div class="ref-section">
  <h3>Delivery Endpoints</h3>
  <table>
    <thead><tr><th>Method</th><th>Endpoint</th><th>Description</th></tr></thead>
    <tbody>
      <tr><td><span class="method-badge method-get">GET</span></td><td><code>/api/v1/driver/deliveries</code></td><td>List delivery runs</td></tr>
      <tr><td><span class="method-badge method-get">GET</span></td><td><code>/api/v1/driver/deliveries/{run}</code></td><td>Get run with all stops &amp; items</td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/deliveries/{run}/stops/{stop}/arrive</code></td><td>Arrive at stop <em>(optional)</em></td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/deliveries/{run}/stops/{stop}/confirm</code></td><td>Confirm delivery at stop</td></tr>
      <tr><td><span class="method-badge method-post">POST</span></td><td><code>/api/v1/driver/deliveries/{run}/stops/{stop}/fail</code></td><td>Mark stop as failed</td></tr>
    </tbody>
  </table>
</div>

<div class="ref-section">
  <h3>Warehouse Portal Pages</h3>
  <p>All pages require login at: <a href="https://parcelman.qixapps.com/admin">https://parcelman.qixapps.com/admin</a></p>
  <table>
    <thead><tr><th>Phase</th><th>Page</th><th>URL</th></tr></thead>
    <tbody>
      <tr><td>Receiving</td><td>Pending Receipts</td><td><a href="https://parcelman.qixapps.com/warehouse/receipts/pending">…/warehouse/receipts/pending</a></td></tr>
      <tr><td>Sorting</td><td>Sorting Dashboard</td><td><a href="https://parcelman.qixapps.com/warehouse/sorting">…/warehouse/sorting</a></td></tr>
      <tr><td>Transport</td><td>Outbound Manifests</td><td><a href="https://parcelman.qixapps.com/warehouse/manifests/transport">…/warehouse/manifests/transport</a></td></tr>
      <tr><td>Transport</td><td>Incoming Manifests</td><td><a href="https://parcelman.qixapps.com/warehouse/manifests/incoming">…/warehouse/manifests/incoming</a></td></tr>
      <tr><td>Delivery</td><td>Delivery Runs</td><td><a href="https://parcelman.qixapps.com/warehouse/deliveries/runs">…/warehouse/deliveries/runs</a></td></tr>
    </tbody>
  </table>
</div>

<!-- ── ITEM STATUS REFERENCE ───────────────────────────── -->
<div class="ref-section">
  <h3>Item Status Reference</h3>
  <table>
    <thead><tr><th>Status</th><th>Meaning</th><th>Set By</th></tr></thead>
    <tbody>
      <tr><td><span class="status s-neutral">pending</span></td><td>Item created, not yet collected</td><td>System</td></tr>
      <tr><td><span class="status s-active">picked_up</span></td><td>Rider collected from vendor</td><td>Rider — Finalise Pickup</td></tr>
      <tr><td><span class="status s-active">at_warehouse</span></td><td>Received and logged at a warehouse</td><td>Warehouse — Finalise Receipt</td></tr>
      <tr><td><span class="status s-active">sorted</span></td><td>Grouped into a sort batch</td><td>Warehouse — Seal Batch</td></tr>
      <tr><td><span class="status s-active">in_transit</span></td><td>On a vehicle between warehouses</td><td>Warehouse — Dispatch Manifest</td></tr>
      <tr><td><span class="status s-active">at_destination</span></td><td>Arrived at destination, pending receiving</td><td>Rider — Arrive at Destination</td></tr>
      <tr><td><span class="status s-active">out_for_delivery</span></td><td>On a delivery run heading to recipient</td><td>Warehouse — Dispatch Delivery Run</td></tr>
      <tr><td><span class="status s-success">delivered</span></td><td>Handed to recipient</td><td>Rider — Confirm Stop</td></tr>
      <tr><td><span class="status s-failed">returned</span></td><td>Back in warehouse after failed delivery</td><td>System</td></tr>
    </tbody>
  </table>
</div>

<hr>

<!-- ── KEY RULES ───────────────────────────────────────── -->
<h2 id="rules">Key Rules</h2>

<ol>
  <li><strong>Riders have capabilities.</strong> A rider can have any combination of Pickup, Transport, and Delivery. Only show the relevant sections in the app.</li>
  <li><strong>Verification codes are per-stop, not per-run.</strong> Each stop has its own unique code sent to that recipient's phone number.</li>
  <li><strong>Verification codes expire after 24 hours.</strong> If expired, warehouse staff must resend via the portal.</li>
  <li><strong>The <code>tracking_code</code> on Scan Load</strong> is the barcode printed and stuck on the item during warehouse receiving — not the shipment number. The rider reads it from the manifest item list in the API response.</li>
  <li><strong>Arrive at Stop is optional.</strong> Going straight to Confirm Stop is fine — <code>arrived_at</code> is backfilled automatically.</li>
  <li><strong>Failing a stop does not cancel the run.</strong> The run continues with remaining stops; failed items re-enter the delivery queue.</li>
  <li><strong>Warehouse Dispatch ≠ Rider Depart.</strong> The warehouse Dispatch action and the rider's Depart call are independent — warehouse dispatches when the vehicle is ready; rider calls Depart after finishing loading.</li>
</ol>

</body>
</html>
