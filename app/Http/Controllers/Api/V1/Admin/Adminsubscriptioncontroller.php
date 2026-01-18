<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\UserSubscriptionResource;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionFeature;
use App\Models\UserSubscription;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    /**
     * Get all subscription planssss
     */
    public function plans(Request $request)
    {
        $query = SubscriptionPlan::with('features');

        // Filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->ordered();

        $plans = $query->paginate($request->get('per_page', 15));

        return SubscriptionPlanResource::collection($plans);
    }

    public function planById($id)
    {
        $plan = SubscriptionPlan::with('features')->find($id);

        if (!$plan) {
            return response()->json([
                'message' => 'Plan not found'
            ], 404);
        }

        return new SubscriptionPlanResource($plan);
    }


    /**
     * Create new plan
     */
    public function createPlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'max_listings' => 'required|integer|min:0',
            'max_bookings_per_month' => 'required|integer|min:0',
            'max_featured_listings' => 'nullable|integer|min:0',
            'featured_listings_allowed' => 'boolean',
            'priority_support' => 'boolean',
            'analytics_access' => 'boolean',
            'api_access' => 'boolean',
            'trial_days' => 'nullable|integer|min:0',
            'stripe_plan_id' => 'nullable|string',
            'stripe_yearly_plan_id' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan created successfully.',
            'data' => new SubscriptionPlanResource($plan),
        ], 201);
    }

    /**
     * Update plan
     */
    public function updatePlan(Request $request, $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'max_listings' => 'integer|min:0',
            'max_bookings_per_month' => 'integer|min:0',
            'max_featured_listings' => 'nullable|integer|min:0',
            'featured_listings_allowed' => 'boolean',
            'priority_support' => 'boolean',
            'analytics_access' => 'boolean',
            'api_access' => 'boolean',
            'trial_days' => 'nullable|integer|min:0',
        ]);

        $plan->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan updated successfully.',
            'data' => new SubscriptionPlanResource($plan),
        ]);
    }

    /**
     * Delete plan
     */
    public function deletePlan($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        // Check if plan has active subscriptions
        $activeCount = UserSubscription::where('subscription_plan_id', $id)
            ->whereIn('status', ['trial', 'active'])
            ->count();

        if ($activeCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete plan with {$activeCount} active subscriptions.",
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan deleted successfully.',
        ]);
    }

    /**
     * Add feature to plan
     */
    public function addFeature(Request $request, $planId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_included' => 'boolean',
        ]);

        $plan = SubscriptionPlan::findOrFail($planId);

        $feature = SubscriptionFeature::create([
            'subscription_plan_id' => $plan->id,
            'name' => $request->name,
            'description' => $request->description,
            'is_included' => $request->boolean('is_included', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feature added successfully.',
            'data' => $feature,
        ], 201);
    }

    /**
     * Remove feature from plan
     */
    public function removeFeature($planId, $featureId)
    {
        $feature = SubscriptionFeature::where('subscription_plan_id', $planId)
            ->where('id', $featureId)
            ->firstOrFail();

        $feature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feature removed successfully.',
        ]);
    }

    /**
     * Get all subscriptions
     */
    public function subscriptions(Request $request)
    {
        $query = UserSubscription::with(['user', 'plan']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('plan_id')) {
            $query->where('subscription_plan_id', $request->plan_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $query->orderBy('created_at', 'desc');

        $subscriptions = $query->paginate($request->get('per_page', 15));

        return UserSubscriptionResource::collection($subscriptions);
    }

    /**
     * Cancel user subscription (admin)
     */
    public function cancelSubscription(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription = UserSubscription::findOrFail($id);

        $subscription->cancel($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Subscription canceled successfully.',
            'data' => new UserSubscriptionResource($subscription),
        ]);
    }

    /**
     * Get subscription statistics
     */
    public function statistics()
    {
        $totalSubscriptions = UserSubscription::count();
        $activeSubscriptions = UserSubscription::active()->count();
        $trialSubscriptions = UserSubscription::trial()->count();
        $canceledSubscriptions = UserSubscription::canceled()->count();

        // Revenue
        $monthlyRevenue = UserSubscription::whereIn('status', ['trial', 'active'])
            ->where('billing_cycle', 'monthly')
            ->sum('amount');

        $yearlyRevenue = UserSubscription::whereIn('status', ['trial', 'active'])
            ->where('billing_cycle', 'yearly')
            ->sum('amount');

        // Subscriptions by plan
        $byPlan = UserSubscription::whereIn('status', ['trial', 'active'])
            ->selectRaw('subscription_plan_id, COUNT(*) as count')
            ->groupBy('subscription_plan_id')
            ->with('plan')
            ->get()
            ->map(function ($sub) {
                return [
                    'plan_id' => $sub->subscription_plan_id,
                    'plan_name' => $sub->plan->name,
                    'subscribers' => $sub->count,
                ];
            });

        // Recent subscriptions (last 30 days)
        $recentSubscriptions = UserSubscription::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Churn rate (last 30 days)
        $cancellationsLast30Days = UserSubscription::where('canceled_at', '>=', now()->subDays(30))
            ->count();
        $activeAtStart = UserSubscription::where('created_at', '<', now()->subDays(30))
            ->whereIn('status', ['trial', 'active', 'canceled'])
            ->count();
        $churnRate = $activeAtStart > 0 ? round(($cancellationsLast30Days / $activeAtStart) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_subscriptions' => $totalSubscriptions,
                    'active_subscriptions' => $activeSubscriptions,
                    'trial_subscriptions' => $trialSubscriptions,
                    'canceled_subscriptions' => $canceledSubscriptions,
                ],
                'revenue' => [
                    'monthly_recurring' => $monthlyRevenue,
                    'yearly_recurring' => $yearlyRevenue,
                    'total_mrr' => $monthlyRevenue + ($yearlyRevenue / 12),
                ],
                'by_plan' => $byPlan,
                'recent_subscriptions' => $recentSubscriptions,
                'churn_rate' => $churnRate . '%',
            ],
        ]);
    }
}
