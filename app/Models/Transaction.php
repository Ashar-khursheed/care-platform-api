<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'payment_id',
        'payout_id',
        'booking_id',
        'type',
        'amount',
        'currency',
        'direction',
        'balance_after',
        'status',
        'description',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Polymorphic relationship to reference
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCredit($query)
    {
        return $query->where('direction', 'credit');
    }

    public function scopeDebit($query)
    {
        return $query->where('direction', 'debit');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Helper Methods
     */
    public function isCredit()
    {
        return $this->direction === 'credit';
    }

    public function isDebit()
    {
        return $this->direction === 'debit';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Get user's current balance
     */
    public static function getUserBalance($userId)
    {
        $credits = self::where('user_id', $userId)
            ->where('direction', 'credit')
            ->where('status', 'completed')
            ->sum('amount');

        $debits = self::where('user_id', $userId)
            ->where('direction', 'debit')
            ->where('status', 'completed')
            ->sum('amount');

        return round($credits - $debits, 2);
    }
}