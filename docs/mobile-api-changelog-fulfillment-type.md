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

---

# Mobile API Changelog — Photo Recipient Phone Tagging

**Date**: 2026-04-16
**Version**: Photo Tagging v1
**Affects**: Vendor App only
**Backwards Compatible**: Yes — `phones[]` is optional, existing behavior unchanged if omitted.

---

## Summary

Vendors can now **tag each photo with a recipient phone number** when creating a shipment. This helps the admin group photos by recipient when processing the shipment later.

- Each photo gets an optional phone number (not required)
- Photos with the same phone are later grouped into the same delivery item by admin
- Photos with no phone fall back to the shipment-level delivery recipient

---

## Vendor App Changes

### 1. Create Shipment — `POST /api/v1/vendor/shipments`

**New optional field per item: `phones[]`**

The `phones` array corresponds 1:1 with the `images` array (same index = same photo). If a phone is empty or missing for an index, that photo has no tagged recipient.

**Request (multipart/form-data):**
```
destination_mode = single
pickup_contact_name = John Doe
pickup_contact_phone = 0241234567
items[0][images][0] = <photo_file_1>
items[0][images][1] = <photo_file_2>
items[0][images][2] = <photo_file_3>
items[0][phones][0] = 0551234567       // Photo 1 → recipient phone A
items[0][phones][1] = 0551234567       // Photo 2 → same recipient
items[0][phones][2] = 0209876543       // Photo 3 → different recipient
```

**Rules:**
- `items[0][phones][]` — optional, array of strings, max 20 chars each
- Phones are auto-formatted by the backend (Ghana phone format)
- If `phones` array is shorter than `images` array, remaining photos get `null`
- If `phones` array is omitted entirely, all photos get `null`

### 2. Image Object in Responses — All Endpoints

**New field: `recipient_phone`**

Every image object in API responses now includes `recipient_phone`:

```json
{
  "id": 1,
  "url": "https://storage.example.com/shipments/1/items/1/photo.jpg?...",
  "original_name": "fridge-front.jpg",
  "size": 245760,
  "size_human": "240.00 KB",
  "recipient_phone": "+233551234567",    // NEW — null if not tagged
  "expires_at": "2026-04-16T12:00:00Z"
}
```

| Value | Meaning |
|-------|---------|
| `"+233551234567"` | Photo tagged with this recipient's phone |
| `null` | No recipient tagged — uses shipment-level delivery |

This field appears in:
- `GET /api/v1/vendor/shipments` (list)
- `GET /api/v1/vendor/shipments/{id}` (show)
- `POST /api/v1/vendor/shipments` (create response)
- `PUT /api/v1/vendor/shipments/{id}` (update response)
- `POST /api/v1/vendor/shipments/{id}/items/{itemId}/images` (upload response)

---

## Vendor App UX Recommendations

### Photo Grid — Tagging Flow

1. **After taking photos**, show all photos in the existing grid
2. **Add a "Tag Recipients" button** (or use the existing select mode pattern)
3. User selects one or more photos → enters a phone number → all selected photos get tagged
4. Tagged photos show a **phone badge overlay** (e.g. small phone icon + last 4 digits)
5. User can tap a tagged photo to change or remove the phone
6. Untagged photos are fine — they go to the default shipment recipient

### Visual Indicators

```
┌──────────┐  ┌──────────┐  ┌──────────┐
│          │  │          │  │          │
│  Photo 1 │  │  Photo 2 │  │  Photo 3 │
│          │  │          │  │          │
│ 📱 4567  │  │ 📱 4567  │  │          │  ← No tag
└──────────┘  └──────────┘  └──────────┘
  Recipient A   Recipient A   No phone
```

### Batch Tagging (Recommended)

Since vendors often have many photos for the same recipient:
1. Long-press to enter select mode (existing pattern)
2. Select all photos for one recipient
3. Tap "Assign Phone" → enter phone → done
4. Repeat for next batch

---

## Backwards Compatibility

| Scenario | Impact |
|----------|--------|
| App doesn't send `phones[]` | All photos get `recipient_phone: null` — existing behavior |
| App ignores `recipient_phone` in response | No impact — field is just `null` for old photos |

**No breaking changes.** The field is fully additive.

---

## Database Change

