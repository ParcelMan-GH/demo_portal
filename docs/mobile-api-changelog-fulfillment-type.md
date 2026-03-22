# Mobile API Changelog — Fulfillment Type Feature

**Date**: 2026-03-22
**Version**: Fulfillment Type v1
**Affects**: Vendor App, Driver App
**Backwards Compatible**: Yes — all new fields are additive, existing behavior unchanged for `fulfillment_type: "warehouse"` (the default).

---

## Summary

Three fulfillment types are now supported:

| Value | Description | Driver Flow |
|-------|-------------|-------------|
| `warehouse` | Standard: items go through warehouse (default, existing behavior) | Pickup → Drive to warehouse |
| `self_pickup` | Recipient collects from warehouse | Pickup → Drive to warehouse (same as standard) |
| `direct` | Driver picks up and delivers directly, no warehouse stop | Pickup → Deliver to recipient |

---

## Vendor App Changes

### 1. Create Shipment — `POST /api/v1/vendor/shipments`

**New optional request field:**

```json
{
  "destination_mode": "single",
  "fulfillment_type": "warehouse",    // NEW — optional, defaults to "warehouse"
  "pickup_contact_name": "...",
  ...
}
```

Valid values: `"warehouse"`, `"self_pickup"`, `"direct"`

If omitted, defaults to `"warehouse"` (existing behavior — no app change required).

### 2. Update Shipment — `PUT /api/v1/vendor/shipments/{id}`

**New optional field (only while shipment is in `draft` status):**

```json
{
  "fulfillment_type": "self_pickup"
}
```

Cannot be changed after shipment is submitted.

### 3. Shipment Response — All Endpoints

**Two new fields added to every shipment object** (list, show, create, update responses):

```json
{
  "id": 123,
  "shipment_number": "PCM-2026-00001",
  "status": "at_warehouse",
  "fulfillment_type": "warehouse",      // NEW — always present
  "destination_mode": "single",
  "pickup": { ... },
  "delivery": { ... },
  "collection": null,                    // NEW — null for warehouse/direct, object for self_pickup
  "items": [ ... ],
  "can_edit": false,
  "can_delete": false,
  "can_submit": false,
  "submitted_at": "...",
  "created_at": "...",
  "updated_at": "...",
  "invoice": { ... },
  "invoice_history": [ ... ],
  "pickup_assignment": { ... }
}
```

### 4. Collection Object (self_pickup only)

When `fulfillment_type` is `"self_pickup"` and the shipment has reached the warehouse, the `collection` field contains:

**Before collection (status = "ready"):**
```json
{
  "collection": {
    "status": "ready",
    "warehouse_name": "Accra Main Hub",
    "warehouse_address": "Circle, Accra",
    "warehouse_phone": "+233501234567",
    "ready_at": "2026-03-22T14:00:00+00:00",
    "collected_at": null,
    "collected_by_name": null
  }
}
```

**After collection (status = "collected"):**
```json
{
  "collection": {
    "status": "collected",
    "warehouse_name": "Accra Main Hub",
    "warehouse_address": "Circle, Accra",
    "warehouse_phone": "+233501234567",
    "ready_at": "2026-03-22T14:00:00+00:00",
    "collected_at": "2026-03-22T16:30:00+00:00",
    "collected_by_name": "Jane Smith"
  }
}
```

**When `fulfillment_type` is `"warehouse"` or `"direct"`:**
```json
{
  "collection": null
}
```

**When `fulfillment_type` is `"self_pickup"` but shipment hasn't reached warehouse yet:**
```json
{
  "collection": null
}
```

### Vendor App UX Recommendations

- Show a delivery method picker on the create shipment screen (3 options: Deliver, Self-Pickup, Direct)
- For `self_pickup` shipments: show collection status card when `collection` is not null
  - `"ready"`: Show warehouse name, address, phone — "Ready for collection"
  - `"collected"`: Show collected timestamp and name — "Collected"
- For `direct` shipments: show a "Direct Delivery" badge — the driver will pick up and deliver directly

---

## Driver App Changes

### 1. Pickup List/Show — `GET /api/v1/driver/pickups` and `GET /api/v1/driver/pickups/{id}`

**Two new fields added to each pickup object:**

