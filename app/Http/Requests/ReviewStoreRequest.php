<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only clients can create reviews
        return $this->user() && $this->user()->isClient();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'booking_id' => [
                'required',
                'integer',
                'exists:bookings,id',
                Rule::unique('reviews', 'booking_id')->whereNull('deleted_at'),
            ],
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking ID is required.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'booking_id.unique' => 'You have already reviewed this booking.',
            'rating.required' => 'Rating is required.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
            'comment.max' => 'Comment cannot exceed 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $booking = Booking::find($this->booking_id);

            if (!$booking) {
                return;
            }

            // Check if booking belongs to this client
            if ($booking->client_id !== $this->user()->id) {
                $validator->errors()->add(
                    'booking_id',
                    'This booking does not belong to you.'
                );
            }

            // Check if booking is completed
            if ($booking->status !== 'completed') {
                $validator->errors()->add(
                    'booking_id',
                    'You can only review completed bookings.'
                );
            }

            // Check if already reviewed
            $existingReview = Review::where('booking_id', $this->booking_id)
                ->whereNull('deleted_at')
                ->exists();

            if ($existingReview) {
                $validator->errors()->add(
                    'booking_id',
                    'You have already reviewed this booking.'
                );
            }
        });
    }
}