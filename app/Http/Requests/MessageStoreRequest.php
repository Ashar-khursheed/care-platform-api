<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization checked in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'receiver_id' => 'required|integer|exists:users,id',
            'booking_id' => 'nullable|integer|exists:bookings,id',
            'message' => 'required_without:attachment|string|max:5000',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'receiver_id.required' => 'Receiver is required.',
            'receiver_id.exists' => 'Receiver not found.',
            'message.required_without' => 'Message or attachment is required.',
            'message.max' => 'Message cannot exceed 5000 characters.',
            'attachment.max' => 'Attachment size cannot exceed 10MB.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Cannot message yourself
            if ($this->receiver_id == $this->user()->id) {
                $validator->errors()->add(
                    'receiver_id',
                    'You cannot send messages to yourself.'
                );
            }
        });
    }
}