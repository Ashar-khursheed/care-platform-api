<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminInquiryController extends Controller
{
    /**
     * List all inquiries with pagination and filtering.
     */
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
