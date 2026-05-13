<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformSetting extends Model
{
    /**
     * Request-level cache for settings that are read many times while rendering.
     *
     * @var array<string, mixed>
     */
    private static array $valueCache = [];

    protected $fillable = [
        'key',
        'value',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $setting): void {
            unset(self::$valueCache[$setting->key]);
        });
        static::deleted(function (self $setting): void {
            unset(self::$valueCache[$setting->key]);
        });
    }

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$valueCache)) {
            return self::$valueCache[$key];
        }

        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return self::$valueCache[$key] = $default;
        }

        if ($setting->is_encrypted && $setting->value) {
            try {
                return self::$valueCache[$key] = Crypt::decryptString($setting->value);
            } catch (\Exception $e) {
                return self::$valueCache[$key] = $default;
            }
        }

        return self::$valueCache[$key] = $setting->value;
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, mixed $value, bool $encrypt = false, ?string $description = null): static
    {
        $storedValue = $encrypt && $value ? Crypt::encryptString($value) : $value;

        unset(self::$valueCache[$key]);

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'is_encrypted' => $encrypt,
                'description' => $description,
            ]
        );
    }
}
