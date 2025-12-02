<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'client_id',
        'provider_id',
        'listing_id',
        'rating',
        'comment',
        'provider_response',
        'response_date',
        'status',
        'rejection_reason',
        'moderated_by',
        'moderated_at',
        'is_flagged',
        'flag_reason',
        'helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'is_flagged' => 'boolean',
        'response_date' => 'datetime',
        'moderated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function listing()
    {
        return $this->belongsTo(ServiceListing::class, 'listing_id');
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function scopeByProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByListing($query, $listingId)
    {
        return $query->where('listing_id', $listingId);
    }

    public function scopeWithRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Helper Methods
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function hasResponse()
    {
        return !is_null($this->provider_response);
    }

    public function canBeEdited()
    {
        // Client can edit within 24 hours
        return $this->created_at->diffInHours(now()) < 24;
    }

    public function canBeDeleted()
    {
        // Client can delete within 48 hours
        return $this->created_at->diffInHours(now()) < 48;
    }

    /**
     * Auto-update provider ratings when review is saved
     */
    protected static function booted()
    {
        static::created(function ($review) {
            if ($review->status === 'approved') {
                $review->updateProviderRating();
            }
        });

        static::updated(function ($review) {
            if ($review->wasChanged('status') || $review->wasChanged('rating')) {
                $review->updateProviderRating();
            }
        });

        static::deleted(function ($review) {
            $review->updateProviderRating();
        });
    }

    /**
     * Update provider's average rating
     */
    public function updateProviderRating()
    {
        $provider = $this->provider;
        
        $averageRating = Review::where('provider_id', $provider->id)
            ->approved()
            ->avg('rating');
        
        $reviewCount = Review::where('provider_id', $provider->id)
            ->approved()
            ->count();

        $provider->update([
            'rating' => round($averageRating, 2),
            'reviews_count' => $reviewCount,
        ]);

        // Also update the listing rating if applicable
        if ($this->listing_id) {
            $listingAverage = Review::where('listing_id', $this->listing_id)
                ->approved()
                ->avg('rating');
            
            $listingReviewCount = Review::where('listing_id', $this->listing_id)
                ->approved()
                ->count();

            $this->listing->update([
                'rating' => round($listingAverage, 2),
                'reviews_count' => $listingReviewCount,
            ]);
        }
    }
}