```json
{
  "id": 321,
  "shipment_id": 123,
  "shipment_number": "PCM-2026-00001",
  "status": "assigned",
  "is_direct_delivery": false,          // NEW — boolean
  "direct_delivery": null,              // NEW — null or object
  "cancellation_reason": null,
  "notes": null,
  "pickup_latitude": 5.6037,
  "pickup_longitude": -0.1870,
  "timeline": { ... },
  "target_warehouse": { ... },
  "shipment": { ... },
  "created_at": "...",
  "updated_at": "..."
}
```

### 2. When `is_direct_delivery` is `false` (Standard + Self-Pickup)

No change to the driver's flow. After pickup, drive to the target warehouse as usual.

```json
{
  "is_direct_delivery": false,
  "direct_delivery": null
}
```

### 3. When `is_direct_delivery` is `true` (Direct Delivery)

The driver should **NOT** go to the warehouse after pickup. Instead, deliver directly to the recipient.

```json
{
  "is_direct_delivery": true,
  "direct_delivery": {
    "recipient_name": "Mr. Kwame",
    "recipient_phone": "+233987654321",
    "location": {
      "region": "Greater Accra",
      "district": "Ablekuma West Municipal",
      "town": "Odorkor",
      "latitude": 5.5780,
      "longitude": -0.2650,
      "gh_post_address": null,
      "landmark": "Near the traffic light"
    },
    "instructions": "Call before arriving"
  }
}
```

### 4. Confirm Pickup for Direct Delivery — `POST /api/v1/driver/pickups/{id}/confirm-pickup`

When the driver confirms pickup on a direct delivery shipment, the response includes a new `auto_delivery` field:

**Request**: Same as before (no changes).

**Response** (new field added):
```json
{
  "success": true,
  "message": "Pickup confirmed. Proceed to delivery.",
  "data": {
    "assignment": {
      "id": 321,
      "status": "completed",
      "is_direct_delivery": true,
      "direct_delivery": { ... },
      "auto_delivery": {                      // NEW — only present for direct deliveries
        "delivery_run_id": 555,
        "run_number": "DRN-2026-0042",
        "stop_id": 777,
        "recipient_name": "Mr. Kwame",
        "recipient_phone": "+233987654321",
        "location": {
          "region": "Greater Accra",
          "district": "Ablekuma West Municipal",
          "town": "Odorkor",
          "latitude": 5.5780,
          "longitude": -0.2650,
          "gh_post_address": null,
          "landmark": "Near the traffic light"
        },
        "instructions": "Call before arriving"
      },
      ...
    }
  }
}
```

**When `is_direct_delivery` is `false`:**
```json
{
  "auto_delivery": null
}
```

The `auto_delivery` field provides the delivery run and stop IDs that the app can use to navigate to the delivery confirmation screen. The delivery run will also appear in `GET /api/v1/driver/deliveries`.

### 5. Delivery Run List/Show — `GET /api/v1/driver/deliveries` and `GET /api/v1/driver/deliveries/{run}`

**One new field added per delivery run:**

```json
{
  "id": 555,
  "run_number": "DRN-2026-0042",
  "status": "out_for_delivery",
  "is_direct_delivery": true,           // NEW — boolean
  "warehouse": { ... },
  "timeline": { ... },
  "stops": [ ... ],
  "notes": null,
  "created_at": "...",
  "updated_at": "..."
}
```

This is informational only — the delivery confirmation endpoints (`confirm`, `confirm-packages`, `fail`) work identically regardless of `is_direct_delivery`.

### Driver App UX Recommendations

- Check `is_direct_delivery` on each pickup assignment
- If `true`:
  - Show delivery address alongside pickup address (e.g. "Pickup from Darkuman → Deliver to Odorkor")
  - After confirming pickup, use `auto_delivery.delivery_run_id` and `auto_delivery.stop_id` to navigate directly to the delivery confirmation screen
  - Do NOT show "Drive to warehouse" step
- If `false`:
  - Existing flow — no changes needed

---

## Backwards Compatibility

| Scenario | Impact |
|----------|--------|
| Vendor app ignores `fulfillment_type` field | Shipments default to `"warehouse"` — existing behavior |
| Vendor app ignores `collection` field | No impact — field is `null` for standard shipments |
| Driver app ignores `is_direct_delivery` field | All pickups treated as standard — driver goes to warehouse |
| Driver app ignores `direct_delivery` field | No impact — field is `null` for standard pickups |
| Driver app ignores `auto_delivery` field | Delivery run still appears in GET /deliveries — just won't auto-navigate |

