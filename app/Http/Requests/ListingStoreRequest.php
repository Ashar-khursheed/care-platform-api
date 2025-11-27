<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListingStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only providers can create listings
        return $this->user() && $this->user()->isProvider();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:service_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:50|max:2000',
            'hourly_rate' => 'required|numeric|min:0|max:999.99',
            'years_of_experience' => 'required|integer|min:0|max:50',
            'skills' => 'required|array|min:1',
            'skills.*' => 'string|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string|max:200',
            'availability' => 'nullable|array',
            'service_location' => 'required|string|max:500',
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
            'category_id.required' => 'Please select a service category.',
            'category_id.exists' => 'Selected category does not exist.',
            'title.required' => 'Please provide a listing title.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.required' => 'Please provide a description.',
            'description.min' => 'Description must be at least 50 characters.',
            'description.max' => 'Description cannot exceed 2000 characters.',
            'hourly_rate.required' => 'Please specify your hourly rate.',
            'hourly_rate.min' => 'Hourly rate must be at least $0.',
            'hourly_rate.max' => 'Hourly rate cannot exceed $999.99.',
            'years_of_experience.required' => 'Please specify your years of experience.',
            'skills.required' => 'Please add at least one skill.',
            'skills.min' => 'Please add at least one skill.',
            'service_location.required' => 'Please specify where you provide services.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set default values
        if (!$this->has('is_available')) {
            $this->merge(['is_available' => true]);
        }
    }
}