<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Current Subscription
            if (!Schema::hasColumn('users', 'current_subscription_id')) {
                $table->foreignId('current_subscription_id')
                      ->nullable()
                      ->after('stripe_customer_id')
                      ->constrained('user_subscriptions')
                      ->onDelete('set null');
            }
            
            // Subscription Status (cached for quick access)
            if (!Schema::hasColumn('users', 'subscription_status')) {
                $table->enum('subscription_status', [
                    'none',
                    'trial',
                    'active',
                    'past_due',
                    'canceled',
                    'expired',
                    'paused'
                ])->default('none')->after('current_subscription_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_subscription_id')) {
                $table->dropForeign(['current_subscription_id']);
                $table->dropColumn('current_subscription_id');
            }
            
            if (Schema::hasColumn('users', 'subscription_status')) {
                $table->dropColumn('subscription_status');
            }
        });
    }
};