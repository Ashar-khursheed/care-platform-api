<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPayoutController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/payouts",
 *         summary="Get all payouts",
 *         tags={"Payouts"},
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
        $query = Payout::with(['provider']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by provider
        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 20);
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
 *         path="/api/v1/admin/payouts/{id}",
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
    public function show($id)
    {
        $payout = Payout::with(['provider', 'transaction'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payout
        ]);
    }

        /**
 *     @OA\Post(
 *         path="/api/v1/admin/payouts/{id}/approve",
 *         summary="Approve payout",
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
    public function approvePayout(Request $request, $id)
    {
        $payout = Payout::with('provider')->findOrFail($id);

        if ($payout->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending payouts can be approved',
            ], 400);
        }

        $request->validate([
            'transaction_reference' => 'sometimes|string', // Bank transfer reference
            'notes' => 'sometimes|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Mark payout as paid
            $payout->update([
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => array_merge($payout->metadata ?? [], [
                    'approved_by' => $request->user()->id,
                    'transaction_reference' => $request->transaction_reference ?? null,
                    'admin_notes' => $request->notes ?? null,
                ])
            ]);

            // Create transaction record
            Transaction::create([
                'user_id' => $payout->provider_id,
                'payout_id' => $payout->id,
                'type' => 'payout',
                'amount' => $payout->amount,
                'currency' => $payout->currency,
                'direction' => 'credit',
                'status' => 'completed',
                'description' => "Payout for services rendered",
                'metadata' => [
                    'transaction_reference' => $request->transaction_reference ?? null,
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payout approved and processed successfully',
                'data' => $payout->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
 *     @OA\Post(
 *         path="/api/v1/admin/payouts/{id}/reject",
 *         summary="Reject payout",
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
    public function rejectPayout(Request $request, $id)
    {
        $payout = Payout::findOrFail($id);

        if ($payout->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending payouts can be rejected',
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payout->update([
            'status' => 'rejected',
            'failure_reason' => $request->reason,
            'failed_at' => now(),
            'metadata' => array_merge($payout->metadata ?? [], [
                'rejected_by' => $request->user()->id,
            ])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payout request rejected',
            'data' => $payout
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/payouts/statistics",
 *         summary="Get payout statistics",
 *         tags={"Payouts"},
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
        $stats = [
            'total_payouts' => Payout::count(),
            'pending_payouts' => Payout::where('status', 'pending')->count(),
            'pending_amount' => Payout::where('status', 'pending')->sum('amount'),
            'paid_payouts' => Payout::where('status', 'paid')->count(),
            'paid_amount' => Payout::where('status', 'paid')->sum('amount'),
            'rejected_payouts' => Payout::where('status', 'rejected')->count(),
            'total_pending_value' => number_format(Payout::where('status', 'pending')->sum('amount'), 2),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

        /**
 *     @OA\Post(
 *         path="/api/v1/admin/payouts/bulk-approve",
 *         summary="Bulk approve payouts",
 *         tags={"Payouts"},
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
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'payout_ids' => 'required|array',
            'payout_ids.*' => 'exists:payouts,id',
            'transaction_reference' => 'sometimes|string',
        ]);

        try {
            DB::beginTransaction();

            $payouts = Payout::whereIn('id', $request->payout_ids)
                ->where('status', 'pending')
                ->get();

            $approved = 0;
            foreach ($payouts as $payout) {
                $payout->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'metadata' => array_merge($payout->metadata ?? [], [
                        'approved_by' => $request->user()->id,
                        'transaction_reference' => $request->transaction_reference ?? null,
                    ])
                ]);

                // Create transaction
                Transaction::create([
                    'user_id' => $payout->provider_id,
                    'payout_id' => $payout->id,
                    'type' => 'payout',
                    'amount' => $payout->amount,
                    'currency' => $payout->currency,
                    'direction' => 'credit',
                    'status' => 'completed',
                    'description' => "Payout for services rendered",
                ]);

                $approved++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$approved} payouts approved successfully",
                'data' => [
                    'approved_count' => $approved
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk approve payouts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
