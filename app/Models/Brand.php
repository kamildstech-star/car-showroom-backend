<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Clear cached active brands when brands are modified.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            cache()->forget('public_active_brands');
        });

        static::deleted(function () {
            cache()->forget('public_active_brands');
        });
    }

    /**
     * Cars belonging to this brand.
     */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }
}
