<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItemTracking extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipment_item_id',
        'status',
        'location',
        'notes',
        'meta',
        'created_by',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipment_item_tracking';

    /**
     * Get the shipment item this tracking entry belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class, 'shipment_item_id');
    }

    /**
     * The `created_by` values that mean "this user acted".
     *
     * `created_by` has never been written in one format and the existing rows
     * cannot be rewritten, so a filter has to accept every form in the database
     * or it will silently under-report. Two are in use for a signed-in user:
     *
     *   - `user:{id}` — written by the warehouse services
     *     (WarehouseSortingService, WarehouseTransportService, …)
     *   - `{id}` — a bare id, written by HubBusHandoffService for hub agents
     *
     * `driver:{id}` is deliberately absent. It is a different principal
     * namespace: a driver's id is not a user id, so matching `driver:20` to user
     * 20 would attribute a rider's actions to an admin who happens to share the
     * number.
     *
     * This lives on the model rather than in the one caller because the format
     * is a property of the column, not of any single screen. Nothing else should
     * hard-code these prefixes.
     *
     * @return array<int, string>
     */
    public static function actorValues(int|string|null $userId): array
    {
        if ($userId === null || $userId === '') {
            return [];
        }

        $id = (string) $userId;

        return [$id, "user:{$id}"];
    }

    /**
     * Restrict to rows created by the given user, in any recorded format.
     *
     * Returns no rows at all when `$userId` is null/empty — an unknown actor must
     * never quietly widen to "everyone", which is the bug this replaces.
     */
    public function scopeWhereActor($query, int|string|null $userId)
    {
        $values = self::actorValues($userId);

        if ($values === []) {
            // A predicate that matches nothing, so an unresolved actor cannot
            // fall through to the whole table.
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('created_by', $values);
    }
}