New column on `shipment_item_images` table:
- `recipient_phone` — `VARCHAR(20)`, nullable

This is a tagging field only. The actual delivery phone for processed items remains on `shipment_items.delivery_recipient_phone`.

---

# Mobile API Changelog — OTP Verification Settings

**Date**: 2026-04-16
**Version**: OTP Settings v1
**Affects**: Driver App, Vendor App
**Backwards Compatible**: Yes — new fields are additive with safe defaults.

---

## Summary

Two new admin-configurable settings control OTP verification behavior:

| Setting | Default | Effect |
|---------|---------|--------|
| `delivery.allow_skip_verification` | OFF | Controls whether drivers can skip OTP with a reason |
| `delivery.show_otp_to_vendor` | OFF | Controls whether vendors can see the OTP code in their shipment details |

Both are toggle switches on the admin settings page under **Delivery Settings**.

---

## Driver App Changes

### Delivery Run Response — New Field

```json
{
  "id": 555,
  "run_number": "DRN-2026-0042",
  "allow_skip_verification": false,
  ...
}
```

| Value | Driver UX |
|-------|-----------|
| `false` | Hide skip verification toggle. Show OTP input only + "Call warehouse" banner |
| `true` | Show skip verification toggle (existing behavior) |

### "Call Warehouse" Banner (when `allow_skip_verification = false`)

When the driver can't skip verification, show a help banner below the OTP input:

```
┌──────────────────────────────────────────┐
│ 📞  Can't get the code?                 │
│     Call the warehouse to get the        │
│     verification code              [📱]  │
└──────────────────────────────────────────┘
```

- Phone number: use `delivery.warehouse.contact_phone` from the run response
- If no warehouse phone: show text only ("Call admin to get the verification code"), no call button
- Tap the call button → `Linking.openURL(tel:${phone})`

---

## Vendor App Changes

### Shipment Response — New Field

```json
{
  "id": 123,
  "shipment_number": "PCM-2026-00001",
  "status": "out_for_delivery",
  "delivery_verification": {
    "code": "4821",
    "expires_at": "2026-04-17T14:00:00+00:00",
    "sent_at": "2026-04-16T14:00:00+00:00"
  },
  ...
}
```

| Condition | Value |
|-----------|-------|
| Setting OFF | `"delivery_verification": null` |
| Setting ON + shipment not in delivery | `"delivery_verification": null` |
| Setting ON + shipment in delivery + active OTP | `{ "code": "4821", "expires_at": "...", "sent_at": "..." }` |
| Setting ON + OTP already verified or expired | `"delivery_verification": null` |

### Vendor UX Recommendation

When `delivery_verification` is not null, show a card on the shipment detail screen:

```
┌──────────────────────────────────────────┐
│ 🔑  Delivery Verification Code          │
│                                          │
│          4 8 2 1                         │
│                                          │
│  Share this code with the recipient      │
│  if they didn't receive the SMS.         │
│  Expires: Apr 17, 2026 2:00 PM          │
└──────────────────────────────────────────┘
```

---

## Warehouse Admin Changes

### Delivery Run Show Page

Warehouse staff can now see the **active OTP code** for each delivery stop directly on the delivery run detail page. Displayed as an `OTP: XXXX` badge next to the "Code sent" timestamp.

Only shown when:
- Stop has a code sent
- Stop is NOT yet delivered
- OTP has not expired

This allows warehouse staff to read the code to drivers who call in.

---

## Backwards Compatibility

| Scenario | Impact |
|----------|--------|
| Driver app ignores `allow_skip_verification` | Skip toggle hidden by default — safe |
| Vendor app ignores `delivery_verification` | Field is `null` by default — safe |
| Admin hasn't configured settings | Both default to OFF — existing behavior |

---

# Mobile API Changelog — Bus Courier Handoff

**Date**: 2026-04-16
**Version**: Bus Handoff v1
**Affects**: Driver App
**Backwards Compatible**: Yes — `delivery_method` defaults to `"direct"`, all existing stops unaffected.

---

## Summary

For out-of-town deliveries, drivers can hand packages to a bus station courier instead of delivering directly. The admin confirms delivery via phone call to the recipient the next day.

**Status flow**: `OUT_FOR_DELIVERY → HANDED_TO_COURIER → DELIVERED (admin confirms)`

