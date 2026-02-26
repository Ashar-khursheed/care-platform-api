<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AdminWithdrawalController extends Controller
{
    public function __construct(protected WithdrawalService $withdrawalService)
    {
    }

    /**
     * GET /v1/admin/withdrawals
     * List all withdrawal requests — filterable by status, provider, date.
     */
    #[OA\Get(
        path: '/api/v1/admin/withdrawals',
        summary: 'List all withdrawal requests (Admin)',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Withdrawals']
    )]
    #[OA\Parameter(name: 'escrow_status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['holding', 'released', 'disputed']))]
    #[OA\Parameter(name: 'withdrawal_status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['none', 'requested', 'approved', 'rejected', 'paid', 'cancelled']))]
    #[OA\Parameter(name: 'provider_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'created_at'))]
    #[OA\Parameter(name: 'sort_order', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Response(response: 200, description: 'Success')]
    public function index(Request $request)
    {
        $query = WithdrawalRequest::with([
            'provider:id,first_name,last_name,email',
            'booking:id,booking_date,total_amount,status',
        ]);

        if ($request->filled('escrow_status')) {
            $query->where('escrow_status', $request->escrow_status);
        }

        if ($request->filled('withdrawal_status')) {
            $query->where('withdrawal_status', $request->withdrawal_status);
        }

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $sortBy    = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 20);
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'withdrawals' => $results->items(),
                'pagination'  => [
                    'total'        => $results->total(),
                    'per_page'     => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page'    => $results->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * GET /v1/admin/withdrawals/statistics
     * Overall platform withdrawal stats + commission totals.
     */
    #[OA\Get(
        path: '/api/v1/admin/withdrawals/statistics',
        summary: 'Platform withdrawal statistics and commission totals',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Withdrawals']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function statistics()
    {
        $stats = [
            // Count by escrow state
            'escrow_holding_count'    => WithdrawalRequest::holding()->count(),
            'escrow_released_count'   => WithdrawalRequest::released()->count(),

            // Count by withdrawal state
            'pending_withdrawal_count'=> WithdrawalRequest::pendingWithdrawal()->count(),
            'paid_count'              => WithdrawalRequest::paid()->count(),
            'rejected_count'          => WithdrawalRequest::where('withdrawal_status', 'rejected')->count(),
            'total_count'             => WithdrawalRequest::count(),

            // Amount breakdowns
            'pending_withdrawal_amount'    => (float) WithdrawalRequest::pendingWithdrawal()->sum('net_provider_amount'),
            'total_paid_out'               => (float) WithdrawalRequest::paid()->sum('net_provider_amount'),
            'total_in_escrow'              => (float) WithdrawalRequest::holding()->sum('net_provider_amount'),

            // Commission totals (platform earnings)
            'total_client_fees_collected'  => (float) WithdrawalRequest::released()->sum('client_fee') +
                                              (float) WithdrawalRequest::paid()->sum('client_fee'),
            'total_provider_fees_collected'=> (float) WithdrawalRequest::released()->sum('provider_fee') +
                                              (float) WithdrawalRequest::paid()->sum('provider_fee'),
            'total_platform_commission'    => (float) WithdrawalRequest::whereIn('withdrawal_status', ['paid'])
                                                ->sum('platform_fee_total'),

            // Auto-release overdue
            'overdue_auto_release_count'   => WithdrawalRequest::eligibleForAutoRelease()->count(),

            'currency'                => 'USD',
        ];

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * GET /v1/admin/withdrawals/history
     * Full paginated history view (all time).
     */
    #[OA\Get(
        path: '/api/v1/admin/withdrawals/history',
        summary: 'Full withdrawal history',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Withdrawals']
    )]
    #[OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Response(response: 200, description: 'Success')]
    public function history(Request $request)
    {
        $query = WithdrawalRequest::with([
            'provider:id,first_name,last_name,email',
            'booking:id,booking_date,total_amount',
            'approvedByAdmin:id,first_name,last_name',
        ])->orderBy('created_at', 'desc');

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = $request->get('per_page', 20);
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'history'    => $results->items(),
                'pagination' => [
                    'total'        => $results->total(),
                    'per_page'     => $results->perPage(),
                    'current_page' => $results->currentPage(),
                    'last_page'    => $results->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * GET /v1/admin/withdrawals/commission-report
     * Commission analytics — groupable by month, week, or day.
     */
    #[OA\Get(
        path: '/api/v1/admin/withdrawals/commission-report',
        summary: 'Commission analytics report',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Withdrawals']
    )]
    #[OA\Parameter(name: 'group_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['day', 'week', 'month'], default: 'month'))]
    #[OA\Response(response: 200, description: 'Success')]
    public function commissionReport(Request $request)
    {
        $groupBy = $request->get('group_by', 'month'); // month | week | day

        $dateFormat = match($groupBy) {
            'day'   => '%Y-%m-%d',
            'week'  => '%x-W%v',
            default => '%Y-%m',
        };

        $report = WithdrawalRequest::whereIn('withdrawal_status', ['paid'])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as total_withdrawals'),
                DB::raw('SUM(gross_amount) as total_gross'),
                DB::raw('SUM(client_fee) as total_client_fees'),
                DB::raw('SUM(provider_fee) as total_provider_fees'),
                DB::raw('SUM(platform_fee_total) as total_platform_commission'),
                DB::raw('SUM(net_provider_amount) as total_paid_to_providers'),
            )
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->get();

        $overall = [
            'total_paid_withdrawals'       => (int) WithdrawalRequest::paid()->count(),
            'total_gross_processed'        => (float) WithdrawalRequest::paid()->sum('gross_amount'),
            'total_client_fees'            => (float) WithdrawalRequest::paid()->sum('client_fee'),
            'total_provider_fees'          => (float) WithdrawalRequest::paid()->sum('provider_fee'),
            'total_platform_commission'    => (float) WithdrawalRequest::paid()->sum('platform_fee_total'),
            'total_paid_to_providers'      => (float) WithdrawalRequest::paid()->sum('net_provider_amount'),
            'commission_rate_client'       => '10%',
            'commission_rate_provider'     => '10%',
            'currency'                     => 'USD',
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'overall' => $overall,
                'report'  => $report,
                'group_by'=> $groupBy,
            ],
        ]);
    }

    /**
     * GET /v1/admin/withdrawals/{id}
     * View a single withdrawal request in full detail.
     */
    #[OA\Get(
        path: '/api/v1/admin/withdrawals/{id}',
        summary: 'Get single withdrawal request details',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Withdrawals']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function show(int $id)
    {
        $withdrawal = WithdrawalRequest::with([
            'provider:id,first_name,last_name,email,phone',
            'booking',
            'approvedByAdmin:id,first_name,last_name',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $withdrawal,
        ]);
    }

    /**
     * POST /v1/admin/withdrawals/{id}/approve
     * Admin manually approves and releases funds to the provider.
     */
    #[OA\Post(
        path: '/api/v1/admin/withdrawals/{id}/approve',
        summary: 'Approve withdrawal request and release escrow',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Withdrawals']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'transaction_reference', type: 'string', example: 'WIRE-123'),
                new OA\Property(property: 'notes', type: 'string', example: 'Sent via wire transfer')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Bad Request - Cannot approve in current state')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function approve(Request $request, int $id)
    {
        $withdrawal = WithdrawalRequest::findOrFail($id);

        if (!in_array($withdrawal->withdrawal_status, ['requested', 'none'])) {
            return response()->json([
                'success' => false,
                'message' => "Cannot approve. Current withdrawal_status is '{$withdrawal->withdrawal_status}'. Only 'requested' or 'none' can be approved.",
            ], 400);
        }

        $request->validate([
            'transaction_reference' => 'sometimes|string|max:255',
            'notes'                 => 'sometimes|string|max:1000',
        ]);

        try {
            $updated = $this->withdrawalService->approveWithdrawal(
                $withdrawal,
                $request->user(),
                $request->only(['transaction_reference', 'notes'])
            );

            return response()->json([
                'success' => true,
                'message' => "Withdrawal approved. \${$updated->net_provider_amount} released to provider #{$updated->provider_id}.",
                'data'    => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve withdrawal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /v1/admin/withdrawals/{id}/reject
     * Admin rejects a withdrawal request (funds remain in escrow until auto-release).
     */
    #[OA\Post(
        path: '/api/v1/admin/withdrawals/{id}/reject',
        summary: 'Reject withdrawal request',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Withdrawals']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reason'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Invalid bank account')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Bad Request - Cannot reject in current state')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function reject(Request $request, int $id)
    {
        $withdrawal = WithdrawalRequest::findOrFail($id);

        if ($withdrawal->withdrawal_status !== 'requested') {
            return response()->json([
                'success' => false,
                'message' => "Only 'requested' withdrawals can be rejected. Current status: '{$withdrawal->withdrawal_status}'.",
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $updated = $this->withdrawalService->rejectWithdrawal(
                $withdrawal,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request rejected. Provider can re-submit or funds will auto-release.',
                'data'    => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject withdrawal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /v1/admin/withdrawals/bulk-approve
     * Approve multiple withdrawal requests at once.
     */
    #[OA\Post(
        path: '/api/v1/admin/withdrawals/bulk-approve',
        summary: 'Bulk approve withdrawal requests',
        security: [['bearerAuth' => []]],
        tags: ['Admin - Withdrawals']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['withdrawal_ids'],
            properties: [
                new OA\Property(
                    property: 'withdrawal_ids',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [1, 2, 3]
                ),
                new OA\Property(property: 'transaction_reference', type: 'string', example: 'BATCH-123'),
                new OA\Property(property: 'notes', type: 'string', example: 'Batch processed')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 422, description: 'Validation Error')]
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'withdrawal_ids'        => 'required|array|min:1',
            'withdrawal_ids.*'      => 'integer|exists:withdrawal_requests,id',
            'transaction_reference' => 'sometimes|string|max:255',
            'notes'                 => 'sometimes|string|max:1000',
        ]);

        $withdrawals = WithdrawalRequest::whereIn('id', $request->withdrawal_ids)
            ->whereIn('withdrawal_status', ['requested', 'none'])
            ->get();

        if ($withdrawals->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No approvable withdrawals found with the given IDs.',
            ], 400);
        }

        $approved = 0;
        $failed   = 0;
        $errors   = [];

        foreach ($withdrawals as $withdrawal) {
            try {
                $this->withdrawalService->approveWithdrawal(
                    $withdrawal,
                    $request->user(),
                    $request->only(['transaction_reference', 'notes'])
                );
                $approved++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Withdrawal #{$withdrawal->id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$approved} withdrawals approved, {$failed} failed.",
            'data'    => [
                'approved_count' => $approved,
                'failed_count'   => $failed,
                'errors'         => $errors,
            ],
        ]);
    }
}
