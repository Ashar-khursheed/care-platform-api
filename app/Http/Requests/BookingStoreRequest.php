<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class BookingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isClient();
    }

    public function rules(): array
    {  
        return [
          

            'listing_id' => 'required|exists:service_listings,id',
            'booking_date' => 'required|date|after_or_equal:today',

            // Accept HH:MM OR HH:MM:SS
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',

            'service_location' => 'required|string|max:500',
            'special_requirements' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.date_format' => 'Start time must be in HH:MM:SS format.',
            'end_time.date_format' => 'End time must be in HH:MM:SS format.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->start_time && $this->end_time) {

                // Use parse to accept any valid time
                $start = Carbon::parse($this->start_time);
                $end = Carbon::parse($this->end_time);

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
