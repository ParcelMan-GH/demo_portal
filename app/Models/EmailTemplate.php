<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    public const VENDOR_WELCOME = 'vendor_welcome';
    public const SHIPMENT_SUBMITTED = 'shipment_submitted';
    public const PICKUP_ASSIGNED = 'pickup_assigned';
    public const PACKAGE_AT_WAREHOUSE = 'package_at_warehouse';
    public const PAYMENT_REQUIRED = 'payment_required';
    public const PAYMENT_RECEIVED = 'payment_received';
    public const PACKAGE_READY_FOR_COLLECTION = 'package_ready_for_collection';
    public const DELIVERY_OUT_FOR_DELIVERY = 'delivery_out_for_delivery';
    public const DELIVERY_COMPLETED = 'delivery_completed';
    public const PASSWORD_RESET = 'password_reset';

    protected $fillable = [
        'key',
        'name',
        'category',
        'recipient_type',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'is_enabled',
        'is_system',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_enabled' => 'boolean',
        'is_system' => 'boolean',
    ];
}
