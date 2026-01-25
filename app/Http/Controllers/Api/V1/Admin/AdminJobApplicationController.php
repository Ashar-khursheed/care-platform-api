<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class AdminJobApplicationController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/job-applications',
        summary: 'Get all job applications',
        tags: ['Admin - Jobs'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request)
    {
        $query = JobApplication::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $order  = $request->get('order', 'desc');

        $allowedSorts = [
            'first_name',
            'last_name',
            'email',
            'position',
            'experience',
            'availability',
            'created_at',
        ];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $order);

        $perPage = $request->get('per_page', 20);
        $applications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }
}
