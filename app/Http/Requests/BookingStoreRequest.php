<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class BookingStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only clients can create bookings
        return $this->user() && $this->user()->isClient();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'listing_id' => 'required|exists:service_listings,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'service_location' => 'required|string|max:500',
            'special_requirements' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'listing_id.required' => 'Please select a service listing.',
            'listing_id.exists' => 'Selected service listing does not exist.',
            'booking_date.required' => 'Please select a booking date.',
            'booking_date.after_or_equal' => 'Booking date must be today or in the future.',
            'start_time.required' => 'Please specify the start time.',
            'start_time.date_format' => 'Start time must be in HH:MM format.',
            'end_time.required' => 'Please specify the end time.',
            'end_time.date_format' => 'End time must be in HH:MM format.',
            'end_time.after' => 'End time must be after start time.',
            'service_location.required' => 'Please specify the service location.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate minimum booking duration (at least 1 hour)
            if ($this->start_time && $this->end_time) {
                $start = Carbon::createFromFormat('H:i', $this->start_time);
                $end = Carbon::createFromFormat('H:i', $this->end_time);
                $hours = $end->diffInHours($start, false);

                if ($hours < 1) {
                    $validator->errors()->add('end_time', 'Booking must be at least 1 hour.');
                }

                if ($hours > 12) {
                    $validator->errors()->add('end_time', 'Booking cannot exceed 12 hours.');
                }
            }
        });
    }
}