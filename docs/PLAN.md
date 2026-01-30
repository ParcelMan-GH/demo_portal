# Parcelman Express - Implementation Plan

> **For AI Agents:** This document contains the complete implementation plan for a logistics/delivery management system. Follow phases in order. Each phase has clear deliverables and verification steps.

---

## Project Summary

**What:** A logistics system that moves items from Vendor → Warehouse → Customer
**Stack:** Laravel 12 + Blade + Tailwind CSS + Vanilla JavaScript
**Database:** MySQL
**Users:** Super Admin, Warehouse Manager, Driver, Vendor, Customer

---

## Architecture Overview

| Interface | Users | Technology |
|-----------|-------|------------|
| **Web Admin Panel** | Super Admin | Blade + Tailwind + Vanilla JS |
| **Web Warehouse Panel** | Warehouse Manager | Blade + Tailwind + Vanilla JS |
| **Mobile App (Native)** | Vendors + Drivers | External app consuming REST APIs |

> **Note:** Mobile app is a single native application for both Vendors and Drivers. This Laravel project provides the REST APIs for the mobile app.

---

## Tech Stack

```
BACKEND
├── Framework: Laravel 12 (PHP 8.2+)
├── Database: SQLite (dev) → MySQL (prod)
├── Auth: Laravel Sanctum (API tokens for mobile, Session for web)
├── RBAC: Spatie Laravel-Permission
├── Queue: Database driver
└── Settings: Database-driven (admin configurable)

FRONTEND (Web Panels)
├── Templates: Blade
├── CSS: Tailwind CSS 4.0
├── JS: Vanilla JavaScript (no frameworks)
├── Charts: Chart.js
└── Build: Vite 7.0

API (Mobile App)
├── REST API with JSON responses
├── Auth: Laravel Sanctum (token-based)
├── Versioned: /api/v1/
└── Endpoints for Vendors and Drivers

EXTERNAL SERVICES (Admin Configurable via DB)
├── SMS: Arkesel (Ghana)
├── Push Notifications: Firebase FCM
├── File Storage: Local OR Storj (S3-compatible)
└── Barcodes: picqer/php-barcode-generator
```

---

## Packages to Install

```bash
# Backend
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require spatie/laravel-settings
composer require intervention/image
composer require picqer/php-barcode-generator
composer require barryvdh/laravel-dompdf
composer require spatie/laravel-medialibrary
composer require league/flysystem-aws-s3-v3

# Dev
composer require --dev barryvdh/laravel-debugbar

# Frontend
npm install chart.js
```

---

## Database Schema

### Settings Table (Dynamic Configuration)
```
settings
├── id (primary key)
├── group (string) - e.g., "general", "sms", "storage"
├── key (string, unique)
├── value (json)
├── type (string) - "string", "boolean", "integer", "json", "file"
├── created_at
└── updated_at

Example records:
- general.app_name = "Parcelman Express"
- general.currency = "GHS"
- sms.arkesel_api_key = "xxx"
- storage.disk = "local" | "storj"
- storage.storj_access_key = "xxx"
```

### Location Tables
```
regions
├── id, name, code
└── timestamps

districts
├── id, region_id (FK), name, code
└── timestamps

towns
├── id, district_id (FK), name
└── timestamps
```

### Users & Vendors
```
users
├── id (primary key)
├── name (string)
├── email (string, unique)
├── phone (string, unique, nullable)
├── password (string)
├── role (enum: super_admin, warehouse_manager, driver)
├── warehouse_id (FK, nullable) - for scoped access
├── is_active (boolean, default true)
├── email_verified_at (timestamp, nullable)
├── remember_token
└── timestamps

vendors
├── id (primary key)
├── business_name (string)
├── contact_name (string)
├── phone (string, unique) - used for OTP login
├── email (string, nullable)
├── pin_hash (string, nullable) - for PIN authentication
├── address (text, nullable)
├── town_id (FK, nullable)
├── is_active (boolean, default true)
└── timestamps
```

### Warehouses
```
warehouses
├── id (primary key)
├── name (string)
├── code (string, unique) - e.g., "WH-ACCRA-01"
├── type (enum: origin, destination, both) - warehouse can serve as origin, destination, or both
├── address (text)
├── town_id (FK)
├── latitude (decimal, nullable)
├── longitude (decimal, nullable)
├── manager_id (FK to users, nullable)
├── is_active (boolean, default true)
└── timestamps

Note: All locations are "Warehouses" - no distinction between hubs and warehouses.
Origin warehouses receive from vendors, destination warehouses deliver to customers.
A warehouse can be "both" if it serves both purposes.
```

