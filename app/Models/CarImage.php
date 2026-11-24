<?php

namespace App\Models;

use Database\Factories\CarImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarImage extends Model
{
    /** @use HasFactory<CarImageFactory> */
    use HasFactory;

    protected $fillable = [
        'car_id',
        'image_path',
        'thumbnail_path',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Car this image belongs to.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
