<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'showroom_name' => ['nullable', 'string', 'max:100'],
            'showroom_tagline' => ['nullable', 'string', 'max:255'],
            'showroom_phone' => ['nullable', 'string', 'max:30'],
            'showroom_whatsapp' => ['nullable', 'string', 'max:30'],
            'showroom_email' => ['nullable', 'email', 'max:100'],
            'showroom_address' => ['nullable', 'string', 'max:255'],
            'showroom_city' => ['nullable', 'string', 'max:100'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
        ];
    }
}