### Batches & Items
```
batches
├── id (primary key)
├── vendor_id (FK)
├── batch_number (string, unique) - auto-generated "PCM-2026-00001"
├── status (enum: draft, submitted, invoice_sent, invoice_accepted,
│           pickup_assigned, picked_up, at_warehouse, sorted,
│           in_transit, at_destination, out_for_delivery, delivered, cancelled)
├── customer_name (string)
├── customer_phone (string)
├── customer_phone_verified (boolean) - entered twice to confirm
├── delivery_address (text)
├── delivery_town_id (FK)
├── gh_post_address (string, nullable)
├── delivery_latitude (decimal, nullable)
├── delivery_longitude (decimal, nullable)
├── destination_warehouse_id (FK) - target warehouse
├── pickup_driver_id (FK to users, nullable)
├── pickup_at (timestamp, nullable)
├── pickup_photos (json, nullable) - array of image paths
├── pickup_location (json, nullable) - {lat, lng}
├── total_items (integer, default 0)
├── total_weight (decimal, nullable)
├── notes (text, nullable)
└── timestamps

batch_items
├── id (primary key)
├── batch_id (FK)
├── barcode (string, unique, nullable) - generated at warehouse
├── product_name (string)
├── description (text, nullable)
├── quantity (integer, default 1)
├── weight (decimal, nullable)
├── status (enum: pending, picked_up, at_warehouse, in_transit,
│           at_destination, out_for_delivery, delivered, returned)
├── current_warehouse_id (FK, nullable)
├── delivery_driver_id (FK to users, nullable)
├── delivered_at (timestamp, nullable)
├── delivery_photo (string, nullable)
├── delivery_signature (text, nullable) - base64 signature
├── delivery_location (json, nullable) - {lat, lng}
├── delivery_notes (text, nullable)
└── timestamps

batch_item_images
├── id (primary key)
├── batch_item_id (FK)
├── image_path (string)
├── image_type (enum: product, pickup_proof, delivery_proof)
├── uploaded_by_type (string) - "vendor" or "user"
├── uploaded_by_id (integer)
└── created_at

batch_item_tracking
├── id (primary key)
├── batch_item_id (FK)
├── event (string) - e.g., "created", "picked_up", "scanned_in"
├── description (string)
├── warehouse_id (FK, nullable)
├── latitude (decimal, nullable)
├── longitude (decimal, nullable)
├── actor_type (string) - "vendor" or "user"
├── actor_id (integer)
├── metadata (json, nullable)
└── created_at
```

### Invoices
```
invoices
├── id (primary key)
├── batch_id (FK)
├── invoice_number (string, unique) - "INV-2026-00001"
├── vendor_id (FK)
├── pickup_fee (decimal, default 0)
├── transport_fee (decimal, default 0)
├── handling_fee (decimal, default 0)
├── total_amount (decimal)
├── status (enum: pending, accepted, rejected)
├── accepted_at (timestamp, nullable)
├── notes (text, nullable)
└── timestamps
```

### Driver Assignments & Transport
```
driver_assignments
├── id (primary key)
├── driver_id (FK to users)
├── assignment_type (enum: pickup, transport, delivery)
├── batch_id (FK, nullable) - for pickup/transport assignments
├── batch_item_id (FK, nullable) - for individual delivery
├── from_warehouse_id (FK, nullable) - for transport
├── to_warehouse_id (FK, nullable) - for transport
├── status (enum: pending, accepted, in_progress, completed, failed)
├── started_at (timestamp, nullable)
├── completed_at (timestamp, nullable)
├── failure_reason (text, nullable)
└── timestamps

transport_manifests
├── id (primary key)
├── manifest_number (string, unique) - "TM-ACCRA-KUM-2026-001"
├── from_warehouse_id (FK)
├── to_warehouse_id (FK)
├── driver_id (FK to users)
├── status (enum: created, dispatched, in_transit, received)
├── dispatched_at (timestamp, nullable)
├── received_at (timestamp, nullable)
└── timestamps

transport_manifest_items
├── id (primary key)
├── transport_manifest_id (FK)
├── batch_item_id (FK)
├── scanned_out_at (timestamp, nullable)
├── scanned_in_at (timestamp, nullable)
└── timestamps
```

### Auth & Notifications
```
otp_codes
├── id (primary key)
├── phone (string)
├── code (string) - 6 digits
├── expires_at (timestamp)
├── verified_at (timestamp, nullable)
└── created_at

device_tokens
├── id (primary key)
├── tokenable_type (string) - "vendor" or "user"
├── tokenable_id (integer)
├── token (string) - FCM token
├── device_type (string) - "ios", "android", "web"
├── is_active (boolean, default true)
└── timestamps
```

---

## Enums

