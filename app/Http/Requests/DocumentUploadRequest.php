<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="DocumentUploadRequest",
 *     required={"document", "type"},
 *     @OA\Property(
 *         property="document",
 *         type="string",
 *         format="binary",
 *         description="Document file (PDF, JPG, JPEG, PNG - max 10MB)"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"identity", "certification", "background_check", "insurance"},
 *         example="identity",
 *         description="Type of document being uploaded"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         maxLength=500,
 *         example="Driver's License - Front",
 *         description="Optional description of the document"
 *     )
 * )
 */
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240' // 10MB
            ],
            'type' => [
                'required',
                'string',
                'in:identity,certification,background_check,insurance'
            ],
            'description' => [
                'nullable',
                'string',
                'max:500'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Please upload a document file.',
            'document.mimes' => 'Document must be a PDF, JPG, JPEG, or PNG file.',
            'document.max' => 'Document size must not exceed 10MB.',
            'type.required' => 'Document type is required.',
            'type.in' => 'Invalid document type. Must be one of: identity, certification, background_check, insurance.',
        ];
    }
}