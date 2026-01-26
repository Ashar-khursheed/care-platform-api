<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'client_id',
        'provider_id',
        'amount',
        'platform_fee',
        'provider_amount',
        'currency',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_customer_id',
        'payment_method_id',
        'payment_method_type',
        'card_brand',
        'card_last4',
        'status',
        'paid_at',
        'failed_at',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'stripe_error',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'provider_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    // public function booking()
    // {
    //     return $this->belongsTo(Booking::class);
    // }
      public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class, 'booking_id'); // <- make sure Payment table has booking_id
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function payout()
    {
        return $this->hasOne(Payout::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->whereIn('status', ['refunded', 'partially_refunded']);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Helper Methods
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isSucceeded()
    {
        return $this->status === 'succeeded';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isRefunded()
    {
        return in_array($this->status, ['refunded', 'partially_refunded']);
    }

    public function canBeRefunded()
    {
        return $this->isSucceeded() && !$this->isRefunded();
    }

    public function requiresAction()
    {
        return in_array($this->status, ['requires_action', 'requires_payment_method', 'requires_confirmation']);
    }

    /**
     * Calculate platform fee (10% commission)
     */
    public static function calculatePlatformFee($amount)
    {
        $feePercentage = config('payment.platform_fee_percentage', 10); // 10%
        return round($amount * ($feePercentage / 100), 2);
    }

    /**
     * Calculate provider amount (after platform fee)
     */
    public static function calculateProviderAmount($amount)
    {
        $platformFee = self::calculatePlatformFee($amount);
        return round($amount - $platformFee, 2);
    }

    /**
     * Mark payment as succeeded
     */
    public function markAsSucceeded($stripeChargeId = null)
    {
        $this->update([
            'status' => 'succeeded',
            'stripe_charge_id' => $stripeChargeId,
            'paid_at' => now(),
        ]);

        // Create transaction record
        Transaction::create([
            'user_id' => $this->client_id,
            'payment_id' => $this->id,
            'booking_id' => $this->booking_id,
            'type' => 'payment',
            'amount' => $this->amount,
            'currency' => $this->currency,
            'direction' => 'debit',
            'status' => 'completed',
            'description' => "Payment for booking #{$this->booking_id}",
        ]);

        // Create platform fee transaction
        Transaction::create([
            'user_id' => 1, // Platform/admin user
            'payment_id' => $this->id,
            'booking_id' => $this->booking_id,
            'type' => 'platform_fee',
            'amount' => $this->platform_fee,
            'currency' => $this->currency,
            'direction' => 'credit',
            'status' => 'completed',
            'description' => "Platform fee for booking #{$this->booking_id}",
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'stripe_error' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    /**
     * Process refund
     */
    public function processRefund($amount = null, $reason = null)
    {
        $refundAmount = $amount ?? $this->amount;
        $isPartialRefund = $refundAmount < $this->amount;

        $this->update([
            'status' => $isPartialRefund ? 'partially_refunded' : 'refunded',
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);

        // Create refund transaction
        Transaction::create([
            'user_id' => $this->client_id,
            'payment_id' => $this->id,
            'booking_id' => $this->booking_id,
            'type' => 'refund',
            'amount' => $refundAmount,
            'currency' => $this->currency,
            'direction' => 'credit',
            'status' => 'completed',
            'description' => "Refund for booking #{$this->booking_id}",
        ]);
    }
    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::saved(function ($payment) {
            if ($payment->booking) {
                // Map detailed Stripe statuses to Booking-compatible statuses
                $status = $payment->status;
                
                // Keep the mapping strict to avoid enum errors in the bookings table
                $bookingStatus = match($status) {
                    'succeeded' => 'paid',
                    'refunded' => 'refunded',
                    'partially_refunded' => 'partially_refunded',
                    'failed' => 'failed',
                    default => 'pending', // handles requires_payment_method, processing, etc.
                };

                $payment->booking->update(['payment_status' => $bookingStatus]);
            }
        });
    }
}