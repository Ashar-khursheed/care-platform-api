<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListingStoreRequest;
use App\Http\Resources\ListingResource;
use App\Models\ServiceListing;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class JobController extends Controller
{
    #[OA\Get(
        path: '/api/v1/jobs',
        summary: 'Get all jobs (for providers)',
        tags: ['Jobs']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function index(Request $request)
    {
        // Jobs are listings where the "provider" (owner) is actually a client or user asking for help
        // For now, we assume any listing created via this endpoint or distinguished by some logic is a job
        // We can filter by user_type of the provider relation
        
        $query = ServiceListing::with(['category', 'provider'])
            ->whereHas('provider', function($q) {
                $q->where('user_type', 'client');
            })
            ->active();

        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('location')) {
            $query->where('service_location', 'like', "%{$request->location}%");
        }

        $jobs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => [
                'jobs' => ListingResource::collection($jobs),
                'pagination' => [
                    'total' => $jobs->total(),
                    'per_page' => $jobs->perPage(),
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                ]
            ]
        ], 200);
    }

    #[OA\Post(
        path: '/api/v1/jobs',
        summary: 'Create a new job request',
        security: [['bearerAuth' => []]],
        tags: ['Jobs']
    )]
    #[OA\Response(response: 201, description: 'Created')]
    public function store(ListingStoreRequest $request)
    {
        // Reuse ListingStoreRequest as validation rules are similar
        // Ensure the user is a client (or allow any authenticated user to post a job?)
        // For now, assuming any auth user can post
        
        try {
            $job = ServiceListing::create([
                'provider_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'hourly_rate' => $request->hourly_rate, // Budget
                'years_of_experience' => 0, 
                'skills' => $request->skills,
                'languages' => $request->languages,
                'availability' => $request->availability, // Timings
                'service_location' => $request->service_location,
                'service_radius' => $request->service_radius,
                'is_available' => true,
                'status' => 'active', // Auto-approve for now or pending
            ]);

            $job->load(['category', 'provider']);

            return response()->json([
                'success' => true,
                'message' => 'Job posted successfully',
                'data' => new ListingResource($job)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to post job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/my-jobs',
        summary: 'Get my posted jobs',
        security: [['bearerAuth' => []]],
        tags: ['Jobs']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function myJobs(Request $request)
    {
        $jobs = ServiceListing::with(['category', 'bids.provider'])
            ->where('provider_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => [
                'jobs' => ListingResource::collection($jobs),
                'pagination' => [
                    'total' => $jobs->total(),
                    'per_page' => $jobs->perPage(),
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                ]
            ]
        ], 200);
    }
    
    #[OA\Get(
        path: '/api/v1/jobs/{id}',
        summary: 'Get job details',
        tags: ['Jobs']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function show($id)
    {
        $job = ServiceListing::with(['category', 'provider', 'bids.provider'])->find($id);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ListingResource($job)
        ], 200);
    }
}