---

## Driver App Changes

### Claimed Package Responses — Route Intent

The driver package-custody endpoints now expose the package routing intent before a delivery run is created:

- `POST /api/v1/driver/scan-claim`
- `GET /api/v1/driver/my-packages`
- `GET /api/v1/driver/package-history/{barcode}`

```json
{
  "barcode": "TRKRNDOBJCW-001",
  "description": "Laptop",
  "delivery_method": "bus_handoff",
  "route_label": "Bus Station",
  "recipient_name": "Ama Mensah",
  "delivery_town": "Madina"
}
```

| Field | Type | Values |
|-------|------|--------|
| `delivery_method` | string | `"direct"` or `"bus_handoff"` |
| `route_label` | string | `"Bus Station"` for bus handoff, otherwise the recipient label |

Purpose:
- the My Packages screen can show `Bus Station -> Town` before a run exists
- the actual station name is still captured only during `confirm-handoff`

### Delivery Run Stop Response — New Fields

```json
{
  "id": 9,
  "delivery_method": "bus_handoff",
  "recipient_name": "Ama Mensah",
  "recipient_phone": "+233241234567",
  "status": "pending",
  "handoff": null,
  ...
}
```

| Field | Type | Values |
|-------|------|--------|
| `delivery_method` | string | `"direct"` (default) or `"bus_handoff"` |
| `handoff` | object\|null | Populated after handoff confirmed |

### After Handoff

```json
{
  "delivery_method": "bus_handoff",
  "status": "handed_off",
  "handoff": {
    "courier_name": "Gucci",
    "courier_phone": "+233549771816",
    "vehicle_number": "GN 1439-13",
    "handed_off_at": "2026-04-16T14:30:00+00:00"
  }
}
```

### New Stop Status: `handed_off`

Added alongside existing `pending`, `arrived`, `delivered`, `failed`.

### New Endpoint: Confirm Handoff

**`POST /api/v1/driver/deliveries/{run}/stops/{stop}/confirm-handoff`** (multipart/form-data)

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `courier_name` | string (max 255) | Yes | Bus driver's name |
| `courier_phone` | string (max 20) | Yes | Bus driver's phone |
| `vehicle_number` | string (max 50) | Yes | Vehicle registration plate |
| `latitude` | numeric | Yes | GPS at handoff location |
| `longitude` | numeric | Yes | GPS at handoff location |
| `proof_photo` | file (image, max 12MB) | Yes | Photo of handoff/slip |

**No OTP, no packages count, no skip verification.** Just courier details + photo.

### Arrive Behavior

When `delivery_method = "bus_handoff"`:
- `POST .../arrive` still works
- **No OTP SMS is sent** (no recipient to verify at the bus station)
- Message: "Arrival recorded. Ready for bus courier handoff."

### Driver App UX

When `stop.delivery_method === "bus_handoff"`:

1. **Info tab**: same as normal — show recipient details, location, packages
2. **Confirm tab**: show **BUS COURIER HANDOFF** form (violet theme):
   - Courier Name (text input)
   - Courier Phone (phone input)
   - Vehicle Number (text input, auto-uppercase)
   - Proof Photo (camera/gallery picker)
   - **No** OTP input, packages stepper, or skip verification
3. **Action button**: "Confirm Handoff" (violet gradient) instead of "Confirm Delivery"
4. **Fail tab**: same as normal — can still mark as failed

---

## New Shipment Status: `handed_to_courier`

Added to the ShipmentStatus enum. Sits between `out_for_delivery` and `delivered`.

| Status | Label | Meaning |
|--------|-------|---------|
| `handed_to_courier` | Handed to Courier | Packages given to bus courier, awaiting admin confirmation |

---

## Backwards Compatibility

| Scenario | Impact |
|----------|--------|
| Driver app ignores `delivery_method` | All stops default to `"direct"` — existing behavior |
| Driver app ignores `handoff` field | Field is `null` for non-handoff stops — safe |
| Stop with `delivery_method = "direct"` | No change — use `confirm-packages` as before |
| Old app hits `confirm-handoff` on a direct stop | Returns 400: "This stop is not a bus handoff stop" |

---

# Mobile API Changelog — Vendor Commission / Earnings

