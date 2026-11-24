<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'min:7', 'max:30', 'regex:/^(\+?[0-9\s\-\(\)]+)$/'],
            'message' => ['nullable', 'string', 'max:2000'],
            'car_id' => ['nullable', 'integer', 'exists:cars,id'],
            'source' => ['nullable', 'string', 'max:50'],
            // Honeypot field for bot spam trap (must be empty)
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Please provide your full name.',
            'customer_name.min' => 'Your name must be at least 2 characters.',
            'phone.required' => 'Please provide a valid phone or WhatsApp number.',
            'phone.regex' => 'Please provide a valid phone number format.',
            'car_id.exists' => 'The selected vehicle was not found in our showroom.',
            'website.max' => 'Spam submission detected.',
        ];
    }
}