```php
// app/Enums/UserRole.php
enum UserRole: string {
    case SUPER_ADMIN = 'super_admin';
    case WAREHOUSE_MANAGER = 'warehouse_manager';
    case DRIVER = 'driver';
}

// app/Enums/WarehouseType.php
enum WarehouseType: string {
    case ORIGIN = 'origin';           // Receives items from vendors
    case DESTINATION = 'destination'; // Delivers items to customers
    case BOTH = 'both';               // Can do both
}

// app/Enums/BatchStatus.php
enum BatchStatus: string {
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case INVOICE_SENT = 'invoice_sent';
    case INVOICE_ACCEPTED = 'invoice_accepted';
    case PICKUP_ASSIGNED = 'pickup_assigned';
    case PICKED_UP = 'picked_up';
    case AT_WAREHOUSE = 'at_warehouse';
    case SORTED = 'sorted';
    case IN_TRANSIT = 'in_transit';
    case AT_DESTINATION = 'at_destination';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}

// app/Enums/ItemStatus.php
enum ItemStatus: string {
    case PENDING = 'pending';
    case PICKED_UP = 'picked_up';
    case AT_WAREHOUSE = 'at_warehouse';
    case IN_TRANSIT = 'in_transit';
    case AT_DESTINATION = 'at_destination';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';
}

// app/Enums/AssignmentType.php
enum AssignmentType: string {
    case PICKUP = 'pickup';
    case TRANSPORT = 'transport';
    case DELIVERY = 'delivery';
}

// app/Enums/AssignmentStatus.php
enum AssignmentStatus: string {
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
```

---

## Implementation Phases

> **IMPORTANT:** Complete each phase fully before moving to the next. Verify each phase works before proceeding.

---

### PHASE 1: Foundation

**Goal:** Set up project infrastructure with packages and base layouts.

**Tasks:**
1. Install all composer packages listed above
2. Install all npm packages listed above
3. Run `php artisan vendor:publish` for installed packages
4. Configure vanilla JavaScript in `resources/js/app.js`:
   ```javascript
   // Import Tailwind CSS
   import '../css/app.css';

   // Global utility functions
   window.App = {
       // Modal management
       openModal(id) {
           document.getElementById(id)?.classList.remove('hidden');
       },
       closeModal(id) {
           document.getElementById(id)?.classList.add('hidden');
       },
       // Dropdown toggle
       toggleDropdown(id) {
           document.getElementById(id)?.classList.toggle('hidden');
       },
       // Confirm dialog
       confirm(message, callback) {
           if (window.confirm(message)) callback();
       }
   };

   // Close dropdowns when clicking outside
   document.addEventListener('click', (e) => {
       if (!e.target.closest('[data-dropdown]')) {
           document.querySelectorAll('[data-dropdown-menu]').forEach(el => el.classList.add('hidden'));
       }
   });
   ```
5. Create base Blade layouts
6. Create reusable Blade components
7. Set up Storj disk in `config/filesystems.php`

**Files to Create:**

```
resources/views/layouts/
├── app.blade.php           # Base layout with Tailwind + vanilla JS
├── admin.blade.php         # Admin panel layout with sidebar
├── warehouse.blade.php     # Warehouse manager layout
└── guest.blade.php         # Guest/auth pages layout

resources/views/components/
├── sidebar.blade.php
├── card.blade.php
├── table.blade.php
├── modal.blade.php
├── input.blade.php
├── select.blade.php
├── textarea.blade.php
├── button.blade.php
├── status-badge.blade.php
├── alert.blade.php
└── pagination.blade.php
```

**Verification:**
```bash
npm run build
php artisan serve
# Visit http://localhost:8000 - should see styled page
```

---

### PHASE 2: Database & Models

**Goal:** Create all migrations and Eloquent models.

**Migration Order (respect foreign keys):**
1. `create_settings_table`
2. `create_regions_table`
3. `create_districts_table`
4. `create_towns_table`
5. `create_warehouses_table`
6. `add_fields_to_users_table` (role, warehouse_id, phone, is_active)
7. `create_vendors_table`
8. `create_otp_codes_table`
9. `create_device_tokens_table`
10. `create_batches_table`
11. `create_batch_items_table`
12. `create_batch_item_images_table`
13. `create_batch_item_tracking_table`
14. `create_invoices_table`
15. `create_driver_assignments_table`
16. `create_transport_manifests_table`
17. `create_transport_manifest_items_table`
18. Run `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"` then migrate

**Models to Create:**

```
app/Models/
├── Setting.php
├── Region.php
├── District.php
├── Town.php
├── Warehouse.php
├── Vendor.php
├── Batch.php
├── BatchItem.php
├── BatchItemImage.php
├── BatchItemTracking.php
├── Invoice.php
├── DriverAssignment.php
├── TransportManifest.php
├── TransportManifestItem.php
├── OtpCode.php
└── DeviceToken.php
```

**Create Enums:**

```
app/Enums/
├── UserRole.php
├── WarehouseType.php
├── BatchStatus.php
├── ItemStatus.php
├── AssignmentType.php
├── AssignmentStatus.php
├── InvoiceStatus.php
└── ManifestStatus.php
```

**Key Relationships:**
- Region hasMany Districts
- District belongsTo Region, hasMany Towns
- Town belongsTo District
- Warehouse belongsTo Town, hasOne Manager (User)
- User belongsTo Warehouse (nullable)
- Vendor belongsTo Town
- Batch belongsTo Vendor, belongsTo DestinationWarehouse, hasMany BatchItems
- BatchItem belongsTo Batch, hasMany Images, hasMany Tracking
- Invoice belongsTo Batch, belongsTo Vendor
- DriverAssignment belongsTo User (driver), belongsTo Batch or BatchItem
- TransportManifest belongsTo FromWarehouse, ToWarehouse, Driver
- TransportManifestItem belongsTo Manifest, belongsTo BatchItem

