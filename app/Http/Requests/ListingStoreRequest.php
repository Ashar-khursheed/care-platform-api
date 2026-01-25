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
        // Allow both providers and clients to create listings
        // Providers create "Service Offerings"
        // Clients create "Job Posts"
        return $this->user() && ($this->user()->isProvider() || $this->user()->isClient());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'category_id' => 'required|exists:service_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20|max:2000', // Reduced min length
            'hourly_rate' => 'required|numeric|min:0|max:999.99',
            'service_location' => 'required|string|max:500',
            'service_radius' => 'nullable|numeric|min:0|max:100',
            'is_available' => 'boolean',
            'availability' => 'nullable|array',
        ];

        // Provider-specific requirements
        if ($this->user()->isProvider()) {
            $rules['years_of_experience'] = 'required|integer|min:0|max:50';
            $rules['skills'] = 'required|array|min:1';
            $rules['skills.*'] = 'string|max:100';
            $rules['languages'] = 'nullable|array';
            $rules['languages.*'] = 'string|max:50';
            $rules['certifications'] = 'nullable|array';
            $rules['certifications.*'] = 'string|max:200';
        } else {
            // Client-specific (Job Post) relaxation
            $rules['years_of_experience'] = 'nullable|integer|min:0|max:50';
            $rules['skills'] = 'nullable|array'; // Optional for job posts, but recommended
            $rules['skills.*'] = 'string|max:100';
            $rules['languages'] = 'nullable|array';
            $rules['certifications'] = 'nullable|array';
        }

        return $rules;
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