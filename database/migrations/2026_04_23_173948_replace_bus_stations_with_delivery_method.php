<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the admin-preselected `bus_stations` model with:
 *   - `shipment_items.delivery_method` ('direct' | 'bus_handoff'), tagged by
 *     the warehouse agent at receiving time
 *   - `delivery_run_stops.bus_station_name` free-text, captured by the
 *     driver at handoff time
 *
 * Pre-existing `shipment_items.bus_station_id` values are migrated:
 *   - Items with a station set → delivery_method = 'bus_handoff'
 *   - Any delivery_run_stop already linked to such items → the station's
 *     name is copied onto stop.bus_station_name so historical records
 *     keep their station
 *
 * Finally drops the FK, the column, and the `bus_stations` table.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. New columns
        Schema::table('shipment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('shipment_items', 'delivery_method')) {
                $table->string('delivery_method', 20)
                    ->default('direct')
                    ->after('fulfillment_type');
                $table->index('delivery_method');
            }
        });

        Schema::table('delivery_run_stops', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_run_stops', 'bus_station_name')) {
                $table->string('bus_station_name', 255)
                    ->nullable()
                    ->after('handoff_vehicle_number');
            }
        });

        // 2. Backfill delivery_method on items with an existing bus_station_id
        if (Schema::hasColumn('shipment_items', 'bus_station_id')) {
            DB::table('shipment_items')
                ->whereNotNull('bus_station_id')
                ->update(['delivery_method' => 'bus_handoff']);

            // 3. Backfill bus_station_name on stops linked to those items,
            //    using the station's name from the legacy table.
            if (Schema::hasTable('bus_stations')) {
                $rows = DB::table('delivery_run_items as dri')
                    ->join('shipment_items as si', 'si.id', '=', 'dri.shipment_item_id')
                    ->join('bus_stations as bs', 'bs.id', '=', 'si.bus_station_id')
                    ->select('dri.delivery_run_stop_id as stop_id', 'bs.name as station_name')
                    ->whereNotNull('dri.delivery_run_stop_id')
                    ->whereNotNull('si.bus_station_id')
                    ->distinct()
                    ->get();

                foreach ($rows as $row) {
                    DB::table('delivery_run_stops')
                        ->where('id', $row->stop_id)
                        ->whereNull('bus_station_name')
                        ->update(['bus_station_name' => $row->station_name]);
                }
            }
        }

        // 4. Drop FK + column on shipment_items, then drop the table.
        if (Schema::hasColumn('shipment_items', 'bus_station_id')) {
            Schema::table('shipment_items', function (Blueprint $table) {
                try {
                    $table->dropForeign(['bus_station_id']);
                } catch (\Throwable $e) {
                    // FK may have been named differently or already dropped.
                }
                $table->dropColumn('bus_station_id');
            });
        }

        Schema::dropIfExists('bus_stations');
    }

    public function down(): void
    {
        if (!Schema::hasTable('bus_stations')) {
            Schema::create('bus_stations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('shipment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('shipment_items', 'bus_station_id')) {
                $table->unsignedBigInteger('bus_station_id')->nullable()->after('delivery_instructions');
                $table->foreign('bus_station_id')->references('id')->on('bus_stations')->nullOnDelete();
            }
        });

        Schema::table('delivery_run_stops', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_run_stops', 'bus_station_name')) {
                $table->dropColumn('bus_station_name');
            }
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_items', 'delivery_method')) {
                $table->dropIndex(['delivery_method']);
                $table->dropColumn('delivery_method');
            }
        });
    }
};
