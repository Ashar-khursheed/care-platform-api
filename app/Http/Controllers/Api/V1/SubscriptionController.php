<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\UserSubscriptionResource;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get all available plans
     */
    public function plans(Request $request)
    {
        $plans = SubscriptionPlan::active()
            ->with('features')
            ->ordered()
            ->get();

        return SubscriptionPlanResource::collection($plans);
    }

    /**
     * Get current user's subscription
     */
    public function current(Request $request)
    {
        $subscription = UserSubscription::where('user_id', $request->user()->id)
            ->with('plan.features')
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 404);
        }

        return new UserSubscriptionResource($subscription);
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_method_id' => 'nullable|string',
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // Check if user already has active subscription
        $existingSubscription = UserSubscription::where('user_id', $user->id)
            ->whereIn('status', ['trial', 'active'])
            ->first();

        if ($existingSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription. Please upgrade or cancel your current subscription first.',
            ], 400);
        }

        // Subscribe user
        $result = $this->subscriptionService->subscribe(
            $user,
            $plan,
            $request->billing_cycle,
            $request->payment_method_id
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully.',
            'data' => new UserSubscriptionResource($result['subscription']),
        ], 201);
    }

    /**
     * Upgrade subscription
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'nullable|in:monthly,yearly',
        ]);

        $user = $request->user();
        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        $currentSubscription = UserSubscription::where('user_id', $user->id)
            ->whereIn('status', ['trial', 'active'])
            ->first();

        if (!$currentSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found to upgrade.',
            ], 404);
        }

        // Check if it's actually an upgrade
        if ($newPlan->price <= $currentSubscription->plan->price) {
            return response()->json([
                'success' => false,
                'message' => 'The selected plan is not an upgrade. Use downgrade endpoint instead.',
            ], 400);
        }

        $result = $this->subscriptionService->upgrade(
            $currentSubscription,
            $newPlan,
            $request->billing_cycle
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription upgraded successfully.',
            'data' => new UserSubscriptionResource($result['subscription']),
        ]);
    }

    /**
     * Downgrade subscription
     */
    public function downgrade(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $user = $request->user();
        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        $currentSubscription = UserSubscription::where('user_id', $user->id)
            ->whereIn('status', ['trial', 'active'])
            ->first();

        if (!$currentSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found to downgrade.',
            ], 404);
        }

        // Check if it's actually a downgrade
        if ($newPlan->price >= $currentSubscription->plan->price) {
            return response()->json([
                'success' => false,
                'message' => 'The selected plan is not a downgrade. Use upgrade endpoint instead.',
            ], 400);
        }

        $result = $this->subscriptionService->downgrade(
            $currentSubscription,
            $newPlan
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Subscription will be downgraded at the end of billing period.',
            'data' => new UserSubscriptionResource($result['subscription']),
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'immediately' => 'nullable|boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->whereIn('status', ['trial', 'active'])
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found to cancel.',
            ], 404);
        }

        $result = $this->subscriptionService->cancel(
            $subscription,
            $request->boolean('immediately'),
            $request->reason
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription canceled successfully.',
            'data' => new UserSubscriptionResource($result['subscription']),
        ]);
    }

    /**
     * Resume canceled subscription
     */
    public function resume(Request $request)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'canceled')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No canceled subscription found to resume.',
            ], 404);
        }

        $result = $this->subscriptionService->resume($subscription);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription resumed successfully.',
            'data' => new UserSubscriptionResource($result['subscription']),
        ]);
    }

    /**
     * Get subscription history
     */
    public function history(Request $request)
    {
        $subscriptions = UserSubscription::where('user_id', $request->user()->id)
            ->with('plan.features')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return UserSubscriptionResource::collection($subscriptions);
    }

    /**
     * Get usage statistics
     */
    public function usage(Request $request)
    {
        $subscription = UserSubscription::where('user_id', $request->user()->id)
            ->whereIn('status', ['trial', 'active'])
            ->with('plan')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'listings' => [
                    'used' => $subscription->listings_used,
                    'limit' => $subscription->plan->max_listings,
                    'unlimited' => $subscription->plan->hasUnlimitedListings(),
                    'remaining' => $subscription->plan->hasUnlimitedListings() 
                        ? 'unlimited' 
                        : max(0, $subscription->plan->max_listings - $subscription->listings_used),
                    'can_create' => $subscription->canCreateListing(),
                ],
                'bookings' => [
                    'used' => $subscription->bookings_used,
                    'limit' => $subscription->plan->max_bookings_per_month,
                    'unlimited' => $subscription->plan->hasUnlimitedBookings(),
                    'remaining' => $subscription->plan->hasUnlimitedBookings() 
                        ? 'unlimited' 
                        : max(0, $subscription->plan->max_bookings_per_month - $subscription->bookings_used),
                    'can_create' => $subscription->canCreateBooking(),
                    'resets_at' => $subscription->usage_reset_at 
                        ? $subscription->usage_reset_at->format('Y-m-d H:i:s') 
                        : null,
                ],
            ],
        ]);
    }
}
