<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'provider_id',
        'amount',
        'message',
        'status',
    ];

    /**
     * Get the listing that was bid on
     */
    public function listing()
    {
        return $this->belongsTo(ServiceListing::class, 'listing_id');
    }

    /**
     * Get the provider who made the bid
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
