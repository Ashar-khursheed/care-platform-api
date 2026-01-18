<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\StripeService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create payment intent for booking
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'payment_method_id' => 'nullable|string',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        // Authorization: Only client who made the booking
        if ($request->user()->id !== $booking->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to pay for this booking.',
            ], 403);
        }

        // Check if booking is already paid
        if ($booking->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Booking is already paid.',
            ], 400);
        }

        // Check if booking is accepted
        if ($booking->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Booking must be accepted by provider before payment.',
            ], 400);
        }

        $result = $this->stripeService->createPaymentIntent($booking, $request->payment_method_id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent.',
                'error' => $result['error'],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'payment_id' => $result['payment']->id,
            'client_secret' => $result['client_secret'],
            'requires_action' => $result['requires_action'],
        ]);
    }

    /**
     * Confirm payment
     */
    public function confirmPayment(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        // Authorization
        if ($request->user()->id !== $payment->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($payment->isSucceeded()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already succeeded.',
            ], 400);
        }

        $result = $this->stripeService->confirmPaymentIntent(
            $payment->stripe_payment_intent_id,
            $request->payment_method_id
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment.',
                'error' => $result['error'],
            ], 500);
        }

        // Update payment status
        $payment->update([
            'status' => $result['payment_intent']->status,
        ]);

        return response()->json([
            'success' => true,
            'status' => $payment->status,
            'requires_action' => $result['requires_action'],
        ]);
    }

    /**
     * Get payment details
     */
    public function show(Request $request, $id)
    {
        $payment = Payment::with(['booking', 'client', 'provider'])->findOrFail($id);

        // Authorization: client or provider
        if (!in_array($request->user()->id, [$payment->client_id, $payment->provider_id])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'booking_id' => $payment->booking_id,
                'amount' => $payment->amount,
                'platform_fee' => $payment->platform_fee,
                'provider_amount' => $payment->provider_amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'payment_method_type' => $payment->payment_method_type,
                'card_brand' => $payment->card_brand,
                'card_last4' => $payment->card_last4,
                'paid_at' => $payment->paid_at,
                'refunded_at' => $payment->refunded_at,
                'refund_amount' => $payment->refund_amount,
                'created_at' => $payment->created_at,
            ],
        ]);
    }

    /**
     * Get my payments
     */
    public function myPayments(Request $request)
    {
        $user = $request->user();

        $query = Payment::with(['booking', 'client', 'provider']);

        if ($user->isClient()) {
            $query->byClient($user->id);
        } elseif ($user->isProvider()) {
            $query->byProvider($user->id);
        }

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 10);
        $payments = $query->paginate($perPage);

        return response()->json($payments);
    }

    /**
     * Get my transactions
     */
    public function myTransactions(Request $request)
    {
        $user = $request->user();

        $query = Transaction::with(['payment', 'payout', 'booking'])
            ->byUser($user->id);

        // Filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 10);
        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Request refund (client only)
     */
    public function requestRefund(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $payment = Payment::findOrFail($id);

        // Authorization: only client
        if ($request->user()->id !== $payment->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Validate payment can be refunded
        if (!$payment->canBeRefunded()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be refunded.',
            ], 400);
        }

        // Validate refund amount
        $refundAmount = $request->amount ?? $payment->amount;
        if ($refundAmount > $payment->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount cannot exceed payment amount.',
            ], 400);
        }

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
            'refund_amount' => $result['amount'],
        ]);
    }

    /**
     * Get payment statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        if ($user->isClient()) {
            $stats = [
                'total_payments' => Payment::byClient($user->id)->count(),
                'total_spent' => Payment::byClient($user->id)->succeeded()->sum('amount'),
                'successful_payments' => Payment::byClient($user->id)->succeeded()->count(),
                'failed_payments' => Payment::byClient($user->id)->failed()->count(),
                'refunded_payments' => Payment::byClient($user->id)->refunded()->count(),
                'total_refunded' => Payment::byClient($user->id)->refunded()->sum('refund_amount'),
            ];
        } elseif ($user->isProvider()) {
            $stats = [
                'total_earnings' => Payment::byProvider($user->id)->succeeded()->sum('provider_amount'),
                'platform_fees_paid' => Payment::byProvider($user->id)->succeeded()->sum('platform_fee'),
                'successful_payments' => Payment::byProvider($user->id)->succeeded()->count(),
                'pending_payouts' => $user->payouts()->pending()->sum('amount'),
                'total_paid_out' => $user->payouts()->paid()->sum('amount'),
            ];
        } else {
            $stats = [];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Webhook handler
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        $result = $this->stripeService->handleWebhook($payload, $signature);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 400);
        }

        return response()->json(['success' => true]);
    }
}
