# Parcelman Express - Implementation Timeline

> Tracking progress of development tasks

---

## Phase 1: Vendor Authentication APIs

**Started**: January 26, 2026

### Tasks

| Task | Status | Date |
|------|--------|------|
| Create TIMELINE.md | Done | Jan 26, 2026 |
| Install Laravel Sanctum | Done | Jan 26, 2026 |
| Create platform_settings migration | Done | Jan 26, 2026 |
| Create vendors migration | Done | Jan 26, 2026 |
| Create otp_codes migration | Done | Jan 26, 2026 |
| Create vendor_activity_logs migration | Done | Jan 26, 2026 |
| Create PlatformSetting model | Done | Jan 26, 2026 |
| Create Vendor model | Done | Jan 26, 2026 |
| Create OtpCode model | Done | Jan 26, 2026 |
| Create VendorActivityLog model | Done | Jan 26, 2026 |
| Create SmsService (Arkesel) | Done | Jan 26, 2026 |
| Create OtpService | Done | Jan 26, 2026 |
| Create ActivityLogService | Done | Jan 26, 2026 |
| Create VendorAuthService | Done | Jan 26, 2026 |
| Create Form Request classes | Done | Jan 26, 2026 |
| Create VendorAuthController | Done | Jan 26, 2026 |
| Configure API routes | Done | Jan 26, 2026 |
| Create API Tester UI | Done | Jan 26, 2026 |
| Redesign API Tester UI (VS Code style) | Done | Jan 26, 2026 |
| Create PlatformSettingsSeeder | Done | Jan 26, 2026 |
| Run migrations and test | Done | Jan 26, 2026 |

---

## Phase 2: Vendor Profile APIs

**Started**: January 26, 2026

### Tasks

| Task | Status | Date |
|------|--------|------|
| Create VendorProfileService | Done | Jan 26, 2026 |
| Create UpdateProfileRequest | Done | Jan 26, 2026 |
| Create ChangePinRequest | Done | Jan 26, 2026 |
| Create VendorProfileController | Done | Jan 26, 2026 |
| Add profile routes to api.php | Done | Jan 26, 2026 |
| Update API Tester with profile endpoints | Done | Jan 26, 2026 |
| Update Change PIN to use OTP verification | Done | Jan 26, 2026 |

---

## Phase 3: Driver APIs

**Started**: January 26, 2026

### Tasks

| Task | Status | Date |
|------|--------|------|
| Create drivers migration | Done | Jan 26, 2026 |
| Create driver_activity_logs migration | Done | Jan 26, 2026 |
| Create Driver model | Done | Jan 26, 2026 |
| Create DriverActivityLog model | Done | Jan 26, 2026 |
| Create DriverActivityLogService | Done | Jan 26, 2026 |
| Create DriverAuthService | Done | Jan 26, 2026 |
| Create DriverProfileService | Done | Jan 26, 2026 |
| Create Driver form requests | Done | Jan 26, 2026 |
| Create DriverAuthController | Done | Jan 26, 2026 |
| Create DriverProfileController | Done | Jan 26, 2026 |
| Add driver routes to api.php | Done | Jan 26, 2026 |
| Update API Tester with Driver endpoints | Done | Jan 26, 2026 |

---

## Phase 4: Vendor Shipment APIs

**Started**: January 27, 2026

### Tasks

| Task | Status | Date |
|------|--------|------|
| Create ShipmentStatus and ItemStatus enums | Done | Jan 27, 2026 |
| Create regions migration | Done | Jan 27, 2026 |
| Create districts migration | Done | Jan 27, 2026 |
| Create shipments migration | Done | Jan 27, 2026 |
| Create shipment_items migration | Done | Jan 27, 2026 |
| Create shipment_item_images migration | Done | Jan 27, 2026 |
| Create shipment_item_tracking migration | Done | Jan 27, 2026 |
| Create Region model | Done | Jan 27, 2026 |
| Create District model | Done | Jan 27, 2026 |
| Create Shipment model | Done | Jan 27, 2026 |
| Create ShipmentItem model | Done | Jan 27, 2026 |
| Create ShipmentItemImage model | Done | Jan 27, 2026 |
| Create ShipmentItemTracking model | Done | Jan 27, 2026 |
| Create GhanaLocationsSeeder (16 regions + districts) | Done | Jan 27, 2026 |
| Create ShipmentSettingsSeeder (storage + settings) | Done | Jan 27, 2026 |
| Create LocationService | Done | Jan 27, 2026 |
| Create StorageService (S3/Storj + signed URLs) | Done | Jan 27, 2026 |
| Create ShipmentService | Done | Jan 27, 2026 |
| Create ShipmentItemService | Done | Jan 27, 2026 |
| Create shipment form requests | Done | Jan 27, 2026 |
| Create VendorLocationController | Done | Jan 27, 2026 |
| Create VendorShipmentController | Done | Jan 27, 2026 |
| Create VendorShipmentItemController | Done | Jan 27, 2026 |
| Add shipment routes to api.php | Done | Jan 27, 2026 |
| Update API Tester with shipment endpoints | Done | Jan 27, 2026 |
| Run migrations and seeders | Done | Jan 27, 2026 |

