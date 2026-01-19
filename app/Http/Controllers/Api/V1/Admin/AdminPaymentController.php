<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Transaction;
use App\Services\StripeService;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/payments",
 *         summary="Get all payments",
 *         tags={"Payments"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function index(Request $request)
    {
        $query = Payment::with(['booking', 'client', 'provider']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->byClient($request->client_id);
        }

        if ($request->has('provider_id')) {
            $query->byProvider($request->provider_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $payments = $query->paginate($perPage);

        return response()->json($payments);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/payments/{id}",
 *         summary="Get payment details",
 *         tags={"Payments"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The id of the resource",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function show($id)
    {
        $payment = Payment::with(['booking', 'client', 'provider', 'payout', 'transactions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

        /**
 *     @OA\Post(
 *         path="/api/v1/admin/payments/{id}/refund",
 *         summary="Process refund",
 *         tags={"Payments"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The id of the resource",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function refund(Request $request, $id)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $payment = Payment::findOrFail($id);

        if (!$payment->canBeRefunded()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be refunded.',
            ], 400);
        }

        $refundAmount = $request->amount ?? $payment->amount;

        $result = $this->stripeService->processRefund($payment, $refundAmount, $request->reason);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund.',
                'error' => $result['error'],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully.',
            'data' => $payment->fresh(),
        ]);
    }

    /**
     * Get all payouts
     */
    public function payouts(Request $request)
    {
        $query = Payout::with(['provider', 'payment']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('provider_id')) {
            $query->byProvider($request->provider_id);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $payouts = $query->paginate($perPage);

        return response()->json($payouts);
    }

    /**
     * Process payout
     */
    public function processPayout(Request $request, $id)
    {
        $payout = Payout::findOrFail($id);

        if (!$payout->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Payout is not in pending status.',
            ], 400);
        }

        // Mark as paid (in production, integrate with actual payout system)
        $payout->markAsPaid();

        return response()->json([
            'success' => true,
            'message' => 'Payout processed successfully.',
            'data' => $payout->fresh(),
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/transactions",
 *         summary="Get transactions",
 *         tags={""},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function transactions(Request $request)
    {
        $query = Transaction::with(['user', 'payment', 'payout', 'booking']);

        // Filters
        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('direction')) {
            $query->where('direction', $request->direction);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/payments/statistics",
 *         summary="Get payment statistics",
 *         tags={"Payments"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function statistics(Request $request)
    {
        // Date range
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        // Overall statistics
        $totalPayments = Payment::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $totalAmount = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->succeeded()
            ->sum('amount');
        $totalPlatformFees = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->succeeded()
            ->sum('platform_fee');
        $totalProviderEarnings = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->succeeded()
            ->sum('provider_amount');

        // Status breakdown
        $statusBreakdown = [
            'pending' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])->pending()->count(),
            'succeeded' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])->succeeded()->count(),
            'failed' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])->failed()->count(),
            'refunded' => Payment::whereBetween('created_at', [$dateFrom, $dateTo])->refunded()->count(),
        ];

        // Refund statistics
        $totalRefunds = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->refunded()
            ->count();
        $totalRefundAmount = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->refunded()
            ->sum('refund_amount');

        // Recent payments (last 7 days grouped by date)
        $recentPayments = Payment::whereBetween('created_at', [now()->subDays(7), now()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Top earning providers
        $topProviders = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->succeeded()
            ->selectRaw('provider_id, COUNT(*) as payments_count, SUM(provider_amount) as total_earned')
            ->groupBy('provider_id')
            ->orderBy('total_earned', 'desc')
            ->limit(10)
            ->with('provider')
            ->get()
            ->map(function ($payment) {
                return [
                    'provider_id' => $payment->provider_id,
                    'provider_name' => $payment->provider->first_name . ' ' . $payment->provider->last_name,
                    'payments_count' => $payment->payments_count,
                    'total_earned' => round($payment->total_earned, 2),
                ];
            });

        // Payout statistics
        $pendingPayouts = Payout::pending()->sum('amount');
        $totalPayouts = Payout::paid()->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_payments' => $totalPayments,
                    'total_amount' => round($totalAmount, 2),
                    'total_platform_fees' => round($totalPlatformFees, 2),
                    'total_provider_earnings' => round($totalProviderEarnings, 2),
                ],
                'status_breakdown' => $statusBreakdown,
                'refunds' => [
                    'total_refunds' => $totalRefunds,
                    'total_refund_amount' => round($totalRefundAmount, 2),
                ],
                'recent_payments' => $recentPayments,
                'top_providers' => $topProviders,
                'payouts' => [
                    'pending_payouts' => round($pendingPayouts, 2),
                    'total_payouts' => round($totalPayouts, 2),
                ],
            ],
        ]);
    }
}