**No breaking changes.** All new fields are additive. Apps that don't handle them will continue to work with standard warehouse flow.

---

## Notifications (Backend-Triggered)

These are sent automatically by the backend. The mobile app does NOT need to trigger them.

| Event | Channel | Recipient | Message |
|-------|---------|-----------|---------|
| Self-pickup shipment arrives at warehouse | SMS | Delivery recipient | "Your package {tracking} is ready for collection at {warehouse_name}, {warehouse_address}." |
| Self-pickup shipment arrives at warehouse | Push | Vendor | "Shipment {number} ready for collection at {warehouse}" |
| Collection completed | SMS | Delivery recipient | "Your package {tracking} has been collected. Thank you." |
| Collection completed | Push | Vendor | "Shipment {number} collected by recipient" |

---

## Field Reference

### New fields in Shipment object (Vendor API):
| Field | Type | When present |
|-------|------|-------------|
| `fulfillment_type` | `string` | Always — `"warehouse"`, `"self_pickup"`, or `"direct"` |
| `collection` | `object\|null` | Only when `fulfillment_type = "self_pickup"` AND shipment is at warehouse |

### New fields in Pickup object (Driver API):
| Field | Type | When present |
|-------|------|-------------|
| `is_direct_delivery` | `boolean` | Always — `true` or `false` |
| `direct_delivery` | `object\|null` | Only when `is_direct_delivery = true` |

### New field in Confirm Pickup response (Driver API):
| Field | Type | When present |
|-------|------|-------------|
| `auto_delivery` | `object\|null` | Only when confirming pickup on a direct delivery shipment |

### New field in Delivery Run object (Driver API):
| Field | Type | When present |
|-------|------|-------------|
| `is_direct_delivery` | `boolean` | Always — `true` or `false` |

---

## Full API Tester Example Responses

### Example 1: Vendor creates a self-pickup shipment

**Request:** `POST /api/v1/vendor/shipments`
```json
{
  "destination_mode": "single",
  "fulfillment_type": "self_pickup",
  "pickup_contact_name": "Kwame Mensah",
  "pickup_contact_phone": "0241234567",
  "pickup_contact_phone_confirm": "0241234567",
  "pickup_region_id": 1,
  "pickup_district_id": 5,
  "pickup_town": "Darkuman",
  "delivery_recipient_name": "Ama Serwaa",
  "delivery_recipient_phone": "0551234567",
  "delivery_recipient_phone_confirm": "0551234567",
  "delivery_region_id": 1,
  "delivery_district_id": 8,
  "delivery_town": "Madina"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Shipment created successfully.",
  "data": {
    "shipment": {
      "id": 47,
      "shipment_number": "PCM-2026-00047",
      "status": "draft",
      "fulfillment_type": "self_pickup",
      "destination_mode": "single",
      "pickup": {
        "contact_name": "Kwame Mensah",
        "contact_phone": "+233241234567",
        "location": {
          "type": "dropdown",
          "region": "Greater Accra",
          "region_id": 1,
          "district": "Ablekuma North Municipal",
          "district_id": 5,
          "town": "Darkuman",
          "latitude": null,
          "longitude": null,
          "gh_post_address": null,
          "landmark": null
        },
        "instructions": null
      },
      "delivery": {
        "recipient_name": "Ama Serwaa",
        "recipient_phone": "+233551234567",
        "location": {
          "type": "dropdown",
          "region": "Greater Accra",
          "region_id": 1,
          "district": "La Nkwantanang-Madina Municipal",
          "district_id": 8,
          "town": "Madina",
          "latitude": null,
          "longitude": null,
          "gh_post_address": null,
          "landmark": null
        },
        "instructions": null
      },
      "collection": null,
      "items": [],
      "can_edit": true,
      "can_delete": true,
      "can_submit": false,
      "submitted_at": null,
      "created_at": "2026-03-22T10:00:00+00:00",
      "updated_at": "2026-03-22T10:00:00+00:00",
      "invoice": null,
      "invoice_history": [],
      "pickup_assignment": null
    }
  }
}
```

---

