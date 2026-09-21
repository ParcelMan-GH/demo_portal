<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A call an agent logged against a parcel, as sent from the agent app.
 */
class AgentCallLog extends Model
{
    /** The recipient confirmed the order and paid — triggers auto-batching. */
    public const OUTCOME_CONFIRMED = 'confirmed';

    public const OUTCOME_RESCHEDULED = 'rescheduled';

    public const OUTCOME_UNREACHABLE = 'unreachable';

    public const OUTCOME_CANCELLED = 'cancelled';

    /**
     * @var array<int, string>
     */
    public const OUTCOMES = [
        self::OUTCOME_CONFIRMED,
        self::OUTCOME_RESCHEDULED,
        self::OUTCOME_UNREACHABLE,
        self::OUTCOME_CANCELLED,
    ];

    /**
     * Client spellings mapped onto the canonical outcomes.
     *
     * The spec for this feature calls the trigger "Confirmed Payment" and
     * `CONFIRMED_PAYMENT` while the mobile app sends the literal "confirmed";
     * accepting both keeps the API tolerant without a second decision point
     * downstream.
     *
     * @var array<string, string>
     */
    public const OUTCOME_ALIASES = [
        'confirmed' => self::OUTCOME_CONFIRMED,
        'confirm' => self::OUTCOME_CONFIRMED,
        'confirmed_payment' => self::OUTCOME_CONFIRMED,
        'payment_confirmed' => self::OUTCOME_CONFIRMED,
        'paid' => self::OUTCOME_CONFIRMED,
        'rescheduled' => self::OUTCOME_RESCHEDULED,
        'reschedule' => self::OUTCOME_RESCHEDULED,
        'unreachable' => self::OUTCOME_UNREACHABLE,
        'no_answer' => self::OUTCOME_UNREACHABLE,
        'noanswer' => self::OUTCOME_UNREACHABLE,
        'cancelled' => self::OUTCOME_CANCELLED,
        'canceled' => self::OUTCOME_CANCELLED,
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'shipment_item_id',
        'agent_id',
        'outcome',
        'notes',
        'amount_paid',
        'payment_proof_path',
        'rescheduled_for',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount_paid' => 'decimal:2',
        'rescheduled_for' => 'datetime',
    ];

    /**
     * Map any accepted client spelling onto a canonical outcome, or null when
     * the value is not recognised.
     */
    public static function normalizeOutcome(?string $value): ?string
    {
        $key = strtolower(trim((string) $value));
        $key = str_replace([' ', '-'], '_', $key);

        return self::OUTCOME_ALIASES[$key] ?? null;
    }

    /**
     * Whether this log represents a confirmed payment.
     */
    public function isConfirmedPayment(): bool
    {
        return $this->outcome === self::OUTCOME_CONFIRMED;
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
