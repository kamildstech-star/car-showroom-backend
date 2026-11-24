<?php

namespace App\Models;

use Database\Factories\CarFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    /** @use HasFactory<CarFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'model',
        'variant',
        'slug',
        'year',
        'price',
        'mileage',
        'engine_capacity',
        'transmission',
        'fuel_type',
        'exterior_color',
        'registration_city',
        'assembly',
        'description',
        'status',
        'publication_status',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'price' => 'decimal:2',
            'mileage' => 'integer',
            'engine_capacity' => 'integer',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Clear cached public listings when cars are modified.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            cache()->forget('public_featured_cars');
            cache()->forget('public_latest_cars');
            cache()->forget('public_active_brands');
        });

        static::deleted(function () {
            cache()->forget('public_featured_cars');
            cache()->forget('public_latest_cars');
            cache()->forget('public_active_brands');
        });
    }

    /**
     * Vehicle brand.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Vehicle images ordered by sort_order.
     */
    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)->orderBy('sort_order', 'asc');
    }

    /**
     * Primary display image.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(CarImage::class)->where('is_primary', true);
    }

    /**
     * Inquiries/leads for this vehicle.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Scope: published cars only.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status', 'published');
    }

    /**
     * Scope: available for sale only.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope: featured cars only.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
