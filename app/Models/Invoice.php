<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipment_id',
        'invoice_number',
        'status',
        'pickup_fee',
        'transport_fee',
        'handling_fee',
        'other_fee',
        'total_amount',
        'currency',
        'notes',
        'vendor_notes',
        'rejection_reason',
        'cancel_reason',
        'created_by',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'pickup_fee' => 'decimal:2',
        'transport_fee' => 'decimal:2',
        'handling_fee' => 'decimal:2',
        'other_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }

            // Auto-calculate total
            $invoice->total_amount = $invoice->pickup_fee + $invoice->transport_fee + $invoice->handling_fee + $invoice->other_fee;
        });

        static::updating(function ($invoice) {
            // Re-calculate total on update
            $invoice->total_amount = $invoice->pickup_fee + $invoice->transport_fee + $invoice->handling_fee + $invoice->other_fee;
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $rawPrefix = (string) PlatformSetting::getValue('invoice_prefix', 'INV-');
        $prefix = rtrim(trim($rawPrefix), '-');
        $prefix = $prefix !== '' ? $prefix : 'INV';

        $startNumber = (int) PlatformSetting::getValue('invoice_start_number', 1000);
        $startNumber = $startNumber > 0 ? $startNumber : 1000;
        $numberLength = max(5, strlen((string) $startNumber));

        $year = date('Y');

        $lastInvoice = static::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            $parts = explode('-', $lastInvoice->invoice_number);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = $startNumber;
        }

        return sprintf('%s-%s-%0' . $numberLength . 'd', $prefix, $year, $nextNumber);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
