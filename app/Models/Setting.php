<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * Strict allowlist of settings permitted to be exposed publicly.
     */
    public const ALLOWED_PUBLIC_KEYS = [
        'showroom_name',
        'showroom_tagline',
        'showroom_phone',
        'showroom_whatsapp',
        'showroom_email',
        'showroom_address',
        'showroom_city',
        'opening_hours',
    ];

    protected $fillable = [
        'key',
        'value',
        'group',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Precision cache invalidation: flushes public showroom cache only
     * if the modified setting is in the allowed public keys.
     */
    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            if (in_array($setting->key, self::ALLOWED_PUBLIC_KEYS, true)) {
                cache()->forget('public_showroom_settings');
            }
        });

        static::deleted(function (Setting $setting) {
            if (in_array($setting->key, self::ALLOWED_PUBLIC_KEYS, true)) {
                cache()->forget('public_showroom_settings');
            }
        });
    }

    /**
     * Get setting value by key.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set or create setting.
     */
    public static function set(string $key, ?string $value, string $group = 'general', bool $isPublic = true): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'is_public' => $isPublic,
            ]
        );
    }
}