**Verification:**
```bash
php artisan migrate:fresh
php artisan tinker
# Test: Region::factory()->create()
# Test relationships work
```

---

### PHASE 3: Authentication

**Goal:** Web authentication for admin and warehouse manager. API authentication handled in Phase 9.

> **Note:** Drivers and Vendors use the mobile app with API authentication (Laravel Sanctum tokens). This phase covers web-only authentication.

**Tasks:**
1. Create login page for web users (admin, warehouse manager)
2. Create logout functionality
3. Create password reset flow
4. Create role-based middleware
5. Set up role-based redirects after login
6. Block driver role from web login (they must use mobile app)

**Routes:**
```php
// routes/web.php
Route::get('login', [LoginController::class, 'showForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LogoutController::class, 'logout'])->name('logout');

Route::get('forgot-password', [PasswordResetController::class, 'showForm']);
Route::post('forgot-password', [PasswordResetController::class, 'sendLink']);
Route::get('reset-password/{token}', [PasswordResetController::class, 'showReset']);
Route::post('reset-password', [PasswordResetController::class, 'reset']);
```

**Redirect Logic:**
```php
// After login, redirect based on role:
// Drivers are blocked from web login - must use mobile app
match($user->role) {
    'super_admin' => redirect('/admin'),
    'warehouse_manager' => redirect('/warehouse'),
    'driver' => throw new \Exception('Please use the mobile app'),
};
```

**Files to Create:**

```
app/Http/Controllers/Auth/
├── LoginController.php
├── LogoutController.php
└── PasswordResetController.php

app/Http/Middleware/
├── RoleMiddleware.php         # Check user has required role
└── ScopeToWarehouse.php       # Limit warehouse_manager to their warehouse

resources/views/auth/
├── login.blade.php
├── forgot-password.blade.php
└── reset-password.blade.php
```

**Verification:**
- Create a test user in tinker with role 'super_admin'
- Login and verify redirect to /admin
- Test logout works
- Test middleware blocks unauthorized access

---

### PHASE 4: Core Services

**Goal:** Create business logic service classes.

**Services to Create:**

```
app/Services/
├── SettingsService.php      # Read/write settings from DB
├── StorageService.php       # Switch between local/Storj based on settings
├── ArkeselService.php       # Send SMS via Arkesel API
├── BarcodeService.php       # Generate barcodes for items
├── BatchService.php         # Batch lifecycle management
├── InvoiceService.php       # Create and manage invoices
├── TrackingService.php      # Log tracking events
└── ManifestService.php      # Transport manifest operations
```

**SettingsService Example:**
```php
class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }
}
```

**StorageService Example:**
```php
class StorageService
{
    public function disk(): string
    {
        return app(SettingsService::class)->get('storage.disk', 'local');
    }

    public function store(UploadedFile $file, string $path): string
    {
        return Storage::disk($this->disk())->putFile($path, $file);
    }
}
```

**Verification:**
- Write tests for each service
- Test SettingsService read/write
- Test StorageService with local disk

---

### PHASE 5: Super Admin Panel

**Goal:** Complete admin interface for managing everything.

**Routes (prefix: /admin):**
```php
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Settings
    Route::get('settings/{group?}', [SettingsController::class, 'index'])->name('settings');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

    // Resources
    Route::resource('users', UserController::class);
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('vendors', VendorController::class);
    Route::resource('regions', RegionController::class);
    Route::resource('districts', DistrictController::class);
    Route::resource('towns', TownController::class);

    // Batches & Invoices
    Route::resource('batches', BatchController::class)->only(['index', 'show']);
    Route::post('batches/{batch}/invoice', [InvoiceController::class, 'create'])->name('batches.invoice');
    Route::post('batches/{batch}/assign-pickup', [BatchController::class, 'assignPickup'])->name('batches.assign-pickup');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports');
});
```

**Controllers:**
```
app/Http/Controllers/Admin/
├── DashboardController.php    # Stats: batches, deliveries, revenue
├── SettingsController.php     # CRUD for settings (grouped)
├── UserController.php         # CRUD users (all roles)
├── WarehouseController.php    # CRUD warehouses
├── VendorController.php       # View/manage vendors
├── RegionController.php       # CRUD regions
├── DistrictController.php     # CRUD districts
├── TownController.php         # CRUD towns
├── BatchController.php        # View batches, assign pickup
├── InvoiceController.php      # Create invoices
└── ReportController.php       # Generate reports
```

**Views:**
```
resources/views/admin/
├── dashboard.blade.php
├── settings/
│   ├── index.blade.php        # Settings form by group
│   ├── general.blade.php
│   ├── sms.blade.php
│   ├── storage.blade.php
│   └── notifications.blade.php
├── users/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── warehouses/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── vendors/
│   ├── index.blade.php
│   └── show.blade.php
├── locations/
│   ├── regions/
│   ├── districts/
│   └── towns/
├── batches/
│   ├── index.blade.php
│   ├── show.blade.php
│   └── invoice-form.blade.php
└── reports/
    └── index.blade.php
```