---

## Phase 5: Admin Portal - Users & RBAC

**Started**: January 27, 2026

### Tasks

| Task | Status | Date |
|------|--------|------|
| Create AdminRole enum | Done | Jan 27, 2026 |
| Create admins migration | Done | Jan 27, 2026 |
| Create Admin model | Done | Jan 27, 2026 |
| Update config/auth.php with admin guard | Done | Jan 27, 2026 |
| Create LoginRequest | Done | Jan 27, 2026 |
| Create CreateAdminRequest | Done | Jan 27, 2026 |
| Create UpdateAdminRequest | Done | Jan 27, 2026 |
| Create AuthController | Done | Jan 27, 2026 |
| Create DashboardController | Done | Jan 27, 2026 |
| Create AdminController | Done | Jan 27, 2026 |
| Add admin routes to web.php | Done | Jan 27, 2026 |
| Create admin layout (app.blade.php) | Done | Jan 27, 2026 |
| Create login view | Done | Jan 27, 2026 |
| Create dashboard view | Done | Jan 27, 2026 |
| Create admin index view | Done | Jan 27, 2026 |
| Create admin create view | Done | Jan 27, 2026 |
| Create admin show view | Done | Jan 27, 2026 |
| Create admin edit view | Done | Jan 27, 2026 |
| Create SuperAdminSeeder | Done | Jan 27, 2026 |
| Run migration and seeder | Pending | - |

---

## Setup Commands

Run these commands to complete setup:

```bash
# 1. Install dependencies
composer update

# 2. Publish Sanctum config
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 3. Run migrations
php artisan migrate

# 4. Seed data
php artisan db:seed --class=PlatformSettingsSeeder
php artisan db:seed --class=GhanaLocationsSeeder
php artisan db:seed --class=ShipmentSettingsSeeder
php artisan db:seed --class=SuperAdminSeeder

# 5. Start development server
php artisan serve
```

**API Tester**: http://localhost:8000/api-tester
**Admin Portal**: http://localhost:8000/admin

### Admin Login Credentials
- Email: `admin@parcelman.com`
- Password: `password`

---

## API Endpoints Created

### Vendor Auth
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/auth/vendor/register | POST | No | Register vendor |
| /api/v1/auth/vendor/verify-phone | POST | No | Verify phone with OTP |
| /api/v1/auth/vendor/login | POST | No | Login with PIN |
| /api/v1/auth/vendor/forgot-pin | POST | No | Request PIN reset OTP |
| /api/v1/auth/vendor/reset-pin | POST | No | Reset PIN with OTP |
| /api/v1/auth/vendor/logout | POST | Yes | Logout vendor |

### Vendor Profile
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/vendor/profile | GET | Yes | Get vendor profile |
| /api/v1/vendor/profile | PUT | Yes | Update vendor profile |
| /api/v1/vendor/request-pin-change | POST | Yes | Request OTP for PIN change |
| /api/v1/vendor/change-pin | PUT | Yes | Change PIN with OTP verification |

### Driver Auth
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/driver/login | POST | No | Login with email/password |
| /api/v1/driver/logout | POST | Yes | Logout driver |

### Driver Profile
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/driver/profile | GET | Yes | Get driver profile |
| /api/v1/driver/profile | PUT | Yes | Update driver profile |
| /api/v1/driver/change-password | PUT | Yes | Change password |

### Vendor Location
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/vendor/regions | GET | Yes | List all Ghana regions |
| /api/v1/vendor/regions/{region}/districts | GET | Yes | List districts in region |

