<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class InquiryController extends Controller
{
    #[OA\Post(
        path: '/api/v1/inquiries',
        summary: 'Submit a new inquiry',
        description: 'Submit an inquiry or question to the support team',
        operationId: 'submitInquiry',
        tags: ['Inquiries']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'message'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
                new OA\Property(property: 'message', type: 'string', example: 'I have a question about my payout.')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Inquiry submitted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Thank you for your question! We will respond shortly.'),
                new OA\Property(property: 'data', type: 'object') 
                // In production, you might not want to return the whole inquiry object publicly, but for now we do.
            ]
        )
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $inquiry = Inquiry::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
            'status' => 'pending'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you for your question! We will respond shortly.',
            'data' => $inquiry
        ], 201);
    }
}
