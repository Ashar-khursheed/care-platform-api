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
        Schema::create('subscription_features', function (Blueprint $table) {
            $table->id();
            
            // Foreign Key
            $table->foreignId('subscription_plan_id')
                  ->constrained('subscription_plans')
                  ->onDelete('cascade');
            
            // Feature Details
            $table->string('name'); // e.g., "24/7 Support", "Custom Branding"
            $table->text('description')->nullable();
            $table->boolean('is_included')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('subscription_plan_id');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_features');
    }
};