### Vendor Shipments
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/vendor/shipments | GET | Yes | List vendor's shipments |
| /api/v1/vendor/shipments | POST | Yes | Create draft shipment |
| /api/v1/vendor/shipments/{shipment} | GET | Yes | View shipment with items |
| /api/v1/vendor/shipments/{shipment} | PUT | Yes | Update draft shipment |
| /api/v1/vendor/shipments/{shipment} | DELETE | Yes | Delete draft shipment |
| /api/v1/vendor/shipments/{shipment}/submit | POST | Yes | Submit for invoicing |

### Vendor Shipment Items
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/vendor/shipments/{shipment}/items | POST | Yes | Add item |
| /api/v1/vendor/shipments/{shipment}/items/{item} | PUT | Yes | Update item |
| /api/v1/vendor/shipments/{shipment}/items/{item} | DELETE | Yes | Remove item |
| /api/v1/vendor/shipments/{shipment}/items/{item}/images | POST | Yes | Upload image |
| /api/v1/vendor/shipments/{shipment}/items/{item}/images/{image} | DELETE | Yes | Delete image |

### Admin Portal (Web)
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /admin/login | GET | No | Show login form |
| /admin/login | POST | No | Authenticate admin |
| /admin/logout | POST | Yes | Logout admin |
| /admin | GET | Yes | Dashboard |
| /admin/admins | GET | Yes | List admins |
| /admin/admins/create | GET | Yes | Create admin form |
| /admin/admins | POST | Yes | Store new admin |
| /admin/admins/{admin} | GET | Yes | View admin details |
| /admin/admins/{admin}/edit | GET | Yes | Edit admin form |
| /admin/admins/{admin} | PUT | Yes | Update admin |
| /admin/admins/{admin}/toggle-active | PATCH | Yes | Toggle admin status |

---

## Files Created

### Migrations
- `database/migrations/2026_01_26_000001_create_platform_settings_table.php`
- `database/migrations/2026_01_26_000002_create_vendors_table.php`
- `database/migrations/2026_01_26_000003_create_otp_codes_table.php`
- `database/migrations/2026_01_26_000004_create_vendor_activity_logs_table.php`
- `database/migrations/2026_01_26_000005_add_pin_change_to_otp_codes_purpose.php`
- `database/migrations/2026_01_26_000006_create_drivers_table.php`
- `database/migrations/2026_01_26_000007_create_driver_activity_logs_table.php`
- `database/migrations/2026_01_27_000001_create_regions_table.php`
- `database/migrations/2026_01_27_000002_create_districts_table.php`
- `database/migrations/2026_01_27_000003_create_shipments_table.php`
- `database/migrations/2026_01_27_000004_create_shipment_items_table.php`
- `database/migrations/2026_01_27_000005_create_shipment_item_images_table.php`
- `database/migrations/2026_01_27_000006_create_shipment_item_tracking_table.php`
- `database/migrations/2026_01_27_100001_create_admins_table.php`

### Enums
- `app/Enums/ShipmentStatus.php`
- `app/Enums/ItemStatus.php`
- `app/Enums/AdminRole.php`

### Models
- `app/Models/PlatformSetting.php`
- `app/Models/Vendor.php`
- `app/Models/OtpCode.php`
- `app/Models/VendorActivityLog.php`
- `app/Models/Driver.php`
- `app/Models/DriverActivityLog.php`
- `app/Models/Region.php`
- `app/Models/District.php`
- `app/Models/Shipment.php`
- `app/Models/ShipmentItem.php`
- `app/Models/ShipmentItemImage.php`
- `app/Models/ShipmentItemTracking.php`
- `app/Models/Admin.php`

### Services
- `app/Services/SmsService.php`
- `app/Services/OtpService.php`
- `app/Services/ActivityLogService.php`
- `app/Services/VendorAuthService.php`
- `app/Services/VendorProfileService.php`
- `app/Services/DriverActivityLogService.php`
- `app/Services/DriverAuthService.php`
- `app/Services/DriverProfileService.php`
- `app/Services/LocationService.php`
- `app/Services/StorageService.php`
- `app/Services/ShipmentService.php`
- `app/Services/ShipmentItemService.php`