### Example 2: Vendor views self-pickup shipment that is ready for collection

**Request:** `GET /api/v1/vendor/shipments/47`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Shipment retrieved successfully.",
  "data": {
    "shipment": {
      "id": 47,
      "shipment_number": "PCM-2026-00047",
      "status": "sorted",
      "fulfillment_type": "self_pickup",
      "destination_mode": "single",
      "pickup": {
        "contact_name": "Kwame Mensah",
        "contact_phone": "+233241234567",
        "location": {
          "type": "dropdown",
          "region": "Greater Accra",
          "region_id": 1,
          "district": "Ablekuma North Municipal",
          "district_id": 5,
          "town": "Darkuman",
          "latitude": null,
          "longitude": null,
          "gh_post_address": null,
          "landmark": null
        },
        "instructions": null
      },
      "delivery": {
        "recipient_name": "Ama Serwaa",
        "recipient_phone": "+233551234567",
        "location": {
          "type": "dropdown",
          "region": "Greater Accra",
          "region_id": 1,
          "district": "La Nkwantanang-Madina Municipal",
          "district_id": 8,
          "town": "Madina",
          "latitude": null,
          "longitude": null,
          "gh_post_address": null,
          "landmark": null
        },
        "instructions": null
      },
      "collection": {
        "status": "ready",
        "warehouse_name": "Accra Main Hub",
        "warehouse_address": "Circle, Accra",
        "warehouse_phone": "+233501234567",
        "ready_at": "2026-03-22T14:30:00+00:00",
        "collected_at": null,
        "collected_by_name": null
      },
      "items": [
        {
          "id": 89,
          "description": "Box of phone accessories",
          "quantity": 3,
          "status": "sorted",
          "tracking_code": "TRKABCD1234",
          "delivery": null,
          "images": [],
          "pickup_confirmation": {
            "expected_quantity": 3,
            "confirmed_quantity": 3,
            "missing_quantity": 0,
            "extra_quantity": 0,
            "is_exact_match": true,
            "notes": null,
            "confirmed_at": "2026-03-22T12:00:00+00:00",
            "photos": []
          },
          "created_at": "2026-03-22T10:05:00+00:00",
          "updated_at": "2026-03-22T14:30:00+00:00"
        }
      ],
      "can_edit": false,
      "can_delete": false,
      "can_submit": false,
      "submitted_at": "2026-03-22T10:30:00+00:00",
      "created_at": "2026-03-22T10:00:00+00:00",
      "updated_at": "2026-03-22T14:30:00+00:00",
      "invoice": null,
      "invoice_history": [],
      "pickup_assignment": {
        "id": 33,
        "status": "completed",
        "status_label": "Completed",
        "driver_name": "Ahmed Hassan",
        "driver_phone": "+233555123456",
        "driver": {
          "id": 12,
          "name": "Ahmed Hassan",
          "phone": "+233555123456",
          "vehicle_type": "Motorcycle",
          "vehicle_number": "M-1234-22"
        },
        "timeline": {
          "assigned": { "at": "2026-03-22T11:00:00+00:00" },
          "en_route": { "at": "2026-03-22T11:15:00+00:00" },
          "arrived_pickup": { "at": "2026-03-22T11:45:00+00:00", "latitude": 5.5900, "longitude": -0.2400 },
          "picked_up": { "at": "2026-03-22T12:00:00+00:00" },
          "arrived_warehouse": { "at": "2026-03-22T13:00:00+00:00", "warehouse": { "id": 1, "name": "Accra Main Hub", "code": "WH-001" } },
          "received": { "at": "2026-03-22T14:00:00+00:00", "warehouse": { "id": 1, "name": "Accra Main Hub", "code": "WH-001" }, "received_by_user_id": 6, "notes": null },
          "completed": { "at": "2026-03-22T12:00:00+00:00" },
          "cancelled": { "at": null, "reason": null }
        },
        "target_warehouse": { "id": 1, "name": "Accra Main Hub", "code": "WH-001" },
        "notes": null
      }
    }
  }
}
```

---

### Example 3: Driver sees a standard pickup (is_direct_delivery = false)

**Request:** `GET /api/v1/driver/pickups/33`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Pickup retrieved successfully.",
  "data": {
    "pickup": {
      "id": 33,
      "shipment_id": 47,
      "shipment_number": "PCM-2026-00047",
      "status": "assigned",
      "is_direct_delivery": false,
      "direct_delivery": null,
      "cancellation_reason": null,
      "notes": null,
      "pickup_latitude": null,
      "pickup_longitude": null,
      "timeline": {
        "assigned": { "at": "2026-03-22T11:00:00+00:00" },
        "en_route": { "at": null },
        "arrived_pickup": { "at": null, "latitude": null, "longitude": null },
        "picked_up": { "at": null },
        "arrived_warehouse": { "at": null, "warehouse": null },
        "received": { "at": null, "warehouse": null, "received_by_user_id": null, "notes": null },
        "completed": { "at": null },
        "cancelled": { "at": null, "reason": null }
      },
      "target_warehouse": { "id": 1, "name": "Accra Main Hub", "code": "WH-001" },
      "shipment": {
        "id": 47,
        "shipment_number": "PCM-2026-00047",
        "status": "pickup_assigned",
        "vendor_name": "Kwame's Electronics",
        "pickup": {
          "contact_name": "Kwame Mensah",
          "contact_phone": "+233241234567",
          "location": {
            "region": "Greater Accra",
            "district": "Ablekuma North Municipal",
            "town": "Darkuman",
            "latitude": null,
            "longitude": null,
            "gh_post_address": null,
            "landmark": null
          },
          "instructions": null
        },
        "items": [
          {
            "id": 89,
            "description": "Box of phone accessories",
            "quantity": 3,
            "status": "pending",
            "tracking_code": null,
            "images": [],
            "pickup_confirmation": null,
            "created_at": "2026-03-22T10:05:00+00:00",
            "updated_at": "2026-03-22T10:05:00+00:00"
          }
        ],
        "submitted_at": "2026-03-22T10:30:00+00:00",
        "created_at": "2026-03-22T10:00:00+00:00",
        "updated_at": "2026-03-22T11:00:00+00:00"
      },
      "created_at": "2026-03-22T11:00:00+00:00",
      "updated_at": "2026-03-22T11:00:00+00:00"
    }
  }
}
```

