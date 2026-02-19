<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\ProfileDocument;

class W9FormController extends Controller
{
    /**
     * Download the blank W-9 form.
     */
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

    /**
     * Upload the filled W-9 form.
     */
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
        // Check if existing W9 exists, if so, update or create new? 
        // Typically we replace the old one or keep history. Let's create new for now or update if unique constraint.
        // The schema doesn't enforce uniqueness on type per user, so we can stick to creating new or updating.
        // Let's check for existing and update status to pending.

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

    /**
     * Get the status of the W-9 form submission.
     */
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
