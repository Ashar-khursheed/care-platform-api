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
        Schema::table('service_listings', function (Blueprint $table) {
            $table->string('zip_code')->nullable()->after('service_location');
            $table->string('city')->nullable()->after('zip_code');
            $table->string('state')->nullable()->after('city');
            
            $table->index('zip_code');
            $table->index('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_listings', function (Blueprint $table) {
            $table->dropColumn(['zip_code', 'city', 'state']);
        });
    }
};