**Verification:**
- Login as super_admin
- Create a warehouse
- Create users with different roles
- View and navigate all pages
- Update settings and verify they save

---

### PHASE 6: Warehouse Manager Panel

**Goal:** Warehouse-scoped operations for receiving, sorting, dispatching.

**Routes (prefix: /warehouse):**
```php
Route::middleware(['auth', 'role:warehouse_manager', 'scope.warehouse'])
    ->prefix('warehouse')->name('warehouse.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Incoming
    Route::get('incoming', [IncomingController::class, 'index'])->name('incoming');
    Route::post('incoming/{batch}/receive', [IncomingController::class, 'receive'])->name('incoming.receive');

    // Items
    Route::get('items', [ItemController::class, 'index'])->name('items.index');
    Route::post('items/{item}/barcode', [ItemController::class, 'generateBarcode'])->name('items.barcode');
    Route::post('items/{item}/scan', [ItemController::class, 'scan'])->name('items.scan');

    // Sorting
    Route::get('sorting', [SortingController::class, 'index'])->name('sorting');
    Route::post('sorting/group', [SortingController::class, 'group'])->name('sorting.group');

    // Manifests
    Route::resource('manifests', ManifestController::class);
    Route::post('manifests/{manifest}/dispatch', [ManifestController::class, 'dispatch'])->name('manifests.dispatch');

    // Receive from other warehouses
    Route::get('receive', [ReceiveController::class, 'index'])->name('receive.index');
    Route::post('receive/{manifest}', [ReceiveController::class, 'receive'])->name('receive.confirm');
});
```

**Controllers:**
```
app/Http/Controllers/Warehouse/
├── DashboardController.php    # Warehouse stats
├── IncomingController.php     # Batches arriving from pickup
├── ItemController.php         # Receive items, generate barcodes
├── SortingController.php      # Group items by destination
├── ManifestController.php     # Create/manage transport manifests
├── DispatchController.php     # Send items to other warehouses
└── ReceiveController.php      # Receive from other warehouses
```

**Key Features:**
- Dashboard shows: items in warehouse, pending dispatch, incoming
- Receive items: scan/enter each item, system generates barcode
- Print barcode labels
- Sort view: group items by destination warehouse
- Create manifest: select items going to same destination
- Assign transport driver to manifest
- Dispatch: mark manifest as sent
- Receive: scan incoming manifest items

**Verification:**
- Login as warehouse_manager
- Verify can only see their warehouse data
- Create a manifest
- Test barcode generation

---

### PHASE 7: Driver Management (Admin View)

**Goal:** Admin and Warehouse Manager interfaces for managing drivers. Drivers themselves use the mobile app only.

> **Note:** Drivers do NOT have a web panel. All driver interactions happen via the mobile app (REST APIs built in Phase 9). This phase covers admin/warehouse views for managing drivers.

**Admin Driver Management Routes:**
```php
// In admin routes
Route::resource('drivers', DriverController::class);
Route::get('drivers/{driver}/assignments', [DriverController::class, 'assignments'])->name('drivers.assignments');
Route::get('drivers/{driver}/history', [DriverController::class, 'history'])->name('drivers.history');
```

**Warehouse Driver Assignment Routes:**
```php
// In warehouse routes - assigning drivers to tasks
Route::get('drivers', [DriverController::class, 'available'])->name('drivers.available');
Route::post('assignments', [AssignmentController::class, 'store'])->name('assignments.store');
Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
```

**Controllers:**
```
app/Http/Controllers/Admin/
├── DriverController.php         # CRUD drivers, view history

app/Http/Controllers/Warehouse/
├── DriverController.php         # View available drivers for assignment
└── AssignmentController.php     # Create and track assignments
```

**Admin Views:**
```
resources/views/admin/drivers/
├── index.blade.php              # List all drivers
├── create.blade.php             # Create new driver
├── edit.blade.php               # Edit driver details
├── show.blade.php               # Driver profile with stats
├── assignments.blade.php        # Driver's current/past assignments
└── history.blade.php            # Driver delivery history
```

**Key Features:**
- Admin: Create/edit/deactivate drivers
- Admin: View driver assignment history and performance
- Warehouse: See available drivers for assignment
- Warehouse: Assign drivers to pickup/transport/delivery tasks
- Warehouse: Track assignment status in real-time

**Verification:**
- Login as admin, create a driver
- Login as warehouse_manager, assign driver to a task
- Verify assignment appears in driver's mobile app (via API)

---

### PHASE 8: Seeders

**Goal:** Create realistic test data for development and testing.

