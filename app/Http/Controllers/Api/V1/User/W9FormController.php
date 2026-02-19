<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\ProfileDocument;
use OpenApi\Attributes as OA;

class W9FormController extends Controller
{
    #[OA\Get(
        path: '/api/v1/w9-form/download',
        summary: 'Download Blank W-9 Form',
        description: 'Download the blank W-9 PDF form for filling out',
        operationId: 'downloadW9Form',
        tags: ['W-9 Form']
    )]
    #[OA\Response(
        response: 200,
        description: 'PDF File Download',
        content: new OA\MediaType(
            mediaType: 'application/pdf',
            schema: new OA\Schema(type: 'string', format: 'binary')
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Form not found'
    )]
    public function downloadBlankForm()
    {
        // Path to the blank W-9 form (stored in public disk or S3 "public" folder)
        // Assuming it's in `storage/app/public/documents/form-w9.pdf`
        $path = 'documents/form-w9.pdf';
        
        // Ensure the file exists in the public disk
        if (!Storage::disk('public')->exists($path)) {
            // Log issue or return error if file missing
             return response()->json([
                'status' => 'error',
                'message' => 'W-9 Form not available for download at this time.'
            ], 404);
        }

        return Storage::disk('public')->download($path, 'Form-W9.pdf');
    }

    #[OA\Post(
        path: '/api/v1/profile/w9-form',
        summary: 'Upload Filled W-9 Form',
        description: 'Upload the filled W-9 PDF form',
        operationId: 'uploadW9Form',
        security: [['bearerAuth' => []]],
        tags: ['W-9 Form']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['document'],
                properties: [
                    new OA\Property(
                        property: 'document',
                        description: 'The filled W-9 PDF file',
                        type: 'string',
                        format: 'binary'
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Upload successful',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'W-9 Form uploaded successfully.'),
                new OA\Property(property: 'data', type: 'object') // refine if needed
            ]
        )
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function uploadFilledForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        
        // Upload to S3 (or default disk)
        $path = $request->file('document')->store('documents/w9', 's3');
        $filename = $request->file('document')->getClientOriginalName();

        // Save to ProfileDocument
        $document = ProfileDocument::updateOrCreate(
            [
                'user_id' => $user->id,
                'document_type' => 'w9_form' 
            ],
            [
                'document_name' => $filename,
                'document_path' => $path,
                'verification_status' => 'pending',
                'rejection_reason' => null,
                'verified_at' => null,
                'verified_by' => null,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'W-9 Form uploaded successfully.',
            'data' => $document
        ]);
    }

    #[OA\Get(
        path: '/api/v1/profile/w9-form/status',
        summary: 'Get W-9 Form Status',
        description: 'Get the status of the W-9 form submission',
        operationId: 'getW9FormStatus',
        security: [['bearerAuth' => []]],
        tags: ['W-9 Form']
    )]
    #[OA\Response(
        response: 200,
        description: 'Status retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'submitted', type: 'boolean', example: true),
                        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'not_submitted'], example: 'pending'),
                        new OA\Property(property: 'document_url', type: 'string', format: 'url', nullable: true),
                        new OA\Property(property: 'rejection_reason', type: 'string', nullable: true)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function status()
    {
        $user = auth()->user();
        $document = ProfileDocument::where('user_id', $user->id)
            ->where('document_type', 'w9_form')
            ->latest()
            ->first();

        if (!$document) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'submitted' => false,
                    'status' => 'not_submitted'
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'submitted' => true,
                'status' => $document->verification_status, // pending, approved, rejected
                'document_url' => Storage::disk('s3')->url($document->document_path),
                'rejection_reason' => $document->rejection_reason
            ]
        ]);
    }
}
