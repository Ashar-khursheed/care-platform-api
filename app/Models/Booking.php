<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'provider_id',
        'listing_id',
        'booking_date',
        'start_time',
        'end_time',
        'hours',
        'hourly_rate',
        'total_amount',
        'service_location',
        'special_requirements',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'completed_at',
        'payment_status',
        'payment_method',
        'transaction_id',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Get the client who made the booking
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
    /**
     * Get the provider for this booking
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the service listing
     */
    public function listing()
    {
        return $this->belongsTo(ServiceListing::class, 'listing_id');
    }

    /**
     * Get the user who cancelled the booking
     */
    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the payments for this booking
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'booking_id');
    }

    /**
     * Get the latest payment for this booking
     */
    public function latestPayment()
    {
        return $this->hasOne(Payment::class, 'booking_id')->latestOfMany();
    }

    /**
     * Check if booking is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if booking is accepted
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if booking is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if booking is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if booking is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if booking can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'accepted']);
    }

    /**
     * Check if booking can be accepted
     */
    public function canBeAccepted(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Scope: Pending bookings
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Accepted bookings
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope: In progress bookings
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Completed bookings
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Cancelled bookings
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope: By client
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope: By provider
     */
    public function scopeByProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope: Upcoming bookings
     */
    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
                    ->whereIn('status', ['pending', 'accepted', 'in_progress'])
                    ->orderBy('booking_date', 'asc')
                    ->orderBy('start_time', 'asc');
    }

    /**
     * Scope: Past bookings
     */
    public function scopePast($query)
    {
        return $query->where(function($q) {
            $q->where('booking_date', '<', now()->toDateString())
              ->orWhereIn('status', ['completed', 'cancelled', 'rejected']);
        })->orderBy('booking_date', 'desc');
    }
}