---

### Example 4: Driver sees a direct delivery pickup (is_direct_delivery = true)

**Request:** `GET /api/v1/driver/pickups/40`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Pickup retrieved successfully.",
  "data": {
    "pickup": {
      "id": 40,
      "shipment_id": 52,
      "shipment_number": "PCM-2026-00052",
      "status": "assigned",
      "is_direct_delivery": true,
      "direct_delivery": {
        "recipient_name": "Mr. Kwame Asante",
        "recipient_phone": "+233987654321",
        "location": {
          "region": "Greater Accra",
          "district": "Ablekuma West Municipal",
          "town": "Odorkor",
          "latitude": 5.5780,
          "longitude": -0.2650,
          "gh_post_address": null,
          "landmark": "Near the traffic light"
        },
        "instructions": "Call before arriving"
      },
      "cancellation_reason": null,
      "notes": null,
      "pickup_latitude": null,
      "pickup_longitude": null,
      "timeline": {
        "assigned": { "at": "2026-03-22T09:00:00+00:00" },
        "en_route": { "at": null },
        "arrived_pickup": { "at": null, "latitude": null, "longitude": null },
        "picked_up": { "at": null },
        "arrived_warehouse": { "at": null, "warehouse": null },
        "received": { "at": null, "warehouse": null, "received_by_user_id": null, "notes": null },
        "completed": { "at": null },
        "cancelled": { "at": null, "reason": null }
      },
      "target_warehouse": { "id": 1, "name": "Accra Main Hub", "code": "WH-001" },
      "shipment": {
        "id": 52,
        "shipment_number": "PCM-2026-00052",
        "status": "pickup_assigned",
        "vendor_name": "Quick Send Logistics",
        "pickup": {
          "contact_name": "Yaw Boateng",
          "contact_phone": "+233201234567",
          "location": {
            "region": "Greater Accra",
            "district": "Okaikwei North Municipal",
            "town": "Darkuman",
            "latitude": null,
            "longitude": null,
            "gh_post_address": null,
            "landmark": "Behind the school"
          },
          "instructions": "Ring the bell twice"
        },
        "items": [
          {
            "id": 95,
            "description": "Laptop bag",
            "quantity": 1,
            "status": "pending",
            "tracking_code": null,
            "images": [],
            "pickup_confirmation": null,
            "created_at": "2026-03-22T08:30:00+00:00",
            "updated_at": "2026-03-22T08:30:00+00:00"
          }
        ],
        "submitted_at": "2026-03-22T08:45:00+00:00",
        "created_at": "2026-03-22T08:30:00+00:00",
        "updated_at": "2026-03-22T09:00:00+00:00"
      },
      "created_at": "2026-03-22T09:00:00+00:00",
      "updated_at": "2026-03-22T09:00:00+00:00"
    }
  }
}
```

---

### Example 5: Driver confirms pickup on direct delivery — gets auto_delivery

**Request:** `POST /api/v1/driver/pickups/40/confirm-pickup`
```json
{
  "latitude": 5.5900,
  "longitude": -0.2400,
  "notes": "All items collected"
}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Pickup confirmed. Proceed to delivery.",
  "data": {
    "assignment": {
      "id": 40,
      "shipment_id": 52,
      "shipment_number": "PCM-2026-00052",
      "status": "completed",
      "is_direct_delivery": true,
      "direct_delivery": {
        "recipient_name": "Mr. Kwame Asante",
        "recipient_phone": "+233987654321",
        "location": {
          "region": "Greater Accra",
          "district": "Ablekuma West Municipal",
          "town": "Odorkor",
          "latitude": 5.5780,
          "longitude": -0.2650,
          "gh_post_address": null,
          "landmark": "Near the traffic light"
        },
        "instructions": "Call before arriving"
      },
      "auto_delivery": {
        "delivery_run_id": 88,
        "run_number": "DR-2026-WH001-0015",
        "stop_id": 201,
        "recipient_name": "Mr. Kwame Asante",
        "recipient_phone": "+233987654321",
        "location": {
          "region": "Greater Accra",
          "district": "Ablekuma West Municipal",
          "town": "Odorkor",
          "latitude": 5.5780,
          "longitude": -0.2650,
          "gh_post_address": null,
          "landmark": "Near the traffic light"
        },
        "instructions": "Call before arriving"
      },
      "cancellation_reason": null,
      "notes": "All items collected",
      "pickup_latitude": 5.5900,
      "pickup_longitude": -0.2400,
      "timeline": {
        "assigned": { "at": "2026-03-22T09:00:00+00:00" },
        "en_route": { "at": "2026-03-22T09:15:00+00:00" },
        "arrived_pickup": { "at": "2026-03-22T09:45:00+00:00", "latitude": 5.5900, "longitude": -0.2400 },
        "picked_up": { "at": "2026-03-22T10:00:00+00:00" },
        "arrived_warehouse": { "at": null, "warehouse": null },
        "received": { "at": null, "warehouse": null, "received_by_user_id": null, "notes": null },
        "completed": { "at": "2026-03-22T10:00:00+00:00" },
        "cancelled": { "at": null, "reason": null }
      },
      "target_warehouse": { "id": 1, "name": "Accra Main Hub", "code": "WH-001" },
      "shipment": {
        "id": 52,
        "shipment_number": "PCM-2026-00052",
        "status": "out_for_delivery",
        "vendor_name": "Quick Send Logistics",
        "pickup": { "..." : "..." },
        "items": [
          {
            "id": 95,
            "description": "Laptop bag",
            "quantity": 1,
            "status": "out_for_delivery",
            "tracking_code": "TRKXY9M2K4P",
            "images": [],
            "pickup_confirmation": {
              "expected_quantity": 1,
              "confirmed_quantity": 1,
              "missing_quantity": 0,
              "extra_quantity": 0,
              "is_exact_match": true,
              "notes": null,
              "confirmed_at": "2026-03-22T09:55:00+00:00",
              "photos": []
            },
            "created_at": "2026-03-22T08:30:00+00:00",
            "updated_at": "2026-03-22T10:00:00+00:00"
          }
        ],
        "submitted_at": "2026-03-22T08:45:00+00:00",
        "created_at": "2026-03-22T08:30:00+00:00",
        "updated_at": "2026-03-22T10:00:00+00:00"
      },
      "created_at": "2026-03-22T09:00:00+00:00",
      "updated_at": "2026-03-22T10:00:00+00:00"
    }
  }
}
```

---

### Example 6: Driver views delivery run (direct delivery)

**Request:** `GET /api/v1/driver/deliveries/88`

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Delivery run retrieved successfully.",
  "data": {
    "delivery": {
      "id": 88,
      "run_number": "DR-2026-WH001-0015",
      "status": "out_for_delivery",
      "is_direct_delivery": true,
      "warehouse": {
        "id": 1,
        "name": "Accra Main Hub",
        "code": "WH-001",
        "address": "Circle, Accra",
        "latitude": 5.5550,
        "longitude": -0.2050,
        "contact_phone": "+233501234567"
      },
      "timeline": {
        "assigned": { "at": "2026-03-22T10:00:00+00:00" },
        "out_for_delivery": { "at": "2026-03-22T10:00:00+00:00" },
        "completed": { "at": null }
      },
      "stops": [
        {
          "id": 201,
          "recipient_name": "Mr. Kwame Asante",
          "recipient_phone": "+233987654321",
          "status": "pending",
          "total_packages": 0,
          "location": {
            "region": "Greater Accra",
            "district": "Ablekuma West Municipal",
            "town": "Odorkor",
            "latitude": 5.5780,
            "longitude": -0.2650,
            "gh_post_address": null,
            "landmark": "Near the traffic light"
          },
          "verification": {
            "code_sent_at": null,
            "code_expires_at": null,
            "attempts": 0,
            "max_attempts": 3
          },
          "timeline": {
            "arrived": { "at": null },
            "delivered": { "at": null }
          },
          "failure_reason": null,
          "failure_notes": null,
          "delivery_notes": null,
          "items": [
            {
              "shipment_item_id": 95,
              "shipment_number": "PCM-2026-00052",
              "description": "Laptop bag",
              "tracking_code": "TRKXY9M2K4P",
              "expected_quantity": 1,
              "delivered_quantity": 0,
              "status": "pending",
              "notes": null,
              "delivered_at": null
            }
          ]
        }
      ],
      "notes": "Auto-created: direct delivery from pickup.",
      "created_at": "2026-03-22T10:00:00+00:00",
      "updated_at": "2026-03-22T10:00:00+00:00"
    }
  }
}
```

