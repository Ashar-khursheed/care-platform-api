<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payout extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'payment_id',
        'amount',
        'currency',
        'stripe_payout_id',
        'stripe_account_id',
        'status',
        'bank_name',
        'account_number_last4',
        'scheduled_at',
        'paid_at',
        'failed_at',
        'failure_reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'encrypted:array', // Encrypt sensitive bank details
    ];

    /**
     * Relationships
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
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

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Mark payout as paid
     */
    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Create transaction record
        Transaction::create([
            'user_id' => $this->provider_id,
            'payment_id' => $this->payment_id,
            'payout_id' => $this->id,
            'type' => 'payout',
            'amount' => $this->amount,
            'currency' => $this->currency,
            'direction' => 'credit',
            'status' => 'completed',
            'description' => "Payout for completed service",
        ]);
    }

    /**
     * Mark payout as failed
     */
    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => now(),
        ]);
    }
}