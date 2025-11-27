<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentUploadRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\ProfileDocumentResource;
use App\Http\Resources\UserResource;
use App\Models\ProfileDocument;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    /**
     * Get authenticated user profile
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => new UserResource($user)
        ], 200);
    }

    /**
     * Update user profile
     */
    public function update(ProfileUpdateRequest $request)
    {
        $user = $request->user();

        try {
            $user->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => new UserResource($user->fresh())
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload profile photo
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        $user = $request->user();

        try {
            // Delete old photo if exists
            if ($user->profile_photo) {
                $this->imageUploadService->deleteFile($user->profile_photo, 'public');
            }

            // Upload new photo
            $path = $this->imageUploadService->uploadProfilePhoto($request->file('photo'), $user->id);

            // Update user record
            $user->update(['profile_photo' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo uploaded successfully',
                'data' => [
                    'profile_photo' => url('storage/' . $path)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete profile photo
     */
    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        try {
            if ($user->profile_photo) {
                // Delete file from storage
                $this->imageUploadService->deleteFile($user->profile_photo, 'public');

                // Update user record
                $user->update(['profile_photo' => null]);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo deleted successfully'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'No profile photo to delete'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload verification document
     */
    public function uploadDocument(DocumentUploadRequest $request)
    {
        $user = $request->user();

        try {
            // Upload document
            $path = $this->imageUploadService->uploadDocument(
                $request->file('document'),
                $user->id,
                $request->document_type
            );

            // Create document record
            $document = ProfileDocument::create([
                'user_id' => $user->id,
                'document_type' => $request->document_type,
                'document_name' => $request->document_name,
                'document_path' => $path,
                'verification_status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully. Verification pending.',
                'data' => new ProfileDocumentResource($document)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all verification documents
     */
    public function getDocuments(Request $request)
    {
        $user = $request->user();
        $documents = ProfileDocument::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProfileDocumentResource::collection($documents)
        ], 200);
    }

    /**
     * Delete verification document
     */
    public function deleteDocument(Request $request, $id)
    {
        $user = $request->user();

        try {
            $document = ProfileDocument::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            // Cannot delete approved documents
            if ($document->verification_status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete approved documents'
                ], 403);
            }

            // Delete file from storage
            $this->imageUploadService->deleteFile($document->document_path, 'local');

            // Delete record
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get verification status
     */
    public function verificationStatus(Request $request)
    {
        $user = $request->user();
        
        $documents = ProfileDocument::where('user_id', $user->id)->get();
        
        $stats = [
            'total_documents' => $documents->count(),
            'pending' => $documents->where('verification_status', 'pending')->count(),
            'approved' => $documents->where('verification_status', 'approved')->count(),
            'rejected' => $documents->where('verification_status', 'rejected')->count(),
            'is_fully_verified' => $user->is_verified,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user_status' => $user->status,
                'is_verified' => $user->is_verified,
                'email_verified' => $user->email_verified_at !== null,
                'phone_verified' => $user->phone_verified_at !== null,
                'document_stats' => $stats,
                'documents' => ProfileDocumentResource::collection($documents)
            ]
        ], 200);
    }
}