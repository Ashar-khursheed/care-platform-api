<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

        /**
         * @OA\Get(
         *     path="/v1/payouts/balance",
         *     operationId="payoutsGetbalance",
         *     tags={"Payouts"},
         *     summary="Get provider's current balance",
         *     security={{"bearerAuth":{}}},
         *     @OA\Response(response=200, description="Success"),
         *     @OA\Response(response=401, description="Unauthorized"),
         *     @OA\Response(response=404, description="Not found"),
         *     @OA\Response(response=500, description="Server error")
         * )
         */
    public function getBalance(Request $request)
    {
        $user = $request->user();

        if (!$user->isProvider()) {
            return response()->json([
                'success' => false,
                'message' => 'Only providers can check balance',
            ], 403);
        }

        // Get total earnings (completed payments)
        $totalEarnings = Payment::where('provider_id', $user->id)
            ->where('status', 'succeeded')
            ->sum('provider_amount');

        // Get total already paid out
        $totalPaidOut = Payout::where('provider_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount');

        // Get pending payouts
        $pendingPayouts = Payout::where('provider_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');

        // Calculate available balance
        $availableBalance = $totalEarnings - $totalPaidOut - $pendingPayouts;

        return response()->json([
            'success' => true,
            'data' => [
                'total_earnings' => number_format($totalEarnings, 2),
                'total_paid_out' => number_format($totalPaidOut, 2),
                'pending_payouts' => number_format($pendingPayouts, 2),
                'available_balance' => number_format($availableBalance, 2),
                'currency' => 'USD',
                'has_stripe_account' => !empty($user->stripe_account_id),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payouts/request",
     *     summary="Request a payout",
     *     tags={"Payouts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "password"},
     *             @OA\Property(property="amount", type="number", format="float", example=100.00),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *             @OA\Property(
     *                 property="bank_account_details",
     *                 type="object",
     *                 @OA\Property(property="bank_name", type="string"),
     *                 @OA\Property(property="account_number", type="string"),
     *                 @OA\Property(property="routing_number", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Payout requested successfully"),
     *     @OA\Response(response=400, description="Insufficient balance"),
     *     @OA\Response(response=401, description="Invalid password"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function requestPayout(Request $request)
    {
        $user = $request->user();

        if (!$user->isProvider()) {
            return response()->json([
                'success' => false,
                'message' => 'Only providers can request payouts',
            ], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:10', // Minimum $10 payout
            'password' => 'required|string', // Security check
            'bank_account_details' => 'sometimes|array',
            'bank_account_details.bank_name' => 'sometimes|string',
            'bank_account_details.account_number' => 'sometimes|string',
            'bank_account_details.routing_number' => 'sometimes|string',
        ]);

        // Verify password for security
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password. Please verify your identity.',
            ], 401);
        }

        // Calculate available balance
        $totalEarnings = Payment::where('provider_id', $user->id)
            ->where('status', 'succeeded')
            ->sum('provider_amount');

        $totalPaidOut = Payout::where('provider_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount');

        $pendingPayouts = Payout::where('provider_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');

        $availableBalance = $totalEarnings - $totalPaidOut - $pendingPayouts;

        // Validate requested amount
        if ($request->amount > $availableBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance. Available: $' . number_format($availableBalance, 2),
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Create payout request
            $payout = Payout::create([
                'provider_id' => $user->id,
                'amount' => $request->amount,
                'currency' => 'USD',
                'status' => 'pending',
                'bank_name' => $request->bank_account_details['bank_name'] ?? null,
                'account_number_last4' => $request->bank_account_details['account_number'] 
                    ? substr($request->bank_account_details['account_number'], -4) 
                    : null,
                'scheduled_at' => now(),
                'metadata' => $request->bank_account_details ?? [],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payout request submitted successfully. It will be processed by admin.',
                'data' => [
                    'payout_id' => $payout->id,
                    'amount' => $payout->amount,
                    'status' => $payout->status,
                    'scheduled_at' => $payout->scheduled_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payout request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payouts",
     *     summary="Get my payout history",
     *     tags={"Payouts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (pending, paid, failed)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function myPayouts(Request $request)
    {
        $user = $request->user();

        if (!$user->isProvider()) {
            return response()->json([
                'success' => false,
                'message' => 'Only providers can view payouts',
            ], 403);
        }

        $query = Payout::where('provider_id', $user->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 10);
        $payouts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'payouts' => $payouts->items(),
                'pagination' => [
                    'total' => $payouts->total(),
                    'per_page' => $payouts->perPage(),
                    'current_page' => $payouts->currentPage(),
                    'last_page' => $payouts->lastPage(),
                ]
            ]
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/payouts/{id}",
 *         summary="Get payout details",
 *         tags={"Payouts"},
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
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $payout = Payout::findOrFail($id);

        // Authorization
        if ($payout->provider_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payout
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payouts/{id}/cancel",
     *     summary="Cancel a pending payout request",
     *     tags={"Payouts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payout ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Payout cancelled successfully"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Payout not found"),
     *     @OA\Response(response=400, description="Cannot cancel payout")
     * )
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $payout = Payout::findOrFail($id);

        // Authorization
        if ($payout->provider_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Can only cancel pending payouts
        if ($payout->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Can only cancel pending payouts',
            ], 400);
        }

        $payout->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Payout request cancelled successfully'
        ]);
    }
}
