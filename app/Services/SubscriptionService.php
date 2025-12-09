<?php

namespace App\Services;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Customer;
use Exception;

class SubscriptionService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Subscribe user to a plan
     */
    public function subscribe(User $user, SubscriptionPlan $plan, $billingCycle = 'monthly', $paymentMethodId = null)
    {
        try {
            // Get or create Stripe customer
            $stripeCustomer = $this->getOrCreateCustomer($user);

            // Attach payment method if provided
            if ($paymentMethodId) {
                $this->attachPaymentMethod($stripeCustomer->id, $paymentMethodId);
            }

            // Determine price ID
            $priceId = $billingCycle === 'yearly' 
                ? $plan->stripe_yearly_plan_id 
                : $plan->stripe_plan_id;

            // Calculate dates
            $trialEnds = $plan->hasTrial() ? now()->addDays($plan->trial_days) : null;
            $starts = $trialEnds ?? now();
            $ends = $billingCycle === 'yearly' 
                ? $starts->copy()->addYear() 
                : $starts->copy()->addMonth();

            // For free plans, skip Stripe
            if ($plan->isFree()) {
                return $this->createFreeSubscription($user, $plan);
            }

            // Create Stripe subscription
            $stripeSubscription = Subscription::create([
                'customer' => $stripeCustomer->id,
                'items' => [['price' => $priceId]],
                'trial_end' => $trialEnds ? $trialEnds->timestamp : 'now',
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            // Create local subscription
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_customer_id' => $stripeCustomer->id,
                'billing_cycle' => $billingCycle,
                'amount' => $billingCycle === 'yearly' ? $plan->yearly_price : $plan->price,
                'currency' => $plan->currency,
                'status' => $trialEnds ? 'trial' : 'active',
                'trial_ends_at' => $trialEnds,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'usage_reset_at' => now()->addMonth(),
            ]);

            return [
                'success' => true,
                'subscription' => $subscription,
                'stripe_subscription' => $stripeSubscription,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create free subscription
     */
    protected function createFreeSubscription(User $user, SubscriptionPlan $plan)
    {
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 0,
            'currency' => $plan->currency,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => null, // Free plans don't expire
            'usage_reset_at' => now()->addMonth(),
        ]);

        return [
            'success' => true,
            'subscription' => $subscription,
        ];
    }

    /**
     * Upgrade subscription
     */
    public function upgrade(UserSubscription $currentSubscription, SubscriptionPlan $newPlan, $billingCycle = null)
    {
        try {
            $billingCycle = $billingCycle ?? $currentSubscription->billing_cycle;

            // If current is free, create new subscription
            if ($currentSubscription->plan->isFree()) {
                $currentSubscription->cancel('Upgraded to paid plan');
                return $this->subscribe($currentSubscription->user, $newPlan, $billingCycle);
            }

            // Update Stripe subscription
            $priceId = $billingCycle === 'yearly' 
                ? $newPlan->stripe_yearly_plan_id 
                : $newPlan->stripe_plan_id;

            $stripeSubscription = Subscription::update(
                $currentSubscription->stripe_subscription_id,
                [
                    'items' => [[
                        'id' => Subscription::retrieve($currentSubscription->stripe_subscription_id)->items->data[0]->id,
                        'price' => $priceId,
                    ]],
                    'proration_behavior' => 'always_invoice', // Charge immediately
                ]
            );

            // Update local subscription
            $currentSubscription->update([
                'subscription_plan_id' => $newPlan->id,
                'billing_cycle' => $billingCycle,
                'amount' => $billingCycle === 'yearly' ? $newPlan->yearly_price : $newPlan->price,
            ]);

            return [
                'success' => true,
                'subscription' => $currentSubscription->fresh(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Downgrade subscription
     */
    public function downgrade(UserSubscription $currentSubscription, SubscriptionPlan $newPlan)
    {
        try {
            // Downgrade at end of billing period
            $priceId = $currentSubscription->billing_cycle === 'yearly' 
                ? $newPlan->stripe_yearly_plan_id 
                : $newPlan->stripe_plan_id;

            Subscription::update(
                $currentSubscription->stripe_subscription_id,
                [
                    'items' => [[
                        'id' => Subscription::retrieve($currentSubscription->stripe_subscription_id)->items->data[0]->id,
                        'price' => $priceId,
                    ]],
                    'proration_behavior' => 'none', // Don't prorate, change at period end
                ]
            );

            // Schedule downgrade
            $currentSubscription->update([
                'metadata' => array_merge($currentSubscription->metadata ?? [], [
                    'scheduled_downgrade' => [
                        'plan_id' => $newPlan->id,
                        'effective_date' => $currentSubscription->ends_at,
                    ]
                ]),
            ]);

            return [
                'success' => true,
                'subscription' => $currentSubscription->fresh(),
                'message' => 'Plan will be downgraded at the end of current billing period',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(UserSubscription $subscription, $immediately = false, $reason = null)
    {
        try {
            if (!$subscription->plan->isFree() && $subscription->stripe_subscription_id) {
                if ($immediately) {
                    // Cancel immediately
                    Subscription::update($subscription->stripe_subscription_id, [
                        'cancel_at_period_end' => false,
                    ]);
                    $stripeSubscription = Subscription::retrieve($subscription->stripe_subscription_id);
                    $stripeSubscription->cancel();
                } else {
                    // Cancel at period end
                    Subscription::update($subscription->stripe_subscription_id, [
                        'cancel_at_period_end' => true,
                    ]);
                }
            }

            $subscription->cancel($reason);

            return [
                'success' => true,
                'subscription' => $subscription->fresh(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resume canceled subscription
     */
    public function resume(UserSubscription $subscription)
    {
        try {
            if ($subscription->stripe_subscription_id) {
                Subscription::update($subscription->stripe_subscription_id, [
                    'cancel_at_period_end' => false,
                ]);
            }

            $subscription->update([
                'status' => 'active',
                'canceled_at' => null,
                'cancellation_reason' => null,
                'auto_renew' => true,
            ]);

            return [
                'success' => true,
                'subscription' => $subscription->fresh(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get or create Stripe customer
     */
    protected function getOrCreateCustomer(User $user)
    {
        if ($user->stripe_customer_id) {
            try {
                return Customer::retrieve($user->stripe_customer_id);
            } catch (Exception $e) {
                // Customer doesn't exist, create new one
            }
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->first_name . ' ' . $user->last_name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Attach payment method to customer
     */
    protected function attachPaymentMethod($customerId, $paymentMethodId)
    {
        $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
        $paymentMethod->attach(['customer' => $customerId]);

        // Set as default payment method
        Customer::update($customerId, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);
    }
}