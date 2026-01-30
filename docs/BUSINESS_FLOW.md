# Parcelman Express - Business Flow

> Complete logical flow of the delivery management system

---

## Overview

Parcelman Express is a logistics/delivery management system that handles the complete journey of items from **Vendor** to **Customer** through a network of **Warehouses**.

---

## Architecture

| Interface | Users | Technology |
|-----------|-------|------------|
| **Web Admin Panel** | Super Admin | Laravel + Blade + Tailwind + Vanilla JS |
| **Web Warehouse Panel** | Warehouse Manager | Laravel + Blade + Tailwind + Vanilla JS |
| **Mobile App (Native)** | Vendors + Drivers | Native app consuming REST APIs |

---

## User Roles

| Role | Description | Interface |
|------|-------------|-----------|
| **Vendor** | Creates batches, accepts invoices, tracks shipments | Mobile App |
| **Super Admin** | Creates invoices, assigns pickup drivers, oversees everything | Web Panel |
| **Warehouse Manager** | Receives items, generates barcodes, sorts, assigns transport/delivery drivers | Web Panel |
| **Driver** | Picks up, transports, delivers (same driver can do all three) | Mobile App |
| **Customer** | Receives delivery or picks up from warehouse | SMS notifications |

> **Note:** All locations are called "Warehouses". A warehouse can be of type: `origin` (receives from vendors), `destination` (delivers to customers), or `both`.

---

## Complete Flow

### PHASE 1: Vendor Creates Shipment

```
VENDOR opens app
    |
    +-- Logs in (OTP / PIN / Fingerprint)
    |
    +-- Creates a new BATCH
        |
        +-- Enters CUSTOMER details:
        |   +-- Name: John Mensah
        |   +-- Phone: 0244123456 (types twice to confirm)
        |   +-- Location:
        |       +-- Region -> District -> Town (dropdowns)
        |       +-- OR GH Post address (optional)
        |       +-- OR Pick/Search on map (optional)
        |
        +-- Adds ITEMS to batch:
            +-- Item 1: Fridge (Qty: 5) + photos
            +-- Item 2: AC (Qty: 2) + photos

        |
        v
    SUBMITS BATCH -> Waits for invoice
```

---

### PHASE 2: Admin Processes Request

```
ADMIN (Super Admin at HQ) sees new batch request
    |
    +-- Reviews items and destination
    |
    +-- Creates INVOICE with pricing:
        +-- Pickup fee
        +-- Transport fee
        +-- Handling fee

        |
        v
    SENDS INVOICE to vendor
```

---

### PHASE 3: Vendor Accepts

```
VENDOR receives invoice notification
    |
    +-- Reviews the amount
    |
    +-- ACCEPTS invoice (or rejects)
        |
        v
    Batch is now ready for pickup
```

---

### PHASE 4: Driver Assigned for Pickup

```
ADMIN assigns a DRIVER for pickup
    |
    +-- Sees list of available drivers
    +-- Filters by: vehicle type, location, capacity
    |
    +-- Selects driver -> Driver gets notification
```

---

### PHASE 5: Driver Picks Up from Vendor

```
DRIVER receives pickup assignment
    |
    +-- Sees vendor details and item list
    +-- Calls vendor / Navigates to location
    |
    +-- At vendor location:
        +-- Checks each item against list
        +-- Takes PHOTO of each item
        +-- System captures GPS location
        |
        +-- CONFIRMS pickup
            |
            v
        Vendor sees confirmation with photos
        Driver heads to WAREHOUSE
```

---

### PHASE 6: Warehouse Receives Items

```
WAREHOUSE MANAGER (at origin warehouse)
    |
    +-- Driver arrives with items
    |
    +-- For EACH item:
        +-- Receives/checks item
        +-- Generates BARCODE label
        +-- Prints and attaches to item
        |
        +-- Barcode includes: Batch#, Item#, Destination

        |
        v
    All items now in warehouse inventory
```

---

### PHASE 7: Sorting by Destination

