<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUploadRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
            'document_type' => 'required|in:identity_proof,address_proof,certification,background_check,other',
            'document_name' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Please upload a document.',
            'document.file' => 'The uploaded file is invalid.',
            'document.mimes' => 'Document must be a PDF, JPG, JPEG, or PNG file.',
            'document.max' => 'Document size cannot exceed 5MB.',
            'document_type.required' => 'Please select a document type.',
            'document_type.in' => 'Invalid document type selected.',
            'document_name.required' => 'Please provide a document name.',
        ];
    }
}