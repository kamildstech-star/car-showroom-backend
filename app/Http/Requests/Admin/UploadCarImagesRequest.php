<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadCarImagesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize payload so single files or array of files are both supported.
     */
    protected function prepareForValidation(): void
    {
        if ($this->hasFile('images') && !is_array($this->file('images'))) {
            $this->merge([
                'images' => [$this->file('images')],
            ]);
        } elseif ($this->hasFile('image') && !$this->hasFile('images')) {
            $this->merge([
                'images' => [$this->file('image')],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5MB in kilobytes
                'dimensions:min_width=100,min_height=100',
            ],
            'alt_texts' => ['nullable', 'array'],
            'alt_texts.*' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Please provide at least one image file to upload.',
            'images.array' => 'Images must be provided as an array or file list.',
            'images.max' => 'You cannot upload more than 10 images at once.',
            'images.*.required' => 'Each image file is required.',
            'images.*.file' => 'Uploaded item must be a valid file.',
            'images.*.image' => 'Uploaded file must be a valid image.',
            'images.*.mimes' => 'Only JPEG, JPG, PNG, and WebP image formats are accepted.',
            'images.*.max' => 'Each image must not exceed 5MB.',
            'images.*.dimensions' => 'Images must be at least 100x100 pixels in size.',
        ];
    }
}
