<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileDocumentResource;
use App\Models\ProfileDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdminDocumentController extends Controller
{
        /**
         * @OA\Get(
         *     path="/v1/admin/documents/pending",
         *     operationId="admindocumentsGetpendingdocuments",
         *     tags={"Admin - Documents"},
         *     summary="Get pending",
         *     security={{"bearerAuth":{}}},
         *     @OA\Response(response=200, description="Success"),
         *     @OA\Response(response=401, description="Unauthorized"),
         *     @OA\Response(response=404, description="Not found")
         * )
         */
    public function getPendingDocuments(Request $request)
    {
        $query = ProfileDocument::with('user')
            ->where('verification_status', 'pending')
            ->orderBy('created_at', 'desc');

        // Filter by document type
        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        $perPage = $request->get('per_page', 15);
        $documents = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'documents' => $documents->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'user' => [
                            'id' => $doc->user->id,
                            'name' => $doc->user->full_name,
                            'email' => $doc->user->email,
                            'user_type' => $doc->user->user_type,
                        ],
                        'document_type' => $doc->document_type,
                        'document_name' => $doc->document_name,
                        'verification_status' => $doc->verification_status,
                        'uploaded_at' => $doc->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'pagination' => [
                    'total' => $documents->total(),
                    'per_page' => $documents->perPage(),
                    'current_page' => $documents->currentPage(),
                    'last_page' => $documents->lastPage(),
                ]
            ]
        ], 200);
    }

    /**
     * Get all documents with filters
     */
    public function getAllDocuments(Request $request)
    {
        $query = ProfileDocument::with('user');

        // Filter by status
        if ($request->has('status')) {
            $query->where('verification_status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by document type
        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $documents = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'documents' => $documents->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'user' => [
                            'id' => $doc->user->id,
                            'name' => $doc->user->full_name,
                            'email' => $doc->user->email,
                        ],
                        'document_type' => $doc->document_type,
                        'document_name' => $doc->document_name,
                        'verification_status' => $doc->verification_status,
                        'rejection_reason' => $doc->rejection_reason,
                        'verified_at' => $doc->verified_at?->format('Y-m-d H:i:s'),
                        'uploaded_at' => $doc->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'pagination' => [
                    'total' => $documents->total(),
                    'per_page' => $documents->perPage(),
                    'current_page' => $documents->currentPage(),
                    'last_page' => $documents->lastPage(),
                ]
            ]
        ], 200);
    }

    /**
     * View document details and download
     */
    public function show($id)
    {
        $document = ProfileDocument::with('user')->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        // Get file path
        $filePath = $document->document_path;
        
        // Check if file exists
        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found'
            ], 404);
        }

        // Get file content
        $fileContent = Storage::disk('local')->get($filePath);
        $mimeType = Storage::disk('local')->mimeType($filePath);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $document->id,
                'user' => [
                    'id' => $document->user->id,
                    'name' => $document->user->full_name,
                    'email' => $document->user->email,
                    'user_type' => $document->user->user_type,
                ],
                'document_type' => $document->document_type,
                'document_name' => $document->document_name,
                'verification_status' => $document->verification_status,
                'rejection_reason' => $document->rejection_reason,
                'verified_at' => $document->verified_at?->format('Y-m-d H:i:s'),
                'uploaded_at' => $document->created_at->format('Y-m-d H:i:s'),
                'file_info' => [
                    'mime_type' => $mimeType,
                    'size' => Storage::disk('local')->size($filePath),
                ],
            ]
        ], 200);
    }

    /**
     * Download document file
     */
    public function download($id)
    {
        $document = ProfileDocument::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        $filePath = $document->document_path;

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found'
            ], 404);
        }

        return Storage::disk('local')->download($filePath, $document->document_name);
    }

        /**
 *     @OA\Put(
 *         path="/api/v1/admin/documents/{id}/approve",
 *         summary="Approve document",
 *         tags={""},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The id of the resource",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function approve(Request $request, $id)
    {
        $document = ProfileDocument::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        try {
            $document->update([
                'verification_status' => 'approved',
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
                'rejection_reason' => null,
            ]);

            // Check if user should be fully verified
            $this->checkUserVerification($document->user_id);

            // TODO: Send approval notification to user

            return response()->json([
                'success' => true,
                'message' => 'Document approved successfully',
                'data' => new ProfileDocumentResource($document)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
 *     @OA\Put(
 *         path="/api/v1/admin/documents/{id}/reject",
 *         summary="Reject document",
 *         tags={""},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The id of the resource",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $document = ProfileDocument::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        try {
            $document->update([
                'verification_status' => 'rejected',
                'rejection_reason' => $request->reason,
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
            ]);

            // TODO: Send rejection notification to user with reason

            return response()->json([
                'success' => true,
                'message' => 'Document rejected',
                'data' => new ProfileDocumentResource($document)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
 *     @OA\Delete(
 *         path="/api/v1/admin/documents/{id}",
 *         summary="Delete document",
 *         tags={""},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The id of the resource",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function destroy($id)
    {
        $document = ProfileDocument::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        try {
            // Delete file from storage
            if (Storage::disk('local')->exists($document->document_path)) {
                Storage::disk('local')->delete($document->document_path);
            }

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
     * Check if user should be fully verified
     */
    private function checkUserVerification($userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return;
        }

        // Check if user has at least one approved identity document
        $hasIdentityProof = ProfileDocument::where('user_id', $userId)
            ->where('document_type', 'identity_proof')
            ->where('verification_status', 'approved')
            ->exists();

        // If user is a provider, check for certifications
        if ($user->user_type === 'provider') {
            $hasCertification = ProfileDocument::where('user_id', $userId)
                ->whereIn('document_type', ['certification', 'background_check'])
                ->where('verification_status', 'approved')
                ->exists();

            if ($hasIdentityProof && $hasCertification) {
                $user->update([
                    'is_verified' => true,
                    'status' => 'active',
                ]);
            }
        } else {
            // For clients, just identity proof is enough
            if ($hasIdentityProof) {
                $user->update([
                    'is_verified' => true,
                    'status' => 'active',
                ]);
            }
        }
    }
}
