<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CarImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk('public');

        return [
            'id' => $this->id,
            'car_id' => $this->car_id,
            'image_path' => $this->image_path,
            'thumbnail_path' => $this->thumbnail_path,
            'url' => $this->image_path ? $disk->url($this->image_path) : null,
            'thumbnail_url' => $this->thumbnail_path
                ? $disk->url($this->thumbnail_path)
                : ($this->image_path ? $disk->url($this->image_path) : null),
            'alt_text' => $this->alt_text,
            'is_primary' => (bool) $this->is_primary,
            'sort_order' => (int) $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