---

### Example 7: Driver views delivery run (standard — not direct)

**Request:** `GET /api/v1/driver/deliveries/75`

**Response:** `200 OK` (only showing the new field difference)
```json
{
  "success": true,
  "message": "Delivery run retrieved successfully.",
  "data": {
    "delivery": {
      "id": 75,
      "run_number": "DR-2026-WH001-0010",
      "status": "out_for_delivery",
      "is_direct_delivery": false,
      "warehouse": { "..." : "..." },
      "timeline": { "..." : "..." },
      "stops": [ "..." ],
      "notes": null,
      "created_at": "2026-03-22T08:00:00+00:00",
      "updated_at": "2026-03-22T09:00:00+00:00"
    }
  }
}
```

---

### Example 8: Vendor views collected shipment

**Request:** `GET /api/v1/vendor/shipments/47`

**Response:** `200 OK` (showing collection block after handover)
```json
{
  "success": true,
  "message": "Shipment retrieved successfully.",
  "data": {
    "shipment": {
      "id": 47,
      "shipment_number": "PCM-2026-00047",
      "status": "delivered",
      "fulfillment_type": "self_pickup",
      "destination_mode": "single",
      "pickup": { "..." : "..." },
      "delivery": { "..." : "..." },
      "collection": {
        "status": "collected",
        "warehouse_name": "Accra Main Hub",
        "warehouse_address": "Circle, Accra",
        "warehouse_phone": "+233501234567",
        "ready_at": "2026-03-22T14:30:00+00:00",
        "collected_at": "2026-03-22T16:45:00+00:00",
        "collected_by_name": "Ama Serwaa"
      },
      "items": [ "..." ],
      "can_edit": false,
      "can_delete": false,
      "can_submit": false,
      "submitted_at": "2026-03-22T10:30:00+00:00",
      "created_at": "2026-03-22T10:00:00+00:00",
      "updated_at": "2026-03-22T16:45:00+00:00",
      "invoice": null,
      "invoice_history": [],
      "pickup_assignment": { "..." : "..." }
    }
  }
}
```