**Date**: 2026-04-17
**Version**: Vendor Earnings v1
**Affects**: Vendor App only
**Backwards Compatible**: Yes — new endpoints, no changes to existing ones.

---

## Summary

Vendors earn a commission (GHS) for each package successfully delivered. Earnings accumulate and admin can process payouts (MoMo transfer). The rate is configured by admin.

---

## New Vendor API Endpoints

### 1. Earnings Summary — `GET /api/v1/vendor/earnings/summary`

Returns the vendor's balance and payout eligibility.

```json
{
  "success": true,
  "data": {
    "total_earned": 124.00,
    "available_balance": 44.00,
    "total_paid": 80.00,
    "pending_payout": 0.00,
    "min_payout": 20.00,
    "can_request_payout": true,
    "currency": "GHS"
  }
}
```

| Field | Meaning |
|-------|---------|
| `total_earned` | Lifetime earnings |
| `available_balance` | Approved earnings not yet in a payout |
| `total_paid` | Sum of sent + confirmed payouts |
| `pending_payout` | Payouts created but not yet sent |
| `min_payout` | Minimum balance required for payout (admin-configured) |
| `can_request_payout` | `true` if `available_balance >= min_payout` |

### 2. Earnings List — `GET /api/v1/vendor/earnings`

Paginated list of per-package earnings.

**Query params**: `limit` (default 20, max 100), `offset` (default 0), `status` (optional: `approved` or `paid`)

```json
{
  "success": true,
  "data": {
    "earnings": [
      {
        "id": 1,
        "shipment_number": "PCM-2026-00045",
        "amount": 2.00,
        "status": "approved",
        "created_at": "2026-04-16T14:00:00+00:00"
      }
    ],
    "pagination": {
      "offset": 0,
      "limit": 20,
      "total": 62,
      "has_more": true,
      "next_offset": 20,
      "current_page": 1,
      "last_page": 4,
      "per_page": 20
    }
  }
}
```

### 3. Payouts List — `GET /api/v1/vendor/payouts`

Paginated list of payout history.

**Query params**: `limit` (default 20, max 100), `offset` (default 0), `status` (optional: `pending`, `sent`, `confirmed`)

```json
{
  "success": true,
  "data": {
    "payouts": [
      {
        "id": 1,
        "amount": 40.00,
        "status": "sent",
        "payment_method": "momo",
        "payment_reference": "TXN123456",
        "payment_phone": "+233241234567",
        "sent_at": "2026-04-15T10:00:00+00:00",
        "confirmed_at": null,
        "created_at": "2026-04-15T09:00:00+00:00"
      }
    ],
    "pagination": { ... }
  }
}
```

---

## Vendor App UX Recommendations

### Earnings Card (Account tab or dedicated section)

```
┌──────────────────────────────────────────┐
│ 💰  Your Earnings                        │
│                                          │
│  GHS 44.00                               │
│  Available Balance                       │
│                                          │
│  Total Earned: GHS 124.00               │
│  Paid Out: GHS 80.00                    │
│                                          │
│  Min. payout: GHS 20.00                 │
└──────────────────────────────────────────┘
```

### Earnings History

List of per-package earnings with shipment number, amount, date, and status badge (approved = green, paid = blue).

### Payouts History

List of payouts with amount, method, reference, status, and dates.

---

## How Earnings Are Created (Backend)

Earnings are automatically created when:
- A delivery stop is confirmed as `delivered` (normal delivery)
- Admin confirms a bus handoff delivery via phone call

Rate per package is configured at `Admin → Settings → Vendor Commission`.

---

## TypeScript Types

```typescript
interface EarningsSummary {
  total_earned: number;
  available_balance: number;
  total_paid: number;
  pending_payout: number;
  min_payout: number;
  can_request_payout: boolean;
  currency: string;
}

interface VendorEarning {
  id: number;
  shipment_number: string;
  amount: number;
  status: 'approved' | 'paid';
  created_at: string;
}

interface VendorPayout {
  id: number;
  amount: number;
  status: 'pending' | 'sent' | 'confirmed';
  payment_method: 'momo' | 'bank' | 'cash';
  payment_reference: string | null;
  payment_phone: string | null;
  sent_at: string | null;
  confirmed_at: string | null;
  created_at: string;
}
```
