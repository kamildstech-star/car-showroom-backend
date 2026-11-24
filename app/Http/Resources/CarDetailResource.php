<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryImg = $this->primaryImage ?: $this->images?->first();

        return [
            'id' => $this->id,
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
            'status' => $this->status, // available or sold
            'is_featured' => (bool) $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),
            'primary_image' => $primaryImg ? new CarImageResource($primaryImg) : null,
            'images' => CarImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
