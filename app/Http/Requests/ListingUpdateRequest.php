<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only providers can update listings
        return $this->user() && $this->user()->isProvider();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:service_categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|min:50|max:2000',
            'hourly_rate' => 'sometimes|numeric|min:0|max:999.99',
            'years_of_experience' => 'sometimes|integer|min:0|max:50',
            'skills' => 'sometimes|array|min:1',
            'skills.*' => 'string|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string|max:200',
            'availability' => 'nullable|array',
            'service_location' => 'sometimes|string|max:500',
            'service_radius' => 'nullable|numeric|min:0|max:100',
            'is_available' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Selected category does not exist.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.min' => 'Description must be at least 50 characters.',
            'description.max' => 'Description cannot exceed 2000 characters.',
            'hourly_rate.min' => 'Hourly rate must be at least $0.',
            'hourly_rate.max' => 'Hourly rate cannot exceed $999.99.',
            'skills.min' => 'Please add at least one skill.',
        ];
    }
}