```
WAREHOUSE MANAGER sorts items
    |
    +-- Groups items by destination warehouse:
    |   +-- Kumasi Warehouse: 18 items (3 batches)
    |   +-- Takoradi Warehouse: 15 items
    |   +-- Tamale Warehouse: 14 items
    |
    +-- Creates TRANSPORT MANIFEST for each destination
```

---

### PHASE 8: Transport to Destination Warehouse

```
WAREHOUSE MANAGER assigns DRIVER for transport
    |
    +-- Selects driver with appropriate vehicle
    |
    +-- Driver gets transport assignment
        |
        +-- SCANS each item when loading
        +-- Drives to destination warehouse
        |
        +-- ARRIVES at destination warehouse
```

---

### PHASE 9: Destination Warehouse Receives

```
WAREHOUSE MANAGER (at destination warehouse)
    |
    +-- Transport driver arrives
    |
    +-- For EACH item:
        +-- SCANS barcode to receive
        +-- Verifies against manifest
        |
        +-- Item now in warehouse inventory
```

---

### PHASE 10: Final Delivery

```
WAREHOUSE MANAGER dispatches items to customer
    |
    +-- OPTION A: Assign delivery driver
    |   |
    |   +-- DRIVER:
    |       +-- Gets delivery assignment
    |       +-- Collects items from warehouse
    |       +-- Delivers to customer location
    |       +-- System captures GPS location
    |       +-- Takes PROOF PHOTO
    |       +-- Gets customer SIGNATURE
    |       |
    |       +-- CONFIRMS delivery
    |           |
    |           +-- (If delivery fails: marks reason, returns to warehouse)
    |           |
    |           +-- Vendor notified of successful delivery
    |
    +-- OPTION B: Customer self-pickup
        |
        +-- Customer notified items are ready
        +-- Customer comes to warehouse
        +-- Staff verifies ID/phone
        +-- Scans items out
        +-- Customer signs receipt
        |
        +-- DELIVERY COMPLETE
```

---

## Status Journey

```
DRAFT -> SUBMITTED -> INVOICE SENT -> INVOICE ACCEPTED ->

PICKUP ASSIGNED -> PICKED UP -> AT WAREHOUSE -> SORTED ->

IN TRANSIT -> AT DESTINATION -> OUT FOR DELIVERY -> DELIVERED
```

---

## Warehouse Structure

All locations are **Warehouses** with different types:
- **Origin**: Receives items from vendors (e.g., Accra Warehouse)
- **Destination**: Delivers items to customers (e.g., Kumasi, Takoradi)
- **Both**: Can do both pickup and delivery (e.g., Tamale)

```
SUPER ADMIN (HQ)
    |
    +-- Can see ALL warehouses, ALL items, ALL movements
    |
    +-- ACCRA WAREHOUSE (Type: Origin)
    |   +-- Manager: Kofi
    |   +-- Staff: 3 users
    |   +-- SEES ONLY: Items here, Incoming, Outgoing, Their drivers
    |
    +-- KUMASI WAREHOUSE (Type: Destination)
    |   +-- Manager: Ama
    |   +-- Staff: 2 users
    |   +-- SEES ONLY: Items here, Incoming, Outgoing, Their drivers
    |
    +-- TAKORADI WAREHOUSE (Type: Destination)
    |   +-- Manager: Kweku
    |   +-- Staff: 2 users
    |   +-- SEES ONLY: Items here, Incoming, Outgoing, Their drivers
    |
    +-- TAMALE WAREHOUSE (Type: Both)
        +-- Manager: Yaw
        +-- Staff: 2 users
        +-- SEES ONLY: Items here, Incoming, Outgoing, Their drivers
```

---

## Driver Model

Drivers are flexible - one driver can perform multiple task types based on their capabilities and vehicle.

### Driver Profile

