-- =====================================================================
-- ParcelMan Express — Live DB hot-fix
-- Creates the missing `outgoing_batches` table + FK column on shipment_items
--
-- WHY: The admin Outgoing/Incoming Batches pages 500 with
--   SQLSTATE[42S02]: Base table or view not found: 1146 Table
--   'parcelman_demo.outgoing_batches' doesn't exist
-- because migration 2026_08_24_110355_create_outgoing_batches_table
-- was never run against the live database.
--
-- PREFERRED FIX: run on the server (repo root) instead of this file:
--   php artisan migrate --pretend     # preview first
--   php artisan migrate                # apply pending migrations
--
-- FALLBACK (phpMyAdmin / MySQL CLI): execute the statements below.
-- =====================================================================

-- 1) The outgoing_batches table (hub-and-spoke transport batches)
CREATE TABLE outgoing_batches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(255) NOT NULL,
    delivery_region_id BIGINT UNSIGNED NOT NULL,
    delivery_district_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'open', -- open | in_transit | at_warehouse | dispatched | received
    transport_driver_id BIGINT UNSIGNED NULL,    -- van driver moving the batch to the region
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE INDEX outgoing_batches_batch_number_unique (batch_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Attach batches to existing shipment items
ALTER TABLE shipment_items
    ADD COLUMN outgoing_batch_id BIGINT UNSIGNED NULL AFTER delivery_district_id;

-- (Optional but recommended) index for the new FK column
CREATE INDEX shipment_items_outgoing_batch_id_index ON shipment_items (outgoing_batch_id);

-- 3) IMPORTANT — record the migration as applied so a later `php artisan migrate`
--    does NOT try to re-run it (Laravel tracks this in the `migrations` table).
--    The batch number is computed automatically (current max + 1), so this
--    statement needs NO manual editing — just run it as-is.
INSERT INTO migrations (migration, batch)
SELECT '2026_08_24_110355_create_outgoing_batches_table', COALESCE(MAX(batch), 0) + 1
FROM migrations;
