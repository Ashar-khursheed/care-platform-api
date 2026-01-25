<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\ProfileDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

class AdminUserController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/dashboard',
        summary: 'Dashboard',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_clients' => User::where('user_type', 'client')->count(),
            'total_providers' => User::where('user_type', 'provider')->count(),
            'verified_users' => User::where('is_verified', true)->count(),
            'pending_verification' => User::where('status', 'pending_verification')->count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'pending_documents' => ProfileDocument::where('verification_status', 'pending')->count(),
        ];

        // Recent registrations (last 7 days)
        $recentRegistrations = User::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_registrations' => $recentRegistrations,
            ]
        ], 200);
    }

    #[OA\Get(
        path: '/api/v1/admin/users',
        summary: 'Get all users',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by user type
        if ($request->has('user_type') && $request->user_type) {
            $query->where('user_type', $request->user_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by verification
        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->is_verified);
        }

        // Search by name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'users' => UserResource::collection($users),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ]
        ], 200);
    }

    #[OA\Get(
        path: '/api/v1/admin/users/{id}',
        summary: 'Get user details',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get user's documents
        $documents = ProfileDocument::where('user_id', $id)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'documents' => $documents,
            ]
        ], 200);
    }

    #[OA\Put(
        path: '/api/v1/admin/users/{id}',
        summary: 'Update user details',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'first_name', type: 'string'),
                new OA\Property(property: 'last_name', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Update success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'phone' => "sometimes|string|unique:users,phone,{$id}",
            'user_type' => 'sometimes|in:client,provider,admin',
            'bio' => 'sometimes|string|max:1000',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'country' => 'sometimes|string',
            'zip_code' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => new UserResource($user->fresh())
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/users/{id}/verify',
        summary: 'Verify user',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'User verified successfully')]
    #[OA\Response(response: 404, description: 'User not found')]
    public function verifyUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        try {
            $user->update([
                'is_verified' => true,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            // TODO: Send verification email to user

            return response()->json([
                'success' => true,
                'message' => 'User verified successfully',
                'data' => new UserResource($user)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/users/{id}/suspend',
        summary: 'Suspend user',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function suspendUser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

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

        try {
            $user->update([
                'status' => 'suspended',
            ]);

            // TODO: Send suspension email with reason
            // TODO: Log suspension reason

            return response()->json([
                'success' => true,
                'message' => 'User suspended successfully',
                'data' => new UserResource($user)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/users/{id}/activate',
        summary: 'Activate user',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'User activated successfully')]
    #[OA\Response(response: 404, description: 'User not found')]
    public function activateUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        try {
            $user->update([
                'status' => 'active',
            ]);

            // TODO: Send activation email

            return response()->json([
                'success' => true,
                'message' => 'User activated successfully',
                'data' => new UserResource($user)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/admin/users/{id}',
        summary: 'Delete user',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Delete success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent deleting admin users
        if ($user->user_type === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin users'
            ], 403);
        }

        try {
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/users/{id}/reset-password',
        summary: 'Reset user password',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'password', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function resetPassword(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Revoke all user tokens
            $user->tokens()->delete();

            // TODO: Send password reset notification email

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
