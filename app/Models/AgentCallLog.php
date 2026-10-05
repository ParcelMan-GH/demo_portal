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

    /** The agent was given the code by the recipient. */
    public const CODE_SOURCE_CUSTOMER = 'customer';

    /** The agent was given the code by the hub agent holding the parcel. */
    public const CODE_SOURCE_HUB_AGENT = 'hub_agent';

    /**
     * Where a pickup code may come from.
     *
     * Both are accepted — the agent is expected to phone the recipient, and the
     * hub agent is a legitimate fallback when the recipient cannot be reached.
     * They are recorded separately because they are not equally strong evidence:
     * a hub agent always knows the code, so a run of hub-sourced confirmations is
     * the signal that this step is being short-cut. Cheap to capture now and
     * impossible to recover later if it is not.
     *
     * @var array<int, string>
     */
    public const CODE_SOURCES = [
        self::CODE_SOURCE_CUSTOMER,
        self::CODE_SOURCE_HUB_AGENT,
    ];

    /** Confirmed by the contact agent themself. */
    public const CONFIRMED_BY_AGENT = 'agent';

    /** Confirmed by an admin on the agent's behalf. */
    public const CONFIRMED_BY_ADMIN = 'admin';

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
        'pickup_code_confirmed_at',
        'pickup_code_source',
        'pickup_code_confirmed_by',
        'pickup_code_confirmed_by_user_id',
        'pickup_code_confirmed_before_release',
        'pickup_code_attempts',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount_paid' => 'decimal:2',
        'rescheduled_for' => 'datetime',
        'pickup_code_confirmed_at' => 'datetime',
        'pickup_code_confirmed_before_release' => 'boolean',
        'pickup_code_attempts' => 'integer',
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

    /**
     * Whether the agent has confirmed this call against the parcel's pickup code.
     *
     * Distinct from `isConfirmedPayment()`: that records what the recipient said on
     * the phone, this records that the codes matched. A day's commission is gated
     * on this one.
     */
    public function isPickupCodeConfirmed(): bool
    {
        return $this->pickup_code_confirmed_at !== null;
    }

    /** Confirmed by an admin rather than by the agent. */
    public function wasConfirmedByAdmin(): bool
    {
        return $this->pickup_code_confirmed_by === self::CONFIRMED_BY_ADMIN;
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pickup_code_confirmed_by_user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
