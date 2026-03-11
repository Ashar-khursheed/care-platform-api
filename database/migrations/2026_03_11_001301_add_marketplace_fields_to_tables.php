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
            $table->string('business_name')->nullable()->after('last_name');
            $table->string('facility_type')->nullable()->after('business_name');
            $table->string('desired_role')->nullable()->after('user_type');
            $table->integer('profile_completion_percentage')->default(0)->after('status');
        });

        Schema::table('service_listings', function (Blueprint $table) {
            $table->date('shift_date')->nullable()->after('description');
            $table->time('shift_start_time')->nullable()->after('shift_date');
            $table->time('shift_end_time')->nullable()->after('shift_start_time');
            $table->boolean('is_urgent')->default(false)->after('is_featured');
            $table->boolean('quick_pay')->default(false)->after('is_urgent');
            $table->integer('workers_needed')->default(1)->after('quick_pay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['business_name', 'facility_type', 'desired_role', 'profile_completion_percentage']);
        });

        Schema::table('service_listings', function (Blueprint $table) {
            $table->dropColumn(['shift_date', 'shift_start_time', 'shift_end_time', 'is_urgent', 'quick_pay', 'workers_needed']);
        });
    }
};
