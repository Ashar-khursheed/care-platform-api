<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AdminW9FormController extends Controller
{
    /**
     * List all W-9 form submissions with optional status filter.
     *
     * GET /api/v1/admin/w9-forms
     * Query params: status (pending|approved|rejected), per_page, user_id
     */
    #[OA\Get(
        path: '/api/v1/admin/w9-forms',
        summary: 'List all W-9 form submissions',
        description: 'Returns paginated list of W-9 form submissions. Filter by status: pending, approved, rejected.',
        operationId: 'adminListW9Forms',
        security: [['bearerAuth' => []]],
        tags: ['Admin - W9 Forms']
    )]
    #[OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected']))]
    #[OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    public function index(Request $request)
    {
        $query = ProfileDocument::with('user')
            ->where('document_type', 'w9_form')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('verification_status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = $request->get('per_page', 15);
        $documents = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'w9_forms' => $documents->map(fn($doc) => $this->formatDocument($doc)),
                'pagination' => [
                    'total'        => $documents->total(),
                    'per_page'     => $documents->perPage(),
                    'current_page' => $documents->currentPage(),
                    'last_page'    => $documents->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * List only pending W-9 forms.
     *
     * GET /api/v1/admin/w9-forms/pending
     */
    #[OA\Get(
        path: '/api/v1/admin/w9-forms/pending',
        summary: 'List pending W-9 form submissions',
        description: 'Returns all W-9 forms awaiting admin review.',
        operationId: 'adminPendingW9Forms',
        security: [['bearerAuth' => []]],
        tags: ['Admin - W9 Forms']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function pending(Request $request)
    {
        $perPage = $request->get('per_page', 15);

        $documents = ProfileDocument::with('user')
            ->where('document_type', 'w9_form')
            ->where('verification_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'w9_forms' => $documents->map(fn($doc) => $this->formatDocument($doc)),
                'pagination' => [
                    'total'        => $documents->total(),
                    'per_page'     => $documents->perPage(),
                    'current_page' => $documents->currentPage(),
                    'last_page'    => $documents->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * Get details of a single W-9 submission, including a pre-signed S3 URL.
     *
     * GET /api/v1/admin/w9-forms/{id}
     */
    #[OA\Get(
        path: '/api/v1/admin/w9-forms/{id}',
        summary: 'Get W-9 form details',
        description: 'Returns detailed information about a specific W-9 submission including a temporary download URL.',
        operationId: 'adminShowW9Form',
        security: [['bearerAuth' => []]],
        tags: ['Admin - W9 Forms']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function show($id)
    {
        $document = ProfileDocument::with(['user', 'verifier'])
            ->where('document_type', 'w9_form')
            ->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'W-9 form not found',
            ], 404);
        }

        // Generate a temporary pre-signed URL (valid for 60 minutes)
        $documentUrl = null;
        try {
            $documentUrl = Storage::disk('s3')->temporaryUrl(
                $document->document_path,
                now()->addMinutes(60)
            );
        } catch (\Exception $e) {
            // Fallback to public URL if temporaryUrl is not supported
            try {
                $documentUrl = Storage::disk('s3')->url($document->document_path);
            } catch (\Exception $ex) {
                $documentUrl = null;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'                  => $document->id,
                'user'                => [
                    'id'        => $document->user->id,
                    'name'      => $document->user->full_name,
                    'email'     => $document->user->email,
                    'user_type' => $document->user->user_type,
                ],
                'document_name'       => $document->document_name,
                'verification_status' => $document->verification_status,
                'rejection_reason'    => $document->rejection_reason,
                'document_url'        => $documentUrl,
                'verified_at'         => $document->verified_at?->format('Y-m-d H:i:s'),
                'verified_by'         => $document->verifier ? [
                    'id'   => $document->verifier->id,
                    'name' => $document->verifier->full_name,
                ] : null,
                'uploaded_at'         => $document->created_at->format('Y-m-d H:i:s'),
            ],
        ], 200);
    }

    /**
     * Approve a W-9 form submission.
     *
     * PUT /api/v1/admin/w9-forms/{id}/approve
     */
    #[OA\Put(
        path: '/api/v1/admin/w9-forms/{id}/approve',
        summary: 'Approve a W-9 form',
        description: 'Marks the W-9 form as approved. Optionally mark the user as w9_verified.',
        operationId: 'adminApproveW9Form',
        security: [['bearerAuth' => []]],
        tags: ['Admin - W9 Forms']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'W-9 form approved')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function approve(Request $request, $id)
    {
        $document = ProfileDocument::with('user')
            ->where('document_type', 'w9_form')
            ->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'W-9 form not found',
            ], 404);
        }

        if ($document->verification_status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'W-9 form is already approved',
            ], 409);
        }

        try {
            $document->update([
                'verification_status' => 'approved',
                'verified_at'         => now(),
                'verified_by'         => $request->user()->id,
                'rejection_reason'    => null,
            ]);

            // Optionally flag the user as w9 verified on their profile
            // This requires a `w9_verified` boolean column on users table.
            // If the column exists, update it:
            $user = $document->user;
            if (isset($user->w9_verified)) {
                $user->update(['w9_verified' => true]);
            }

            return response()->json([
                'success' => true,
                'message' => 'W-9 form approved successfully',
                'data'    => $this->formatDocument($document->refresh()),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve W-9 form',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a W-9 form submission with a reason.
     *
     * PUT /api/v1/admin/w9-forms/{id}/reject
     * Body: { "reason": "string" }
     */
    #[OA\Put(
        path: '/api/v1/admin/w9-forms/{id}/reject',
        summary: 'Reject a W-9 form',
        description: 'Marks the W-9 form as rejected. A rejection reason is required.',
        operationId: 'adminRejectW9Form',
        security: [['bearerAuth' => []]],
        tags: ['Admin - W9 Forms']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reason'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', description: 'Reason for rejection', example: 'Signature is missing on the form.')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'W-9 form rejected')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $document = ProfileDocument::with('user')
            ->where('document_type', 'w9_form')
            ->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'W-9 form not found',
            ], 404);
        }

        try {
            $document->update([
                'verification_status' => 'rejected',
                'rejection_reason'    => $request->reason,
                'verified_at'         => now(),
                'verified_by'         => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'W-9 form rejected',
                'data'    => $this->formatDocument($document->refresh()),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject W-9 form',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get overall W-9 submission statistics.
     *
     * GET /api/v1/admin/w9-forms/statistics
     */
    #[OA\Get(
        path: '/api/v1/admin/w9-forms/statistics',
        summary: 'W-9 form statistics',
        description: 'Returns counts of pending, approved, and rejected W-9 submissions.',
        operationId: 'adminW9FormStatistics',
        security: [['bearerAuth' => []]],
        tags: ['Admin - W9 Forms']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function statistics()
    {
        $total    = ProfileDocument::where('document_type', 'w9_form')->count();
        $pending  = ProfileDocument::where('document_type', 'w9_form')->where('verification_status', 'pending')->count();
        $approved = ProfileDocument::where('document_type', 'w9_form')->where('verification_status', 'approved')->count();
        $rejected = ProfileDocument::where('document_type', 'w9_form')->where('verification_status', 'rejected')->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'total'    => $total,
                'pending'  => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
            ],
        ], 200);
    }

    // -----------------------------------------------
    // Private Helpers
    // -----------------------------------------------

    /**
     * Format a ProfileDocument model into the standard W-9 response shape.
     */
    private function formatDocument(ProfileDocument $doc): array
    {
        return [
            'id'                  => $doc->id,
            'user'                => [
                'id'        => $doc->user->id,
                'name'      => $doc->user->full_name,
                'email'     => $doc->user->email,
                'user_type' => $doc->user->user_type,
            ],
            'document_name'       => $doc->document_name,
            'verification_status' => $doc->verification_status,
            'rejection_reason'    => $doc->rejection_reason,
            'verified_at'         => $doc->verified_at?->format('Y-m-d H:i:s'),
            'uploaded_at'         => $doc->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