### Form Requests
- `app/Http/Requests/Api/Vendor/RegisterRequest.php`
- `app/Http/Requests/Api/Vendor/VerifyPhoneRequest.php`
- `app/Http/Requests/Api/Vendor/LoginRequest.php`
- `app/Http/Requests/Api/Vendor/ForgotPinRequest.php`
- `app/Http/Requests/Api/Vendor/ResetPinRequest.php`
- `app/Http/Requests/Api/Vendor/UpdateProfileRequest.php`
- `app/Http/Requests/Api/Vendor/ChangePinRequest.php`
- `app/Http/Requests/Api/Vendor/Shipment/CreateShipmentRequest.php`
- `app/Http/Requests/Api/Vendor/Shipment/UpdateShipmentRequest.php`
- `app/Http/Requests/Api/Vendor/Shipment/AddItemRequest.php`
- `app/Http/Requests/Api/Vendor/Shipment/UpdateItemRequest.php`
- `app/Http/Requests/Api/Vendor/Shipment/UploadImageRequest.php`
- `app/Http/Requests/Api/Driver/LoginRequest.php`
- `app/Http/Requests/Api/Driver/UpdateProfileRequest.php`
- `app/Http/Requests/Api/Driver/ChangePasswordRequest.php`
- `app/Http/Requests/Admin/LoginRequest.php`
- `app/Http/Requests/Admin/CreateAdminRequest.php`
- `app/Http/Requests/Admin/UpdateAdminRequest.php`

### Controllers
- `app/Http/Controllers/Api/V1/Auth/VendorAuthController.php`
- `app/Http/Controllers/Api/V1/VendorProfileController.php`
- `app/Http/Controllers/Api/V1/VendorLocationController.php`
- `app/Http/Controllers/Api/V1/VendorShipmentController.php`
- `app/Http/Controllers/Api/V1/VendorShipmentItemController.php`
- `app/Http/Controllers/Api/V1/Auth/DriverAuthController.php`
- `app/Http/Controllers/Api/V1/DriverProfileController.php`
- `app/Http/Controllers/Admin/AuthController.php`
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Admin/AdminController.php`

### Views
- `resources/views/api-tester.blade.php`
- `resources/views/admin/layouts/app.blade.php`
- `resources/views/admin/auth/login.blade.php`
- `resources/views/admin/dashboard/index.blade.php`
- `resources/views/admin/admins/index.blade.php`
- `resources/views/admin/admins/create.blade.php`
- `resources/views/admin/admins/show.blade.php`
- `resources/views/admin/admins/edit.blade.php`

### Seeders
- `database/seeders/PlatformSettingsSeeder.php`
- `database/seeders/GhanaLocationsSeeder.php`
- `database/seeders/ShipmentSettingsSeeder.php`
- `database/seeders/SuperAdminSeeder.php`

### Routes
- `routes/api.php`
- Updated `routes/web.php`
- Updated `bootstrap/app.php`

---

## Notes

- Vendor auth uses Phone + 4-digit PIN
- Driver auth uses Email + Password (drivers created by admin)
- OTP is 6 digits, sent via Arkesel SMS (sender: SHAXI)
- API tester at /api-tester (no auth required)
- Activity logs track device info from headers
- OTP codes logged to Laravel log for development testing
- Separate tokens for Vendor and Driver in API tester

### Phase 4 Notes
- Shipments use configurable numbering: `{prefix}-{year}-{padded_number}` (default: PCM-2026-00001)
- Item tracking codes: `{prefix}{random}` (default: TRK8A3F2K9X)
- Storage configurable between `local` and `s3` (Storj) via platform_settings
- S3 bucket is private - images use signed URLs with configurable expiry
- Location supports 3 methods: dropdown (region+district+town), GPS coordinates, or Ghana Post address
- Recipient phone must be entered twice for confirmation
- Only draft shipments can be edited/deleted
- Must have at least 1 item to submit shipment
- Max 5 images per item (configurable via platform_settings)
- Ghana locations: 16 regions with 218 districts seeded

### Phase 5 Notes
- Admin portal at `/admin` with session-based authentication
- Separate `admins` table (not using Laravel's default `users` table)
- Custom `admin` guard in config/auth.php
- Three roles: `super_admin`, `warehouse_manager`, `warehouse_staff`
- Role-based access control (RBAC) implemented in controllers
- Super Admin: Full access, can create any role
- Warehouse Manager: Can create staff only, sees only admins they created
- Warehouse Staff: Read-only access, cannot create users
- Uses Tailwind CSS + Alpine.js for UI
- Default admin: admin@parcelman.com / password

---

*Last Updated: January 27, 2026 (Phase 5 completed)*
