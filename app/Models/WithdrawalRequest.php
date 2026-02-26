<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawalRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'withdrawal_requests';

    protected $fillable = [
        'provider_id',
        'booking_id',
        'gross_amount',
        'client_fee',
        'provider_fee',
        'platform_fee_total',
        'net_provider_amount',
        'currency',
        'escrow_status',
        'withdrawal_status',
        'escrow_held_at',
        'auto_release_at',
        'escrow_released_at',
        'withdrawal_requested_at',
        'withdrawal_processed_at',
        'bank_name',
        'account_number_last4',
        'rejection_reason',
        'notes',
        'metadata',
        'approved_by',
        'transaction_reference',
    ];

    protected $casts = [
        'gross_amount'              => 'decimal:2',
        'client_fee'                => 'decimal:2',
        'provider_fee'              => 'decimal:2',
        'platform_fee_total'        => 'decimal:2',
        'net_provider_amount'       => 'decimal:2',
        'escrow_held_at'            => 'datetime',
        'auto_release_at'           => 'datetime',
        'escrow_released_at'        => 'datetime',
        'withdrawal_requested_at'   => 'datetime',
        'withdrawal_processed_at'   => 'datetime',
        'metadata'                  => 'array',
    ];

    // =============================================
    // Relationships
    // =============================================

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function approvedByAdmin()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // =============================================
    // Status Helpers
    // =============================================

    public function isEscrowHolding(): bool
    {
        return $this->escrow_status === 'holding';
    }

    public function isEscrowReleased(): bool
    {
        return $this->escrow_status === 'released';
    }

    public function isWithdrawalNone(): bool
    {
        return $this->withdrawal_status === 'none';
    }

    public function isWithdrawalRequested(): bool
    {
        return $this->withdrawal_status === 'requested';
    }

    public function isWithdrawalPaid(): bool
    {
        return $this->withdrawal_status === 'paid';
    }

    public function isWithdrawalRejected(): bool
    {
        return $this->withdrawal_status === 'rejected';
    }

    /**
     * Provider can request withdrawal if:
     * - Escrow is still holding (or released but not yet paid)
     * - Withdrawal not yet requested/paid
     */
    public function canRequestWithdrawal(): bool
    {
        return in_array($this->withdrawal_status, ['none', 'rejected'])
            && $this->escrow_status === 'holding';
    }

    /**
     * Can be cancelled by provider if status is 'requested'
     */
    public function canCancelWithdrawal(): bool
    {
        return $this->withdrawal_status === 'requested';
    }

    /**
     * Eligible for auto-release: escrow is holding AND auto_release_at has passed
     */
    public function isEligibleForAutoRelease(): bool
    {
        return $this->escrow_status === 'holding'
            && $this->auto_release_at !== null
            && $this->auto_release_at->isPast();
    }

    // =============================================
    // Commission Calculations (Static)
    // =============================================

    /**
     * 10% platform fee charged TO the client (on top of gross booking amount)
     */
    public static function calculateClientFee(float $grossAmount): float
    {
        return round($grossAmount * 0.10, 2);
    }

    /**
     * 10% platform fee deducted FROM the provider earnings
     */
    public static function calculateProviderFee(float $grossAmount): float
    {
        return round($grossAmount * 0.10, 2);
    }

    /**
     * What the provider actually receives: gross - provider_fee
     */
    public static function calculateNetProviderAmount(float $grossAmount): float
    {
        $providerFee = self::calculateProviderFee($grossAmount);
        return round($grossAmount - $providerFee, 2);
    }

    /**
     * Full breakdown of fees for a given gross booking amount
     *
     * @return array{client_fee: float, provider_fee: float, platform_fee_total: float, net_provider_amount: float, client_total_charge: float}
     */
    public static function calculateFees(float $grossAmount): array
    {
        $clientFee        = self::calculateClientFee($grossAmount);
        $providerFee      = self::calculateProviderFee($grossAmount);
        $platformFeeTotal = round($clientFee + $providerFee, 2);
        $netProvider      = self::calculateNetProviderAmount($grossAmount);
        $clientTotalCharge = round($grossAmount + $clientFee, 2);

        return [
            'gross_amount'       => $grossAmount,
            'client_fee'         => $clientFee,         // +10%
            'provider_fee'       => $providerFee,       // -10%
            'platform_fee_total' => $platformFeeTotal,  // 20% of gross
            'net_provider_amount'=> $netProvider,        // 90% of gross
            'client_total_charge'=> $clientTotalCharge, // 110% of gross
        ];
    }

    // =============================================
    // Scopes
    // =============================================

    public function scopeHolding($query)
    {
        return $query->where('escrow_status', 'holding');
    }

    public function scopeReleased($query)
    {
        return $query->where('escrow_status', 'released');
    }

    public function scopePendingWithdrawal($query)
    {
        return $query->where('withdrawal_status', 'requested');
    }

    public function scopePaid($query)
    {
        return $query->where('withdrawal_status', 'paid');
    }

    public function scopeEligibleForAutoRelease($query)
    {
        return $query->where('escrow_status', 'holding')
                     ->where('auto_release_at', '<=', now())
                     ->whereNotNull('auto_release_at');
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }
}
