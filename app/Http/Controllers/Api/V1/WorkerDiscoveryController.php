<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class WorkerDiscoveryController extends Controller
{
    #[OA\Get(
        path: '/api/v1/workers/discovery',
        summary: 'Worker Discovery (for Employers)',
        description: 'Search and filter for qualified workers based on role, experience, and location.',
        operationId: 'workerDiscovery',
        tags: ['Discovery'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function index(Request $request)
    {
        $query = User::query()
            ->where('user_type', 'provider')
            ->where('status', 'active');

        // Filter by Role
        if ($request->has('role')) {
            $query->where('desired_role', $request->role);
        }

        // Filter by City/Location
        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        // Filter by verified status
        if ($request->boolean('verified_only')) {
            $query->where('is_verified', true);
        }

        // Filter by Availability (Simple check if they have settings)
        if ($request->has('available_now')) {
            $query->whereNotNull('availability_settings');
        }

        // Search by name or bio
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%")
                  ->orWhere('desired_role', 'like', "%{$search}%");
            });
        }

        $workers = $query->orderBy('profile_completion_percentage', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => [
                'workers' => UserResource::collection($workers),
                'pagination' => [
                    'total' => $workers->total(),
                    'per_page' => $workers->perPage(),
                    'current_page' => $workers->currentPage(),
                    'last_page' => $workers->lastPage(),
                ]
            ]
        ], 200);
    }

    #[OA\Get(
        path: '/api/v1/workers/{id}',
        summary: 'Get worker details for employer',
        tags: ['Discovery']
    )]
    public function show($id)
    {
        $worker = User::where('user_type', 'provider')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($worker)
        ], 200);
    }
}