**Seeders to Create:**
```
database/seeders/
├── DatabaseSeeder.php           # Master seeder - calls all others
├── SettingsSeeder.php           # Default app settings
├── LocationSeeder.php           # Ghana regions, districts, towns
├── RolePermissionSeeder.php     # Spatie roles and permissions
├── WarehouseSeeder.php          # 3-4 test warehouses
├── UserSeeder.php               # Admin + managers + drivers
├── VendorSeeder.php             # 5-10 test vendors
├── BatchSeeder.php              # 20+ batches in various statuses
└── FlowDemoSeeder.php           # Complete flow demo
```

**Default Data:**

**Settings:**
```php
['group' => 'general', 'key' => 'app_name', 'value' => 'Parcelman Express'],
['group' => 'general', 'key' => 'currency', 'value' => 'GHS'],
['group' => 'general', 'key' => 'currency_symbol', 'value' => '₵'],
['group' => 'storage', 'key' => 'disk', 'value' => 'local'],
['group' => 'sms', 'key' => 'provider', 'value' => 'arkesel'],
```

**Users:**
```php
// Super Admin
['name' => 'Admin', 'email' => 'admin@parcelman.com', 'role' => 'super_admin', 'password' => 'password']

// Warehouse Managers (one per warehouse)
['name' => 'Kofi Accra', 'email' => 'kofi@parcelman.com', 'role' => 'warehouse_manager', 'warehouse_id' => 1]
['name' => 'Ama Kumasi', 'email' => 'ama@parcelman.com', 'role' => 'warehouse_manager', 'warehouse_id' => 2]

// Drivers
['name' => 'Driver One', 'email' => 'driver1@parcelman.com', 'role' => 'driver']
['name' => 'Driver Two', 'email' => 'driver2@parcelman.com', 'role' => 'driver']
```

**Warehouses:**
```php
['name' => 'Accra Central Warehouse', 'code' => 'WH-ACC-01', 'type' => 'origin']
['name' => 'Kumasi Warehouse', 'code' => 'WH-KUM-01', 'type' => 'destination']
['name' => 'Takoradi Warehouse', 'code' => 'WH-TAK-01', 'type' => 'destination']
['name' => 'Tamale Warehouse', 'code' => 'WH-TAM-01', 'type' => 'both']
```

**Verification:**
```bash
php artisan migrate:fresh --seed
# Login as admin@parcelman.com / password
# Verify all data exists
```

---

### PHASE 9: Mobile App REST APIs

**Goal:** REST API endpoints for the native mobile app (used by both Vendors and Drivers).

**API Features:**
- Token-based authentication via Laravel Sanctum
- JSON responses with consistent structure
- File upload support for photos
- GPS coordinates acceptance
- Push notification token registration

