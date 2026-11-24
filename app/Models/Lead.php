<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'car_id',
        'customer_name',
        'phone',
        'message',
        'status',
        'source',
        'whatsapp_clicked_at',
        'contacted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_clicked_at' => 'datetime',
            'contacted_at' => 'datetime',
        ];
    }

    /**
     * Associated car (if inquiry was from a car detail page).
     * Includes soft-deleted cars so inquiry context is never lost.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class)->withTrashed();
    }
}
