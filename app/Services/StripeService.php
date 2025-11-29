<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Booking;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Customer;
use Exception;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create payment intent for booking
     */
    public function createPaymentIntent(Booking $booking, $paymentMethodId = null)
    {
        try {
            $amount = $booking->total_amount;
            $platformFee = Payment::calculatePlatformFee($amount);
            $providerAmount = Payment::calculateProviderAmount($amount);

            // Get or create Stripe customer
            $customer = $this->getOrCreateCustomer($booking->client);

            // Create payment intent
            $paymentIntentData = [
                'amount' => $this->convertToStripeAmount($amount), // Stripe expects cents
                'currency' => 'usd',
                'customer' => $customer->id,
                'description' => "Payment for booking #{$booking->id}",
                'metadata' => [
                    'booking_id' => $booking->id,
                    'client_id' => $booking->client_id,
                    'provider_id' => $booking->provider_id,
                ],
            ];

            if ($paymentMethodId) {
                $paymentIntentData['payment_method'] = $paymentMethodId;
                $paymentIntentData['confirm'] = true;
            }

            $paymentIntent = PaymentIntent::create($paymentIntentData);

            // Create payment record
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'client_id' => $booking->client_id,
                'provider_id' => $booking->provider_id,
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'provider_amount' => $providerAmount,
                'currency' => 'usd',
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_customer_id' => $customer->id,
                'payment_method_id' => $paymentMethodId,
                'status' => $paymentIntent->status,
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'client_secret' => $paymentIntent->client_secret,
                'requires_action' => $paymentIntent->status === 'requires_action',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm payment intent
     */
    public function confirmPaymentIntent($paymentIntentId, $paymentMethodId = null)
    {
        try {
            $data = [];
            
            if ($paymentMethodId) {
                $data['payment_method'] = $paymentMethodId;
            }

            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $paymentIntent = $paymentIntent->confirm($data);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'requires_action' => $paymentIntent->status === 'requires_action',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process refund
     */
    public function processRefund(Payment $payment, $amount = null, $reason = null)
    {
        try {
            if (!$payment->stripe_charge_id && !$payment->stripe_payment_intent_id) {
                throw new Exception('No charge or payment intent ID found');
            }

            $refundData = [
                'payment_intent' => $payment->stripe_payment_intent_id,
            ];

            if ($amount) {
                $refundData['amount'] = $this->convertToStripeAmount($amount);
            }

            if ($reason) {
                $refundData['reason'] = $reason;
            }

            $refund = Refund::create($refundData);

            $refundAmount = $this->convertFromStripeAmount($refund->amount);
            $payment->processRefund($refundAmount, $reason);

            return [
                'success' => true,
                'refund' => $refund,
                'amount' => $refundAmount,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get or create Stripe customer
     */
    protected function getOrCreateCustomer($user)
    {
        if ($user->stripe_customer_id) {
            try {
                return Customer::retrieve($user->stripe_customer_id);
            } catch (Exception $e) {
                // Customer not found, create new one
            }
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->first_name . ' ' . $user->last_name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        // Save customer ID to user
        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Convert amount to Stripe format (cents)
     */
    protected function convertToStripeAmount($amount)
    {
        return (int) ($amount * 100);
    }

    /**
     * Convert amount from Stripe format (cents to dollars)
     */
    protected function convertFromStripeAmount($amount)
    {
        return round($amount / 100, 2);
    }

    /**
     * Handle webhook event
     */
    public function handleWebhook($payload, $signature)
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );

            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object);
                    break;
            }

            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentSucceeded($paymentIntent)
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment && !$payment->isSucceeded()) {
            $payment->markAsSucceeded($paymentIntent->charges->data[0]->id ?? null);
        }
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailed($paymentIntent)
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment && !$payment->isFailed()) {
            $errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';
            $payment->markAsFailed($errorMessage);
        }
    }

    /**
     * Handle charge refunded
     */
    protected function handleChargeRefunded($charge)
    {
        $payment = Payment::where('stripe_charge_id', $charge->id)->first();

        if ($payment && !$payment->isRefunded()) {
            $refundAmount = $this->convertFromStripeAmount($charge->amount_refunded);
            $payment->processRefund($refundAmount);
        }
    }
}