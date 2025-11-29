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
        Schema::table('bookings', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed', 'partially_refunded'])
                      ->default('pending')
                      ->after('status');
            }
            
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('payment_status');
            }
            
            if (!Schema::hasColumn('bookings', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('payment_method');
            }
            
            // Payment timestamps
            if (!Schema::hasColumn('bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('transaction_id');
            }
            
            if (!Schema::hasColumn('bookings', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('paid_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columnsToCheck = [
                'payment_status',
                'payment_method',
                'transaction_id',
                'paid_at',
                'refunded_at'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};