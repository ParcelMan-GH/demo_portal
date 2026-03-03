<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentPayment extends Model
{
    protected $fillable = [
        'shipment_id',
        'invoice_id',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'recorded_by_admin_id',
        'payment_date',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public static array $methodLabels = [
        'cash'           => 'Cash',
        'bank_transfer'  => 'Bank Transfer',
        'mobile_money'   => 'Mobile Money',
        'cheque'         => 'Cheque',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'recorded_by_admin_id');
    }

    public function methodLabel(): string
    {
        return self::$methodLabels[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public function formattedAmount(): string
    {
        return number_format((float) $this->amount, 2);
    }
}
