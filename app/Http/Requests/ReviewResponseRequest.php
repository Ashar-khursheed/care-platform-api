<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $review = $this->route('review');
        
        // Only the provider who received the review can respond
        return $this->user() && 
               $this->user()->isProvider() &&
               $this->user()->id === $review->provider_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'response' => 'required|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'response.required' => 'Response is required.',
            'response.max' => 'Response cannot exceed 500 characters.',
        ];
    }
}