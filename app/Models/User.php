<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens;

// class User extends Authenticatable
// {
//     use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

//     protected $fillable = [
//         'first_name',
//         'last_name',
//         'email',
//         'phone',
//         'password',
//         'user_type',
//         'profile_photo',
//         'bio',
//         'address',
//         'city',
//         'state',
//         'country',
//         'zip_code',
//         'latitude',
//         'longitude',
//         'status',
//         'is_verified',
//         'last_active_at',
//     ];

//     protected $hidden = [
//         'password',
//         'remember_token',
//     ];

//     protected $casts = [
//         'email_verified_at' => 'datetime',
//         'phone_verified_at' => 'datetime',
//         'last_active_at' => 'datetime',
//         'is_verified' => 'boolean',
//         'password' => 'hashed',
//     ];

//     // Relationships

//     /**
//      * Profile documents for verification
//      */
//     public function documents()
//     {
//         return $this->hasMany(ProfileDocument::class);
//     }

//     /**
//      * Service listings created by provider
//      */
//     public function listings()
//     {
//         return $this->hasMany(ServiceListing::class, 'provider_id');
//     }

//     /**
//      * Bookings as a client
//      */
//     public function clientBookings()
//     {
//         return $this->hasMany(Booking::class, 'client_id');
//     }

//     /**
//      * Bookings as a provider
//      */
//     public function providerBookings()
//     {
//         return $this->hasMany(Booking::class, 'provider_id');
//     }

//     /**
//      * Reviews given by this user
//      */
//     // public function reviewsGiven()
//     // {
//     //     return $this->hasMany(Review::class, 'reviewer_id');
//     // }

//     // /**
//     //  * Reviews received by this user (as provider)
//     //  */
//     // public function reviewsReceived()
//     // {
//     //     return $this->hasMany(Review::class, 'reviewee_id');
//     // }

//     // /**
//     //  * Payments made as client
//     //  */
//     // public function paymentsAsClient()
//     // {
//     //     return $this->hasMany(Payment::class, 'client_id');
//     // }

//     // /**
//     //  * Payments received as provider
//     //  */
//     // public function paymentsAsProvider()
//     // {
//     //     return $this->hasMany(Payment::class, 'provider_id');
//     // }

//     // /**
//     //  * Messages sent by user
//     //  */
//     // public function sentMessages()
//     // {
//     //     return $this->hasMany(Message::class, 'sender_id');
//     // }

//     // /**
//     //  * Messages received by user
//     //  */
//     // public function receivedMessages()
//     // {
//     //     return $this->hasMany(Message::class, 'receiver_id');
//     // }

//     // /**
//     //  * User notifications
//     //  */
//     // public function notifications()
//     // {
//     //     return $this->hasMany(Notification::class);
//     // }

//     // /**
//     //  * User subscription
//     //  */
//     // public function subscription()
//     // {
//     //     return $this->hasOne(UserSubscription::class)->latest();
//     // }

//     // // Helper Methods

//     // /**
//     //  * Check if user is a client
//     //  */
//     // public function isClient(): bool
//     // {
//     //     return $this->user_type === 'client';
//     // }

//     // /**
//     //  * Check if user is a provider
//     //  */
//     // public function isProvider(): bool
//     // {
//     //     return $this->user_type === 'provider';
//     // }

//     // /**
//     //  * Check if user is an admin
//     //  */
//     // public function isAdmin(): bool
//     // {
//     //     return $this->user_type === 'admin';
//     // }

//     // /**
//     //  * Check if user is verified
//     //  */
//     // public function isVerified(): bool
//     // {
//     //     return $this->is_verified && $this->email_verified_at !== null;
//     // }

//     // /**
//     //  * Get full name
//     //  */
//     // public function getFullNameAttribute(): string
//     // {
//     //     return $this->first_name . ' ' . $this->last_name;
//     // }

//     // /**
//     //  * Get average rating as provider
//     //  */
//     // public function getAverageRatingAttribute(): float
//     // {
//     //     return $this->reviewsReceived()->avg('rating') ?? 0;
//     // }

//     // /**
//     //  * Get total reviews count
//     //  */
//     // public function getTotalReviewsAttribute(): int
//     // {
//     //     return $this->reviewsReceived()->count();
//     // }

//     // /**
//     //  * Check if user has active subscription
//     //  */
//     public function hasActiveSubscription(): bool
//     {
//         return $this->subscription && 
//                $this->subscription->status === 'active' && 
//                $this->subscription->ends_at > now();
//     }

//     // /**
//     //  * Get unread messages count
//     //  */
//     // public function getUnreadMessagesCountAttribute(): int
//     // {
//     //     return $this->receivedMessages()->where('is_read', false)->count();
//     // }

//     // /**
//     //  * Get unread notifications count
//     //  */
//     // public function getUnreadNotificationsCountAttribute(): int
//     // {
//     //     return $this->notifications()->where('is_read', false)->count();
//     // }

//     // /**
//     //  * Scope: Active users
//     //  */
//     // public function scopeActive($query)
//     // {
//     //     return $query->where('status', 'active');
//     // }

//     // /**
//     //  * Scope: Verified users
//     //  */
//     // public function scopeVerified($query)
//     // {
//     //     return $query->where('is_verified', true);
//     // }

//     // /**
//     //  * Scope: Providers only
//     //  */
//     // public function scopeProviders($query)
//     // {
//     //     return $query->where('user_type', 'provider');
//     // }

//     // /**
//     //  * Scope: Clients only
//     //  */
//     // public function scopeClients($query)
//     // {
//     //     return $query->where('user_type', 'client');
//     // }
// }

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'user_type',
        'profile_photo',
        'bio',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'latitude',
        'longitude',
        'status',
        'is_verified',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_active_at' => 'datetime',
        'is_verified' => 'boolean',
        'password' => 'hashed',
    ];

    // Relationships

    /**
     * Service listings created by provider
     */
    public function listings()
    {
        return $this->hasMany(ServiceListing::class, 'provider_id');
    }

    /**
     * Profile documents for verification
     */
    public function documents()
    {
        return $this->hasMany(ProfileDocument::class);
    }

    // Helper Methods

    /**
     * Check if user is a client
     */
    public function isClient(): bool
    {
        return $this->user_type === 'client';
    }

    /**
     * Check if user is a provider
     */
    public function isProvider(): bool
    {
        return $this->user_type === 'provider';
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /**
     * Check if user is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified && $this->email_verified_at !== null;
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Scope: Active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Verified users
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Providers only
     */
    public function scopeProviders($query)
    {
        return $query->where('user_type', 'provider');
    }

    /**
     * Scope: Clients only
     */
    public function scopeClients($query)
    {
        return $query->where('user_type', 'client');
    }

    // Temporary simplified accessors until we build other modules
    
    public function getAverageRatingAttribute(): float
    {
        return 0; // Will be implemented with Review module
    }

    public function getTotalReviewsAttribute(): int
    {
        return 0; // Will be implemented with Review module
    }

    public function hasActiveSubscription(): bool
    {
        return false; // Will be implemented with Subscription module
    }

    public function getUnreadMessagesCountAttribute(): int
    {
        return 0; // Will be implemented with Messaging module
    }

    public function getUnreadNotificationsCountAttribute(): int
    {
        return 0; // Will be implemented with Notification module
    }
    public function bookings()
{
    return $this->hasMany(Booking::class, 'user_id', 'id');
}

}