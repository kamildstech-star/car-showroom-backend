<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
            'is_active' => (bool) $this->is_active,
            'cars_count' => $this->whenCounted('cars'),
            'published_cars_count' => $this->whenAppended('published_cars_count', $this->cars_count),
        ];
    }
}
