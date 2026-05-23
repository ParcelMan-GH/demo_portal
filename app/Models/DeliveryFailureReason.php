<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryFailureReason extends Model
{
    use SoftDeletes;

    public const TYPE_NOT_RECEIVED = 'not_received';
    public const TYPE_ISSUE = 'issue';
    public const TYPE_FAILED = 'failed';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'label',
        'slug',
        'type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_NOT_RECEIVED,
            self::TYPE_ISSUE,
            self::TYPE_FAILED,
            self::TYPE_OTHER,
        ];
    }
}
