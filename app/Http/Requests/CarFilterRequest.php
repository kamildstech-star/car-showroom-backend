<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => ['sometimes', 'string', 'max:120'],
            'brand_id' => ['sometimes', 'integer', 'exists:brands,id'],
            'search' => ['sometimes', 'string', 'max:100'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'gte:min_price'],
            'min_year' => ['sometimes', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'max_year' => ['sometimes', 'integer', 'gte:min_year', 'max:' . (date('Y') + 1)],
            'transmission' => ['sometimes', 'string', Rule::in(['automatic', 'manual'])],
            'fuel_type' => ['sometimes', 'string', Rule::in(['petrol', 'diesel', 'hybrid', 'electric'])],
            'min_mileage' => ['sometimes', 'integer', 'min:0'],
            'max_mileage' => ['sometimes', 'integer', 'gte:min_mileage'],
            'status' => ['sometimes', 'string', Rule::in(['available', 'sold', 'all'])],
            'sort' => ['sometimes', 'string', Rule::in(['newest', 'oldest', 'price_asc', 'price_desc', 'mileage_asc', 'year_desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
