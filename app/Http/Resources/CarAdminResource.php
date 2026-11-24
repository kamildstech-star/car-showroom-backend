<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'model' => $this->model,
            'variant' => $this->variant,
            'title' => trim("{$this->model} {$this->variant} {$this->year}"),
            'slug' => $this->slug,
            'year' => (int) $this->year,
            'price' => (float) $this->price,
            'price_formatted' => 'PKR ' . number_format((float) $this->price, 0),
            'mileage' => $this->mileage ? (int) $this->mileage : null,
            'engine_capacity' => $this->engine_capacity ? (int) $this->engine_capacity : null,
            'transmission' => $this->transmission,
            'fuel_type' => $this->fuel_type,
            'exterior_color' => $this->exterior_color,
            'registration_city' => $this->registration_city,
            'assembly' => $this->assembly,
            'description' => $this->description,
            'status' => $this->status,
            'publication_status' => $this->publication_status,
            'is_featured' => (bool) $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'leads_count' => $this->whenCounted('leads'),
            'primary_image' => $this->primaryImage ? new CarImageResource($this->primaryImage) : null,
            'images' => CarImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
