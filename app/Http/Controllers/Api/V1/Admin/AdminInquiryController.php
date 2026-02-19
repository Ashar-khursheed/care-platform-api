<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AdminInquiryController extends Controller
{
    /**
     * List all inquiries with pagination and filtering.
     */
    #[OA\Get(
        path: '/api/v1/admin/inquiries',
        summary: 'List Inquiries',
        description: 'Get a paginated list of all inquiries (admin only)',
        operationId: 'getAdminInquiries',
        security: [['bearerAuth' => []]],
        tags: ['Admin Inquiries']
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Page number',
        required: false,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter by status (pending, resolved)',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['pending', 'resolved'])
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Search string',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'List of inquiries',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'data', type: 'object') // Paginated result
            ]
        )
    )]
    public function index(Request $request)
    {
        $query = Inquiry::query();

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['pending', 'resolved'])) {
            $query->where('status', $request->status);
        }

        // Search by name, email, or message
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $inquiries = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $inquiries
        ]);
    }

    /**
     * Show a specific inquiry.
     */
    #[OA\Get(
        path: '/api/v1/admin/inquiries/{id}',
        summary: 'Get Inquiry Details',
        description: 'Get details of a specific inquiry',
        operationId: 'getAdminInquiry',
        security: [['bearerAuth' => []]],
        tags: ['Admin Inquiries']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Inquiry details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Inquiry not found')]
    public function show($id)
    {
        $inquiry = Inquiry::with('responder')->find($id);

        if (!$inquiry) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inquiry not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $inquiry
        ]);
    }

    /**
     * Reply to an inquiry (mark as resolved).
     * Note: In a real system, this would likely send an email.
     */
    #[OA\Post(
        path: '/api/v1/admin/inquiries/{id}/reply',
        summary: 'Reply to Inquiry',
        description: 'Reply to an inquiry and mark it as resolved',
        operationId: 'replyAdminInquiry',
        security: [['bearerAuth' => []]],
        tags: ['Admin Inquiries']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['response'],
            properties: [
                new OA\Property(property: 'response', type: 'string', example: 'Hello, here is the answer to your question...')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Replied successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Inquiry resolved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[OA\Response(response: 404, description: 'Inquiry not found')]
    public function reply(Request $request, $id)
    {
        $inquiry = Inquiry::find($id);

        if (!$inquiry) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inquiry not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'response' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $inquiry->update([
            'admin_response' => $request->response,
            'status' => 'resolved',
            'responded_by' => auth()->id(),
            'responded_at' => now()
        ]);

        // TODO: Send email to user with the response

        return response()->json([
            'status' => 'success',
            'message' => 'Inquiry resolved successfully',
            'data' => $inquiry
        ]);
    }

    /**
     * Delete an inquiry.
     */
    #[OA\Delete(
        path: '/api/v1/admin/inquiries/{id}',
        summary: 'Delete Inquiry',
        description: 'Delete an inquiry record',
        operationId: 'deleteAdminInquiry',
        security: [['bearerAuth' => []]],
        tags: ['Admin Inquiries']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Inquiry deleted',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Inquiry deleted successfully')
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Inquiry not found')]
    public function destroy($id)
    {
        $inquiry = Inquiry::find($id);

        if (!$inquiry) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inquiry not found'
            ], 404);
        }

        $inquiry->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Inquiry deleted successfully'
        ]);
    }
}
