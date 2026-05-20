<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\HandlesLocationSearch;

class WorkerDiscoveryController extends Controller
{
    use HandlesLocationSearch;
    #[OA\Get(
        path: '/api/v1/workers/discovery',
        summary: 'Worker Discovery (for Employers)',
        description: 'Search and filter for qualified workers based on role, experience, and location.',
        operationId: 'workerDiscovery',
        tags: ['Discovery'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'search', in: 'query', description: 'Search keyword (searches first_name, last_name, bio, role, city, state, zipcode)', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'zip_code', in: 'query', description: 'Zip code for 30 km radius-based search', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'zipcode', in: 'query', description: 'Alias for zip_code', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'city', in: 'query', description: 'Filter by city', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'state', in: 'query', description: 'Filter by state (e.g. Texas)', required: false, schema: new OA\Schema(type: 'string'))]
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

        // Apply centralized location, search and radius filters for workers/users
        $query = $this->applyLocationSearch($query, $request, 'user');

        // Filter by verified status
        if ($request->boolean('verified_only')) {
            $query->where('is_verified', true);
        }

        // Filter by Availability (Simple check if they have settings)
        if ($request->has('available_now')) {
            $query->whereNotNull('availability_settings');
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
