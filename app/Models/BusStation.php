<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BusStation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'location_hint',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $station) {
            if (blank($station->slug) && filled($station->name)) {
                $station->slug = Str::slug($station->name);
            }
        });
    }
}