```
DRIVER: Daniel Ofori
    |
    +-- Vehicle Info:
    |   +-- Type: Truck
    |   +-- Capacity: 500kg
    |   +-- Plate: GR-1234-20
    |
    +-- Base Location: Accra Warehouse
    +-- Status: Available
    |
    +-- CAPABILITIES:
        +-- [x] Pickup - Can pick from vendors
        +-- [x] Transport - Can do inter-warehouse transfers
        +-- [x] Delivery - Can deliver to customers
```

### Assignment Types

| Type | From | To | Assigned By |
|------|------|-----|-------------|
| **Pickup** | Vendor | Origin Warehouse | Super Admin |
| **Transport** | Origin Warehouse | Destination Warehouse | Warehouse Manager |
| **Delivery** | Destination Warehouse | Customer | Warehouse Manager |

### Example: Full Round Trip

```
MORNING - PICKUP:
    Daniel picks up 5 fridges + 2 ACs from vendor in Accra
    -> Takes to Accra Warehouse (origin)

MIDDAY - TRANSPORT:
    Daniel loads 18 items for Kumasi
    -> Drives to Kumasi Warehouse (destination)

AFTERNOON - DELIVERY:
    Daniel delivers the fridges + ACs to customer in Kumasi
    -> Gets proof of delivery

Same driver, same truck, THREE different assignment types!
```

---

## Customer Location Options

When vendor creates a batch, they can specify customer location using:

1. **Cascading Dropdowns**
   - Region (dropdown)
   - District (dropdown, based on region)
   - Town/Area (text input)

2. **GH Post Address** (optional)
   - e.g., "AK-039-5028"

3. **Pick from Map** (optional)
   - Click on map to drop pin

4. **Search from Map** (optional)
   - Search for location name

---

## GPS Tracking

GPS location is captured at key moments:

### At Pickup
```
{
    action: "pickup_confirmed",
    captured_location: { lat: 5.5913, lng: -0.1864 },
    photos: ["img1.jpg", "img2.jpg"]
}
```

### At Delivery
```
{
    action: "delivery_confirmed",
    captured_location: { lat: 6.6885, lng: -1.6244 },
    proof_photo: "delivery_proof.jpg",
    signature: "signature_data..."
}
```

This helps verify:
- Driver actually went to the location
- Compare with expected customer location
- Audit trail for disputes

---

## Key Business Rules

1. **One driver can do pickup + transport + delivery** based on vehicle and availability
2. **GPS captured** at every pickup and delivery for verification
3. **Photos required** at pickup and delivery as proof
4. **Barcodes** generated at warehouse for tracking through the system
5. **Each warehouse** only sees their own inventory (scoped access)
6. **Super Admin** sees everything across all locations
7. **Phone number confirmation** - customer phone entered twice to prevent errors
8. **Invoice acceptance required** before pickup can be assigned
9. **All locations are "Warehouses"** - no Hub distinction (use types: origin/destination/both)
10. **Vendors and Drivers use mobile app** - REST APIs for all mobile functionality

---

## Notifications

| Event | Who Gets Notified | Method |
|-------|-------------------|--------|
| Batch submitted | Admin | Web notification |
| Invoice sent | Vendor | Push + SMS |
| Invoice accepted | Admin | Web notification |
| Pickup assigned | Driver | Push notification |
| Pickup confirmed | Vendor | Push notification |
| Items at warehouse | Admin | Web notification |
| Transport assigned | Driver | Push notification |
| Items at destination | Warehouse Manager | Web notification |
| Delivery assigned | Driver | Push notification |
| Delivery completed | Vendor | Push + SMS |
| Ready for pickup | Customer | SMS |

---

## Failed Delivery Handling

If delivery fails:
1. Driver marks delivery as failed (via mobile app)
2. Selects reason:
   - Customer not available
   - Wrong address
   - Customer rejected
   - Other (with notes)
3. Items returned to warehouse
4. Warehouse Manager decides next action:
   - Reschedule delivery
   - Contact customer
   - Return to sender

---

*Document Version: 1.1*
*Last Updated: January 2026*
*Changes: Unified all locations as "Warehouses" (no Hub distinction), clarified mobile app architecture*
