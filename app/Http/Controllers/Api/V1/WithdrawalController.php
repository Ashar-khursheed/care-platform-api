<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class WithdrawalController extends Controller
{
    public function __construct(protected WithdrawalService $withdrawalService)
    {
    }

    /**
     * GET /v1/withdrawals/balance
     * Provider's earnings breakdown: in escrow, available, pending withdrawal, total paid.
     */
    #[OA\Get(
        path: '/api/v1/withdrawals/balance',
        summary: 'Get provider withdrawal balance and escrow breakdown',
        security: [['bearerAuth' => []]],
        tags: ['Withdrawals']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 403, description: 'Unauthorized - Only providers')]
    public function balance(Request $request)
    {
        $user = $request->user();

        if (!$user->isProvider()) {
            return response()->json(['success' => false, 'message' => 'Only providers can view withdrawal balance'], 403);
        }

        $balance = $this->withdrawalService->getProviderBalance($user->id);

        // Commission info for the provider
        $balance['commission_info'] = [
            'client_fee_percent'   => '10%',
            'provider_fee_percent' => '10%',
            'escrow_window_days'   => 7,
            'description'          => 'Platform charges 10% from the client and 10% from the provider. Funds are held in escrow for 7 days after job completion.',
        ];

        return response()->json([
            'success' => true,
            'data'    => $balance,
        ]);
    }

    /**
     * GET /v1/withdrawals
     * List all withdrawal requests for the authenticated provider (paginated).
     */
    #[OA\Get(
        path: '/api/v1/withdrawals',
        summary: 'List provider withdrawals',
        security: [['bearerAuth' => []]],
        tags: ['Withdrawals']
    )]
    #[OA\Parameter(name: 'escrow_status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['holding', 'released', 'disputed']))]
    #[OA\Parameter(name: 'withdrawal_status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['none', 'requested', 'approved', 'rejected', 'paid', 'cancelled']))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 403, description: 'Unauthorized - Only providers')]
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isProvider()) {
            return response()->json(['success' => false, 'message' => 'Only providers can view withdrawals'], 403);
        }

        $query = WithdrawalRequest::byProvider($user->id)
            ->with(['booking:id,booking_date,total_amount,status']);

        // Filter by escrow_status
        if ($request->filled('escrow_status')) {
            $query->where('escrow_status', $request->escrow_status);
        }

        // Filter by withdrawal_status
        if ($request->filled('withdrawal_status')) {
            $query->where('withdrawal_status', $request->withdrawal_status);
        }

        $query->orderBy('created_at', 'desc');
        $perPage = $request->get('per_page', 10);
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
     * GET /v1/withdrawals/history
     * Full withdrawal history (Upwork-style transaction history view).
     */
    #[OA\Get(
        path: '/api/v1/withdrawals/history',
        summary: 'Get provider withdrawal history and summary',
        security: [['bearerAuth' => []]],
        tags: ['Withdrawals']
    )]
    #[OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 403, description: 'Unauthorized - Only providers')]
    public function history(Request $request)
    {
        $user = $request->user();

        if (!$user->isProvider()) {
            return response()->json(['success' => false, 'message' => 'Only providers can view history'], 403);
        }

        $query = WithdrawalRequest::byProvider($user->id)
            ->with(['booking:id,booking_date,total_amount,status,client_id'])
            ->orderBy('created_at', 'desc');

        // Date range filter
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = $request->get('per_page', 20);
        $results = $query->paginate($perPage);

        // Compute summary totals for the history view
        $allForProvider = WithdrawalRequest::byProvider($user->id);
        $summary = [
            'total_gross_earned'      => (float) (clone $allForProvider)->sum('gross_amount'),
            'total_fees_paid'         => (float) (clone $allForProvider)->sum('provider_fee'),
            'total_client_fees'       => (float) (clone $allForProvider)->sum('client_fee'),
            'total_platform_fees'     => (float) (clone $allForProvider)->sum('platform_fee_total'),
            'total_net_received'      => (float) (clone $allForProvider)->where('withdrawal_status', 'paid')->sum('net_provider_amount'),
            'total_in_escrow'         => (float) (clone $allForProvider)->holding()->sum('net_provider_amount'),
            'pending_withdrawal_count'=> (int) (clone $allForProvider)->pendingWithdrawal()->count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'summary'     => $summary,
                'history'     => $results->items(),
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
     * GET /v1/withdrawals/{id}
     * Get details of a specific withdrawal request.
     */
    #[OA\Get(
        path: '/api/v1/withdrawals/{id}',
        summary: 'Get single withdrawal request details',
        security: [['bearerAuth' => []]],
        tags: ['Withdrawals']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 403, description: 'Unauthorized')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $withdrawal = WithdrawalRequest::with(['booking', 'approvedByAdmin:id,first_name,last_name'])
            ->findOrFail($id);

        if ($withdrawal->provider_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $withdrawal,
        ]);
    }

    /**
     * POST /v1/withdrawals/{id}/request
     * Provider formally requests payout for an escrow record.
     */
    #[OA\Post(
        path: '/api/v1/withdrawals/{id}/request',
        summary: 'Request payout for an escrow record',
        security: [['bearerAuth' => []]],
        tags: ['Withdrawals']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'bank_name', type: 'string', example: 'Chase Bank'),
                new OA\Property(property: 'account_number', type: 'string', example: '123456789'),
                new OA\Property(property: 'routing_number', type: 'string', example: '123456789')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Bad Request - Cannot request in current state')]
    #[OA\Response(response: 403, description: 'Unauthorized - Only providers')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function requestWithdrawal(Request $request, int $id)
    {
        $user = $request->user();

        if (!$user->isProvider()) {
            return response()->json(['success' => false, 'message' => 'Only providers can request withdrawals'], 403);
        }

        $withdrawal = WithdrawalRequest::byProvider($user->id)->findOrFail($id);

        if (!$withdrawal->canRequestWithdrawal()) {
            return response()->json([
                'success' => false,
                'message' => 'This withdrawal cannot be requested. Escrow must be in holding status and not already requested/paid.',
                'current_status' => [
                    'escrow_status'     => $withdrawal->escrow_status,
                    'withdrawal_status' => $withdrawal->withdrawal_status,
                ],
            ], 400);
        }

        $request->validate([
            'bank_name'      => 'sometimes|string|max:255',
            'account_number' => 'sometimes|string|max:50',
            'routing_number' => 'sometimes|string|max:20',
        ]);

        try {
            $updated = $this->withdrawalService->requestWithdrawal($withdrawal, $request->only([
                'bank_name',
                'account_number',
                'routing_number',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted. Admin will review within 7 days. If no action is taken, funds are auto-released.',
                'data'    => [
                    'id'                    => $updated->id,
                    'withdrawal_status'     => $updated->withdrawal_status,
                    'net_provider_amount'   => $updated->net_provider_amount,
                    'withdrawal_requested_at' => $updated->withdrawal_requested_at,
                    'auto_release_at'       => $updated->auto_release_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /v1/withdrawals/{id}/cancel
     * Provider cancels a pending withdrawal request (returns to 'none' state).
     */
    #[OA\Post(
        path: '/api/v1/withdrawals/{id}/cancel',
        summary: 'Cancel a pending withdrawal request',
        security: [['bearerAuth' => []]],
        tags: ['Withdrawals']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Bad Request - Cannot cancel in current state')]
    #[OA\Response(response: 403, description: 'Unauthorized - Only providers')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function cancel(Request $request, int $id)
    {
        $user = $request->user();

        $withdrawal = WithdrawalRequest::byProvider($user->id)->findOrFail($id);

        if (!$withdrawal->canCancelWithdrawal()) {
            return response()->json([
                'success' => false,
                'message' => 'Only requested withdrawals can be cancelled.',
            ], 400);
        }

        $withdrawal->update([
            'withdrawal_status'       => 'cancelled',
            'withdrawal_processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request cancelled. Your funds remain in escrow.',
            'data'    => [
                'id'                => $withdrawal->id,
                'withdrawal_status' => 'cancelled',
                'escrow_status'     => $withdrawal->escrow_status,
                'auto_release_at'   => $withdrawal->auto_release_at,
            ],
        ]);
    }

    /**
     * GET /v1/withdrawals/fee-calculator
     * Utility endpoint: calculate fees for a given amount (helps frontend show breakdowns).
     */
    #[OA\Get(
        path: '/api/v1/withdrawals/fee-calculator',
        summary: 'Calculate fees for a given amount',
        security: [['bearerAuth' => []]],
        tags: ['Withdrawals']
    )]
    #[OA\Parameter(name: 'amount', in: 'query', required: true, schema: new OA\Schema(type: 'number', format: 'float', example: 100.00))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 422, description: 'Validation Error')]
    public function feeCalculator(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $fees = WithdrawalRequest::calculateFees((float) $request->amount);

        return response()->json([
            'success' => true,
            'data'    => [
                'gross_amount'        => $fees['gross_amount'],
                'client_pays'         => $fees['client_total_charge'],
                'client_fee'          => $fees['client_fee'],
                'provider_fee'        => $fees['provider_fee'],
                'platform_fee_total'  => $fees['platform_fee_total'],
                'net_provider_amount' => $fees['net_provider_amount'],
                'currency'            => 'USD',
                'breakdown'           => [
                    'client_fee_percent'   => '10%',
                    'provider_fee_percent' => '10%',
                    'description'          => 'Client pays gross + 10%, provider receives gross - 10%',
                ],
            ],
        ]);
    }
}
