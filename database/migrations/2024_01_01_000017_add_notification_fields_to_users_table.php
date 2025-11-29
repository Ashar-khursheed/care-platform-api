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
            // Push Notification Token (FCM)
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('stripe_customer_id');
            }
            
            // Device Info
            if (!Schema::hasColumn('users', 'device_type')) {
                $table->enum('device_type', ['ios', 'android', 'web'])->nullable()->after('fcm_token');
            }
            
            // Notification Settings
            if (!Schema::hasColumn('users', 'notifications_enabled')) {
                $table->boolean('notifications_enabled')->default(true)->after('device_type');
            }
            
            if (!Schema::hasColumn('users', 'email_notifications_enabled')) {
                $table->boolean('email_notifications_enabled')->default(true)->after('notifications_enabled');
            }
            
            if (!Schema::hasColumn('users', 'push_notifications_enabled')) {
                $table->boolean('push_notifications_enabled')->default(true)->after('email_notifications_enabled');
            }
            
            if (!Schema::hasColumn('users', 'sms_notifications_enabled')) {
                $table->boolean('sms_notifications_enabled')->default(false)->after('push_notifications_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToCheck = [
                'fcm_token',
                'device_type',
                'notifications_enabled',
                'email_notifications_enabled',
                'push_notifications_enabled',
                'sms_notifications_enabled',
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};