**API Response Format:**
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... },
    "errors": null
}
```

**Auth API Routes (prefix: /api/v1/auth):**
```php
Route::prefix('v1/auth')->group(function () {
    // Vendor Auth (OTP + PIN)
    Route::post('vendor/request-otp', [VendorAuthController::class, 'requestOtp']);
    Route::post('vendor/verify-otp', [VendorAuthController::class, 'verifyOtp']);
    Route::post('vendor/set-pin', [VendorAuthController::class, 'setPin']);
    Route::post('vendor/login', [VendorAuthController::class, 'loginWithPin']);

    // Driver Auth (Email + Password)
    Route::post('driver/login', [DriverAuthController::class, 'login']);

    // Common
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('register-device', [AuthController::class, 'registerDevice'])->middleware('auth:sanctum');
});
```

**Vendor API Routes (prefix: /api/v1/vendor):**
```php
Route::prefix('v1/vendor')->middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('profile', [VendorProfileController::class, 'show']);
    Route::put('profile', [VendorProfileController::class, 'update']);

    // Batches
    Route::get('batches', [VendorBatchController::class, 'index']);
    Route::post('batches', [VendorBatchController::class, 'store']);
    Route::get('batches/{batch}', [VendorBatchController::class, 'show']);
    Route::put('batches/{batch}', [VendorBatchController::class, 'update']);
    Route::delete('batches/{batch}', [VendorBatchController::class, 'destroy']);
    Route::post('batches/{batch}/submit', [VendorBatchController::class, 'submit']);

    // Batch Items
    Route::post('batches/{batch}/items', [VendorBatchItemController::class, 'store']);
    Route::put('batches/{batch}/items/{item}', [VendorBatchItemController::class, 'update']);
    Route::delete('batches/{batch}/items/{item}', [VendorBatchItemController::class, 'destroy']);
    Route::post('batches/{batch}/items/{item}/images', [VendorBatchItemController::class, 'uploadImage']);

    // Invoices
    Route::get('invoices', [VendorInvoiceController::class, 'index']);
    Route::get('invoices/{invoice}', [VendorInvoiceController::class, 'show']);
    Route::post('invoices/{invoice}/accept', [VendorInvoiceController::class, 'accept']);
    Route::post('invoices/{invoice}/reject', [VendorInvoiceController::class, 'reject']);

    // Tracking
    Route::get('tracking', [VendorTrackingController::class, 'index']);
    Route::get('tracking/{batch}', [VendorTrackingController::class, 'show']);

    // Locations (for dropdowns)
    Route::get('regions', [LocationController::class, 'regions']);
    Route::get('regions/{region}/districts', [LocationController::class, 'districts']);
    Route::get('districts/{district}/towns', [LocationController::class, 'towns']);
});
```

**Driver API Routes (prefix: /api/v1/driver):**
```php
Route::prefix('v1/driver')->middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('profile', [DriverProfileController::class, 'show']);

    // Dashboard
    Route::get('dashboard', [DriverDashboardController::class, 'index']);

    // Assignments
    Route::get('assignments', [DriverAssignmentController::class, 'index']);
    Route::get('assignments/{assignment}', [DriverAssignmentController::class, 'show']);
    Route::post('assignments/{assignment}/accept', [DriverAssignmentController::class, 'accept']);
    Route::post('assignments/{assignment}/start', [DriverAssignmentController::class, 'start']);
    Route::post('assignments/{assignment}/complete', [DriverAssignmentController::class, 'complete']);
    Route::post('assignments/{assignment}/fail', [DriverAssignmentController::class, 'fail']);

    // Pickup specific
    Route::post('pickup/{assignment}/confirm', [PickupController::class, 'confirm']);
    // Expected payload: { photos: [], location: {lat, lng}, notes: "" }

    // Transport specific
    Route::post('transport/{manifest}/scan-load', [TransportController::class, 'scanLoad']);
    Route::post('transport/{manifest}/scan-unload', [TransportController::class, 'scanUnload']);

    // Delivery specific
    Route::post('delivery/{assignment}/confirm', [DeliveryController::class, 'confirm']);
    // Expected payload: { photo: "", signature: "", location: {lat, lng}, notes: "" }

    // Barcode scanning
    Route::post('scan', [ScanController::class, 'scan']);
    Route::get('scan/{barcode}', [ScanController::class, 'lookup']);
});
```

**API Controllers:**
```
app/Http/Controllers/Api/V1/
├── Auth/
│   ├── VendorAuthController.php
│   ├── DriverAuthController.php
│   └── AuthController.php
├── Vendor/
│   ├── VendorProfileController.php
│   ├── VendorBatchController.php
│   ├── VendorBatchItemController.php
│   ├── VendorInvoiceController.php
│   └── VendorTrackingController.php
├── Driver/
│   ├── DriverProfileController.php
│   ├── DriverDashboardController.php
│   ├── DriverAssignmentController.php
│   ├── PickupController.php
│   ├── TransportController.php
│   ├── DeliveryController.php
│   └── ScanController.php
└── LocationController.php
```

**API Resources (JSON Transformers):**
```
app/Http/Resources/
├── VendorResource.php
├── BatchResource.php
├── BatchItemResource.php
├── InvoiceResource.php
├── AssignmentResource.php
├── TrackingResource.php
└── LocationResource.php
```

**Verification:**
- Test with Postman/Insomnia
- Vendor: Request OTP → Verify → Set PIN → Login → Create Batch
- Driver: Login → View Assignments → Accept → Complete
- Test file uploads
- Test error responses

---

### PHASE 10: Notifications

**Goal:** SMS and push notification system using database settings.

**SMS Notifications (Arkesel):**
```
app/Notifications/Sms/
├── OtpNotification.php              # OTP code to vendor
├── InvoiceSentNotification.php      # Invoice ready for vendor
├── PickupConfirmedNotification.php  # Pickup done - notify vendor
├── DeliveryUpdateNotification.php   # Status update to customer
└── DeliveredNotification.php        # Delivery complete - notify vendor + customer
```

**Push Notifications (Firebase):**
```
app/Notifications/Push/
├── NewAssignmentNotification.php    # Driver gets new assignment
├── AssignmentUpdateNotification.php # Assignment status changed
└── BatchStatusNotification.php      # Vendor batch status update
```

**ArkeselService:**
```php
class ArkeselService
{
    public function send(string $phone, string $message): bool
    {
        $settings = app(SettingsService::class);

        $response = Http::post('https://sms.arkesel.com/api/v2/sms/send', [
            'sender' => $settings->get('sms.arkesel_sender_id'),
            'message' => $message,
            'recipients' => [$phone],
        ])->withHeaders([
            'api-key' => $settings->get('sms.arkesel_api_key'),
        ]);

        return $response->successful();
    }
}
```

**Verification:**
- Configure Arkesel API key in settings
- Send test SMS
- Configure Firebase credentials
- Send test push notification

---

### PHASE 11: Testing & Polish

**Goal:** Comprehensive testing and final polish.

**Tests to Write:**
```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   └── VendorOtpTest.php
│   ├── Admin/
│   │   ├── DashboardTest.php
│   │   ├── UserManagementTest.php
│   │   ├── WarehouseManagementTest.php
│   │   └── SettingsTest.php
│   ├── Warehouse/
│   │   ├── IncomingTest.php
│   │   ├── BarcodeTest.php
│   │   └── ManifestTest.php
│   └── Driver/
│       ├── AssignmentTest.php
│       └── CompletionTest.php
└── Unit/
    ├── Services/
    │   ├── SettingsServiceTest.php
    │   ├── BarcodeServiceTest.php
    │   └── BatchServiceTest.php
    └── Models/
        ├── BatchTest.php
        └── InvoiceTest.php
