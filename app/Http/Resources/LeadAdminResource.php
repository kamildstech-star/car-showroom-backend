<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $car = $this->whenLoaded('car');
        $carData = null;

        if ($this->relationLoaded('car') && $this->car) {
            $carData = [
                'id' => $this->car->id,
                'brand' => $this->car->relationLoaded('brand') && $this->car->brand ? [
                    'id' => $this->car->brand->id,
                    'name' => $this->car->brand->name,
                    'slug' => $this->car->brand->slug,
                ] : null,
                'model' => $this->car->model,
                'variant' => $this->car->variant,
                'title' => trim("{$this->car->model} {$this->car->variant} {$this->car->year}"),
                'slug' => $this->car->slug,
                'year' => (int) $this->car->year,
                'price' => (float) $this->car->price,
                'price_formatted' => 'PKR ' . number_format((float) $this->car->price, 0),
                'status' => $this->car->status,
                'publication_status' => $this->car->publication_status,
                'deleted_at' => $this->car->deleted_at?->toIso8601String(),
            ];
        }

        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'phone' => $this->phone,
            'message' => $this->message,
            'status' => $this->status,
            'source' => $this->source,
            'whatsapp_clicked_at' => $this->whatsapp_clicked_at?->toIso8601String(),
            'contacted_at' => $this->contacted_at?->toIso8601String(),
            'notes' => $this->notes,
            'car' => $carData,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
