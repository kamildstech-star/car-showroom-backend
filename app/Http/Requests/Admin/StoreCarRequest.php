<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'model' => ['required', 'string', 'max:100'],
            'variant' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:191', 'unique:cars,slug'],
            'year' => ['required', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'engine_capacity' => ['nullable', 'integer', 'min:100', 'max:10000'],
            'transmission' => ['required', 'string', Rule::in(['automatic', 'manual'])],
            'fuel_type' => ['required', 'string', Rule::in(['petrol', 'diesel', 'hybrid', 'electric'])],
            'exterior_color' => ['nullable', 'string', 'max:50'],
            'registration_city' => ['nullable', 'string', 'max:100'],
            'assembly' => ['nullable', 'string', Rule::in(['local', 'imported'])],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['available', 'sold'])],
            'publication_status' => ['sometimes', 'string', Rule::in(['draft', 'published', 'archived'])],
            'is_featured' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