```

**Polish Tasks:**
- Add loading states to all forms
- Add success/error toast notifications
- Improve form validation messages
- Add confirmation dialogs for destructive actions
- Optimize database queries (eager loading)
- Add pagination to all list views
- Mobile responsive testing
- Cross-browser testing

**Verification:**
```bash
php artisan test
# All tests pass

# Manual testing
- Complete full flow: vendor creates batch → admin invoices → driver picks up → warehouse receives → transport → delivery
```

---

## Directory Structure

```
parcelman-express/
├── app/
│   ├── Enums/
│   │   ├── UserRole.php
│   │   ├── WarehouseType.php
│   │   ├── BatchStatus.php
│   │   ├── ItemStatus.php
│   │   ├── AssignmentType.php
│   │   ├── AssignmentStatus.php
│   │   ├── InvoiceStatus.php
│   │   └── ManifestStatus.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/              # Web authentication
│   │   │   ├── Admin/             # Super Admin web panel
│   │   │   ├── Warehouse/         # Warehouse Manager web panel
│   │   │   └── Api/
│   │   │       └── V1/            # Mobile app REST APIs
│   │   │           ├── Auth/
│   │   │           ├── Vendor/
│   │   │           └── Driver/
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php
│   │   │   └── ScopeToWarehouse.php
│   │   ├── Requests/
│   │   └── Resources/             # API JSON transformers
│   ├── Models/
│   │   ├── User.php
│   │   ├── Setting.php
│   │   ├── Region.php
│   │   ├── District.php
│   │   ├── Town.php
│   │   ├── Warehouse.php
│   │   ├── Vendor.php
│   │   ├── Batch.php
│   │   ├── BatchItem.php
│   │   ├── BatchItemImage.php
│   │   ├── BatchItemTracking.php
│   │   ├── Invoice.php
│   │   ├── DriverAssignment.php
│   │   ├── TransportManifest.php
│   │   ├── TransportManifestItem.php
│   │   ├── OtpCode.php
│   │   └── DeviceToken.php
│   ├── Notifications/
│   │   ├── Sms/
│   │   └── Push/
│   └── Services/
│       ├── SettingsService.php
│       ├── StorageService.php
│       ├── ArkeselService.php
│       ├── BarcodeService.php
│       ├── BatchService.php
│       ├── InvoiceService.php
│       ├── TrackingService.php
│       └── ManifestService.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   ├── components/
│   │   ├── auth/
│   │   ├── admin/
│   │   └── warehouse/
│   ├── js/
│   │   └── app.js                 # Vanilla JS utilities
│   └── css/
│       └── app.css                # Tailwind imports
├── routes/
│   ├── web.php                    # Web panel routes
│   └── api.php                    # Mobile app API routes
└── docs/
    ├── BUSINESS_FLOW.md
    └── PLAN.md
```

---

## Quick Reference

### User Credentials (After Seeding)

**Web Panel Users:**
| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@parcelman.com | password |
| Warehouse Manager (Accra) | kofi@parcelman.com | password |
| Warehouse Manager (Kumasi) | ama@parcelman.com | password |

**Mobile App Users (API):**
| Role | Login Method | Credentials |
|------|--------------|-------------|
| Driver | Email + Password | driver1@parcelman.com / password |
| Vendor | Phone + OTP/PIN | 0244123456 / PIN: 1234 |

### Status Flow
```
Batch: draft → submitted → invoice_sent → invoice_accepted → pickup_assigned →
       picked_up → at_warehouse → sorted → in_transit → at_destination →
       out_for_delivery → delivered | cancelled

Item:  pending → picked_up → at_warehouse → in_transit → at_destination →
       out_for_delivery → delivered | returned
```

### Key Commands
```bash
# Development
composer install
npm install
npm run dev
php artisan serve

# Database
php artisan migrate:fresh --seed

# Testing
php artisan test

# Production Build
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Notes for AI Agents

1. **Always run migrations before testing models**
2. **Check foreign key order when creating migrations**
3. **Use enums for status fields, not raw strings**
4. **All settings should come from SettingsService, not .env**
5. **Storage operations must use StorageService (supports local/Storj)**
6. **Warehouse managers can only see their warehouse data**
7. **Drivers can only see their own assignments**
8. **Barcodes are generated ONLY at warehouse, not before**
9. **GPS is captured at pickup confirmation and delivery confirmation**
10. **Photos are required at pickup and delivery as proof**
11. **Use vanilla JavaScript for web panels - NO Alpine.js or other JS frameworks**
12. **Mobile app uses REST APIs - all mobile functionality via /api/v1/ endpoints**
13. **All locations are "Warehouses" - no Hub distinction (use warehouse type: origin/destination/both)**
14. **API responses must follow consistent JSON structure with success, message, data, errors**
15. **Use Laravel Sanctum tokens for API auth, sessions for web auth**
