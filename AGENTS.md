# AGENTS.md

Cold-start briefing for AI agents working in the **Parcelman Express** codebase.

> This is a Laravel 12 monolith that runs a multi-warehouse parcel logistics network. Vendors create shipments, admins price them, drivers pick up → warehouses receive/sort/transport → drivers deliver. Web portals (Blade) serve admins and warehouse staff; REST APIs (Sanctum) serve vendor and driver mobile apps.

---

## 1. Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel **12** on PHP **8.2+** |
| DB | SQLite (dev, `database/database.sqlite`), MySQL-compatible schemas |
| Web auth | Session, multi-guard (`web`, `admin`) |
| API auth | Laravel **Sanctum** (bearer tokens) |
| Queues | Database driver + **Laravel Horizon** |
| Storage | Local + AWS S3 (`league/flysystem-aws-s3-v3`) |
| Frontend | **Blade + Tailwind 4 + Alpine.js 3 + vanilla JS** (no Vue/React/Livewire/Inertia) |
| Build | **Vite 7** (`laravel-vite-plugin`) |
| PDFs | `barryvdh/laravel-dompdf` (invoices, labels) |
| Excel | `maatwebsite/excel` (exports) |
| Tests | **Pest 4** + PHPUnit |
| Lint | Laravel **Pint** |

No third-party RBAC package — roles/permissions are homegrown (see [app/Enums/](app/Enums/) and `Role`/`Permission` models).

---

## 2. Commands

```bash
composer setup        # install, .env, key:generate, migrate, npm i, npm build
composer dev          # concurrently: php artisan serve + queue:listen + vite
composer test         # config:clear then php artisan test (Pest)
npm run dev           # vite dev server only
npm run build         # production build
./vendor/bin/pint     # format PHP
php artisan horizon   # queue dashboard (requires queue worker)
```

`composer dev` is the one-shot dev command — runs Laravel (`:8000`), queue listener, and Vite together.

---

## 3. Repository layout

```
app/
├── Enums/                 Status + type enums — source of truth for domain states
├── Events/ + Listeners/   Status-change pub/sub (notifications, audit)
├── Exports/               Excel export classes
├── Helpers/               PhoneHelper etc.
├── Http/
│   ├── Controllers/
│   │   ├── Admin/         Super-admin portal (~23 controllers)
│   │   ├── Warehouse/     Warehouse-staff portal (~10 controllers)
│   │   └── Api/V1/        Mobile APIs (vendor + driver)
│   ├── Middleware/        admin.audit, system.user, warehouse.user, vendor.active
│   └── Requests/          FormRequest validation
├── Models/                ~37 Eloquent models (see §5)
├── Observers/             Fire events on model lifecycle
├── Providers/
├── Services/              Business logic — controllers stay thin (see §6)
│   └── Warehouse/         Warehouse-specific services
└── Support/

routes/
├── web.php                Admin + warehouse + vendor/driver web portals
├── api.php                /api/v1/* — vendor & driver mobile APIs
└── console.php

resources/
├── views/
│   ├── admin/             Admin portal Blade templates
│   ├── warehouse/         Warehouse portal Blade templates
│   ├── web/{vendor,driver}/   Public-facing portals
│   └── pdf/               Invoice + label PDFs
├── js/{admin,warehouse,web}/  Per-portal JS modules
└── css/

database/
├── migrations/            ~81 migrations, timestamped from 2026-01-26
├── factories/
└── seeders/

docs/                      BUSINESS_FLOW.md, PLAN.md, TIMELINE.md,
                           mobile-api-changelog-fulfillment-type.md
tests/{Feature,Unit}/      Pest tests (RefreshDatabase available)
```

**Non-standard:** `/docs` holds authoritative business-flow docs — read [docs/BUSINESS_FLOW.md](docs/BUSINESS_FLOW.md) before making domain changes.

---

## 4. User types & portals

| User | Auth | Surface | Scope |
|---|---|---|---|
| **Admin** (super admin) | Session (`admin` guard) | `/admin/*` web portal | Global |
| **Warehouse User** (staff/manager) | Session (`web` guard) | `/warehouse/*` web portal | **Single warehouse** (scoped by `warehouse_id`) |
| **Vendor** | Sanctum (OTP login) | `/api/v1/vendor/*` + `/vendor/*` web | Own shipments only |
| **Driver** | Sanctum (PIN/password login) | `/api/v1/driver/*` + `/driver/*` web | Own assignments only |
| **Customer** | None | Receives SMS/OTP for delivery | Passive |

Middleware enforces scoping: `system.user` (super admin only), `warehouse.user` (warehouse staff), `vendor.active`, `admin.audit` (logs admin actions).

---

## 5. Domain model

The shipment is the spine. Everything else attaches to it.

**Pipeline:** `Shipment → Invoice → PickupAssignment → WarehouseReceipt → (SortBatch → TransportManifest)* → DeliveryRun → delivered`

