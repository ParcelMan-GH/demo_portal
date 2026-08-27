# ParcelMan Express Admin — Page Fix Report

**Date:** 2026-08-27 · **Repo:** `ParcelMan-GH/demo_portal` · **Live site:** `new.parcelmanexpress.com`
**Fix commit (local):** `261fdb3` — *"fix: register delivery runs module in admin bundle + graceful outgoing batches fetch"*

---

## 1. What was broken (3 pages)

| Page | URL | Symptom |
|---|---|---|
| Outgoing Batches | `/admin/operations/manifests/transport` | Table empty, cards at 0. Console: `GET /admin/transport-manifests-data … 500`, `Unexpected token '<' … is not valid JSON` |
| Incoming Batches | `/admin/operations/manifests/incoming` | Table empty ("No incoming batches match the current query") |
| Delivery Runs | `/admin/operations/deliveries/runs` | Blank table ("Showing to of"), ~47 Alpine `ReferenceError`s: `warehouseDeliveryRunsPage is not defined`, `meta`, `runStats`, `loading`, `showCreateModal`, … |

---

## 2. Root causes

### A. Delivery Runs — Alpine component never registered (code bug — FIXED)

All backoffice pages load the **admin JS bundle** (`resources/js/admin/app.js`) via the layout. That bundle imported the incoming-manifests modules but **not** the delivery-runs module, so `Alpine.data('warehouseDeliveryRunsPage', …)` (defined in `resources/js/warehouse/modules/deliveries/runs.js`) was never registered. Alpine then threw `warehouseDeliveryRunsPage is not defined` and every binding cascaded into `X is not defined` — killing the whole page.

**Evidence:** live console showed `module.esm-L4ggBBWN.js` — a chunk hash identical to the repo's own build, confirming the live site runs this codebase.

**Fix:** added `import '../warehouse/modules/deliveries/runs.js';` to `resources/js/admin/app.js` and rebuilt Vite assets. Verified the compiled admin bundle now pulls the shared `runs-*.js` chunk.

### B. Outgoing + Incoming Batches — missing DB table (server/deployment — NOT a code bug)

Both data endpoints 500 with:

```
Illuminate\Database\QueryException
SQLSTATE[42S02]: Base table or view not found: 1146
Table 'parcelman_demo.outgoing_batches' doesn't exist
… (app/Http/Controllers/Admin/AdminTransportManifestController.php:103 and :219)
```

The live MySQL database simply does **not** have the `outgoing_batches` table (nor the `shipment_items.outgoing_batch_id` column). The migration `database/migrations/2026_08_24_110355_create_outgoing_batches_table.php` was committed to the repo but **never run on the server**. The controllers and queries themselves are correct once the schema exists.

The Alpine "x is not defined" noise seen in the Outgoing/Incoming screenshots was stale console output from the previously opened Delivery Runs tab, plus the empty-table state produced by the failed fetch. The served HTML config attributes are clean (single-escaped JSON) — that part was already fixed in the repo.

**Fix (server-side, required):** run the pending migration on the server — see §4.

### C. UX hardening (included in commit)

The Outgoing Batches page now checks `res.ok` before parsing JSON and shows a toast on failure, instead of silently rendering "No outgoing batches match the current query" when the server actually errored.

---

## 3. Files changed in `261fdb3`

| File | Change |
|---|---|
| `resources/js/admin/app.js` | +1 import: warehouse `deliveries/runs.js` |
| `resources/views/warehouse/manifests/transport/index.blade.php` | fetch error handling on `loadData()` |
| `public/build/*` | Rebuilt Vite assets (new hashes, old removed) |

---

## 4. What you must do on the server (the actual page fix)

On the server, in the repo directory:

```bash
php artisan migrate --pretend   # preview which migrations will run
php artisan migrate             # apply them (creates outgoing_batches, adds outgoing_batch_id)
php artisan config:clear        # if routes/config were cached
```

**Fallback without shell access:** run `live-db-fix/outgoing_batches_migration.sql` in phpMyAdmin / MySQL CLI. It contains the exact `CREATE TABLE outgoing_batches` + `ALTER TABLE shipment_items ADD COLUMN outgoing_batch_id` statements.

Then **deploy the code commit** (git pull + rebuild assets if the server builds on deploy; the compiled assets are already committed in `public/build`).

---

## 5. Verification checklist (after deploy)

- [ ] `/admin/operations/manifests/transport` — table loads batches; cards show item counts; no console errors
- [ ] `/admin/operations/manifests/incoming` — table loads dispatched/in-transit batches; "Scan Incoming Package" works
- [ ] `/admin/operations/deliveries/runs` — summary cards populated; table shows runs; Create Run / filters work
- [ ] Browser console shows **zero** `ReferenceError`s on all three pages