| Model | Purpose |
|---|---|
| `Shipment` | Vendor's delivery request. 13-state lifecycle (see `ShipmentStatus` enum). |
| `ShipmentItem` | Individual parcel; has images, tracking history, `fulfillment_type` |
| `Invoice` | Admin-priced quote; vendor accepts/rejects (`InvoiceStatus` enum) |
| `PickupAssignment` | Driver assignment to pick up from vendor (GPS + photos + OTP) |
| `WarehouseReceipt` + `...Item` + `...ItemLabel` | Goods-in; barcode labels generated here |
| `SortBatch` + `SortBatchItem` | Group items by destination before transport |
| `TransportManifest` + `...Item` + `...Assignment` | Inter-warehouse movement with driver |
| `DeliveryRun` + `DeliveryRunStop` + `DeliveryRunItem` | Last-mile; one **stop per customer/location** |
| `DeliveryVerificationAttempt` + `OtpCode` | Delivery proof |
| `ShipmentCollection` | Self-pickup fulfillment path |
| `LabelCustodyEvent` | Barcode/label handoff chain of custody |
| `Warehouse`, `Driver`, `Vendor`, `User`, `Admin` | Actors |
| `Region`, `District`, `Location` | Address hierarchy |
| `AdminAuditLog`, `VendorActivityLog`, `DriverActivityLog`, `NotificationLog` | Audit |
| `PlatformSetting` | DB-backed runtime config |

**Fulfillment modes** (`FulfillmentType` enum): `WAREHOUSE` | `SELF_PICKUP` | `DIRECT` — set per-item, not per-shipment.

All status fields are **backed string enums** in [app/Enums/](app/Enums/). Treat those as the canonical state machine — do not introduce free-form status strings.

---

## 6. Architectural conventions

- **Services own business logic.** Controllers orchestrate HTTP + validation + call services. When adding a feature, check [app/Services/](app/Services/) first — there's probably already a service to extend (e.g. `ShipmentService`, `InvoiceService`, `PickupAssignmentService`, `WarehouseReceivingService`).
- **Events + Listeners handle side effects.** Status changes fire events (`ShipmentStatusChanged`, `InvoiceAcceptedByVendor`, `PickupAssignmentStatusChanged`, etc.); listeners send SMS/push/email and write to audit logs. Don't trigger notifications inline from controllers.
- **Observers** wire model lifecycle → events.
- **FormRequest** classes validate inbound payloads — don't validate inline in controllers.
- **Enums everywhere** for status/type. Use `->value` for persistence, enum instance in code.
- **Soft deletes** are used on `Shipment`, `Vendor`, `Warehouse`, `Invoice`, and more — check the model before assuming hard delete.
- **Auto-generated codes:** `invoice_number`, `shipment_number`, `warehouse_code`, barcodes. Don't set these manually in new code.
- **API versioning:** all mobile endpoints live under `/api/v1/`. New endpoints go there unless explicitly starting v2.
- **Warehouse scoping is security-critical.** Any query reachable from `/warehouse/*` must filter by the authenticated user's `warehouse_id`. Super admin queries in `/admin/*` are unscoped.

---

## 7. Frontend conventions

- **Blade + Tailwind + Alpine.** No SPA framework. Keep it that way unless the user asks otherwise.
- **Per-portal JS:** [resources/js/admin/](resources/js/admin/), [resources/js/warehouse/](resources/js/warehouse/), [resources/js/web/](resources/js/web/) each have their own entry. Vite builds all three.
- **Per-portal CSS:** [resources/css/pages/](resources/css/pages/) has `vendor-portal.css`, `driver-portal.css`, `warehouse-portal.css`.
- **Alpine** for modals/dropdowns/collapsibles, **DataTables** for tabular views, **Chart.js** (CDN) for dashboard charts.
- **Toast notifications** go through `admin-utils.js` helpers — don't roll your own.
- The admin dashboard was recently redesigned — see recent commits for the visual direction (clean Stripe-style, fullscreen Quick Actions modal). Match that style for new admin pages.

---

## 8. Testing

- Pest 4 (`composer test`). Tests split into `tests/Feature/` and `tests/Unit/`.
- `RefreshDatabase` trait is available; not globally applied — opt in per test.
- Factories exist for core models in [database/factories/](database/factories/).
- Run a single file: `./vendor/bin/pest tests/Feature/Foo.php`.
- SQLite test DB is the default — keep tests portable (no MySQL-only SQL).

---

## 9. Working in this repo — defaults for agents

- **Read [docs/BUSINESS_FLOW.md](docs/BUSINESS_FLOW.md)** before touching shipment, pickup, receiving, sorting, transport, or delivery logic. The state machine has many branches; the doc is the spec.
- **Don't invent status strings** — add/use values in the corresponding `Enum` in [app/Enums/](app/Enums/).
- **Put business logic in a Service**, not a controller. If the logic crosses two models, that's a service.
- **Fire an Event for cross-cutting side effects** (notifications, audit) instead of calling them directly.
- **Respect warehouse scoping** in every query behind warehouse-auth middleware.
- **Run `./vendor/bin/pint`** before finishing a change set. Run `composer test` if you touched non-trivial logic.
- **Mobile API changes:** bump or document in [docs/mobile-api-changelog-fulfillment-type.md](docs/mobile-api-changelog-fulfillment-type.md) — there is an established changelog pattern.
- **.env** is local-only. Don't commit secrets. `config/*.php` reads via `env()` only during cache-building; prefer `config()` in app code.
