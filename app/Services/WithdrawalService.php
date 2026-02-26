<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalService
{
    /**
     * Called when a booking is marked as completed.
     * Creates an escrow (WithdrawalRequest) record with a 7-day auto-release window.
     *
     * Commission Model (Option A):
     *   - Client pays: gross + 10% (client_fee)
     *   - Provider earns: gross - 10% (provider_fee)
     *   - Platform collects: client_fee + provider_fee (~20% of gross)
     */
    public function createEscrowOnBookingComplete(Booking $booking): WithdrawalRequest
    {
        // Avoid duplicate escrow records for the same booking
        $existing = WithdrawalRequest::where('booking_id', $booking->id)->first();
        if ($existing) {
            return $existing;
        }

        $grossAmount = (float) $booking->total_amount;
        $fees        = WithdrawalRequest::calculateFees($grossAmount);

        $escrowHeldAt  = now();
        $autoReleaseAt = $escrowHeldAt->copy()->addDays(7);

        $withdrawal = WithdrawalRequest::create([
            'provider_id'         => $booking->provider_id,
            'booking_id'          => $booking->id,
            'gross_amount'        => $fees['gross_amount'],
            'client_fee'          => $fees['client_fee'],
            'provider_fee'        => $fees['provider_fee'],
            'platform_fee_total'  => $fees['platform_fee_total'],
            'net_provider_amount' => $fees['net_provider_amount'],
            'currency'            => 'USD',
            'escrow_status'       => 'holding',
            'withdrawal_status'   => 'none',
            'escrow_held_at'      => $escrowHeldAt,
            'auto_release_at'     => $autoReleaseAt,
        ]);

        Log::info("Escrow created for booking #{$booking->id}, provider #{$booking->provider_id}, auto-release at {$autoReleaseAt}");

        return $withdrawal;
    }

    /**
     * Provider requests a withdrawal for a specific escrow record.
     */
    public function requestWithdrawal(WithdrawalRequest $withdrawal, array $bankDetails = []): WithdrawalRequest
    {
        if (!$withdrawal->canRequestWithdrawal()) {
            throw new \Exception("This withdrawal cannot be requested in its current state: escrow={$withdrawal->escrow_status}, withdrawal={$withdrawal->withdrawal_status}");
        }

        $accountNumber = $bankDetails['account_number'] ?? null;

        $withdrawal->update([
            'withdrawal_status'        => 'requested',
            'withdrawal_requested_at'  => now(),
            'bank_name'                => $bankDetails['bank_name'] ?? null,
            'account_number_last4'     => $accountNumber ? substr($accountNumber, -4) : null,
            'metadata'                 => array_merge($withdrawal->metadata ?? [], [
                'bank_details' => [
                    'bank_name'      => $bankDetails['bank_name'] ?? null,
                    'routing_number' => $bankDetails['routing_number'] ?? null,
                    // Stored for admin reference only — do not expose in API response
                    'account_last4'  => $accountNumber ? substr($accountNumber, -4) : null,
                ],
            ]),
        ]);

        Log::info("Withdrawal requested for escrow #{$withdrawal->id}, provider #{$withdrawal->provider_id}, amount: {$withdrawal->net_provider_amount}");

        return $withdrawal->fresh();
    }

    /**
     * Admin manually approves and releases the withdrawal.
     */
    public function approveWithdrawal(WithdrawalRequest $withdrawal, User $admin, array $options = []): WithdrawalRequest
    {
        if (!in_array($withdrawal->withdrawal_status, ['requested', 'none'])) {
            throw new \Exception("Only requested or pending withdrawals can be approved.");
        }

        DB::beginTransaction();
        try {
            $withdrawal->update([
                'escrow_status'            => 'released',
                'withdrawal_status'        => 'paid',
                'escrow_released_at'       => now(),
                'withdrawal_processed_at'  => now(),
                'approved_by'              => $admin->id,
                'transaction_reference'    => $options['transaction_reference'] ?? null,
                'notes'                    => $options['notes'] ?? null,
                'metadata'                 => array_merge($withdrawal->metadata ?? [], [
                    'approved_by'           => $admin->id,
                    'approved_by_name'      => $admin->first_name . ' ' . $admin->last_name,
                    'transaction_reference' => $options['transaction_reference'] ?? null,
                    'admin_notes'           => $options['notes'] ?? null,
                    'approved_at'           => now()->toIso8601String(),
                    'auto_released'         => false,
                ]),
            ]);

            // Create a ledger transaction for the provider
            Transaction::create([
                'user_id'     => $withdrawal->provider_id,
                'booking_id'  => $withdrawal->booking_id,
                'type'        => 'payout',
                'amount'      => $withdrawal->net_provider_amount,
                'currency'    => $withdrawal->currency,
                'direction'   => 'credit',
                'status'      => 'completed',
                'description' => "Withdrawal released for booking #{$withdrawal->booking_id}",
                'metadata'    => [
                    'withdrawal_request_id' => $withdrawal->id,
                    'transaction_reference' => $options['transaction_reference'] ?? null,
                ],
            ]);

            // Platform fee transaction (debit for record-keeping)
            Transaction::create([
                'user_id'     => 1, // Admin/platform user
                'booking_id'  => $withdrawal->booking_id,
                'type'        => 'platform_fee',
                'amount'      => $withdrawal->platform_fee_total,
                'currency'    => $withdrawal->currency,
                'direction'   => 'credit',
                'status'      => 'completed',
                'description' => "Platform commission for booking #{$withdrawal->booking_id} (10% client + 10% provider)",
                'metadata'    => [
                    'withdrawal_request_id' => $withdrawal->id,
                    'client_fee'            => $withdrawal->client_fee,
                    'provider_fee'          => $withdrawal->provider_fee,
                ],
            ]);

            DB::commit();

            Log::info("Withdrawal #{$withdrawal->id} approved by admin #{$admin->id}, released \${$withdrawal->net_provider_amount} to provider #{$withdrawal->provider_id}");

            return $withdrawal->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Withdrawal approval failed #{$withdrawal->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Admin rejects a withdrawal request.
     */
    public function rejectWithdrawal(WithdrawalRequest $withdrawal, User $admin, string $reason): WithdrawalRequest
    {
        if ($withdrawal->withdrawal_status !== 'requested') {
            throw new \Exception("Only requested withdrawals can be rejected.");
        }

        $withdrawal->update([
            'withdrawal_status'       => 'rejected',
            'withdrawal_processed_at' => now(),
            'rejection_reason'        => $reason,
            'approved_by'             => $admin->id,
            'metadata'                => array_merge($withdrawal->metadata ?? [], [
                'rejected_by'      => $admin->id,
                'rejected_by_name' => $admin->first_name . ' ' . $admin->last_name,
                'rejection_reason' => $reason,
                'rejected_at'      => now()->toIso8601String(),
            ]),
        ]);

        Log::info("Withdrawal #{$withdrawal->id} rejected by admin #{$admin->id}: {$reason}");

        return $withdrawal->fresh();
    }

    /**
     * Auto-release a single escrow record (called by scheduled job).
     */
    public function autoReleaseEscrow(WithdrawalRequest $withdrawal): WithdrawalRequest
    {
        if (!$withdrawal->isEligibleForAutoRelease()) {
            Log::warning("Withdrawal #{$withdrawal->id} is not eligible for auto-release.");
            return $withdrawal;
        }

        DB::beginTransaction();
        try {
            $withdrawal->update([
                'escrow_status'           => 'released',
                'withdrawal_status'       => 'paid',
                'escrow_released_at'      => now(),
                'withdrawal_processed_at' => now(),
                'notes'                   => 'Auto-released after 7-day escrow window',
                'metadata'                => array_merge($withdrawal->metadata ?? [], [
                    'auto_released'      => true,
                    'auto_released_at'   => now()->toIso8601String(),
                    'auto_release_notes' => 'Funds automatically released after 7-day window with no admin action.',
                ]),
            ]);

            // Provider ledger transaction
            Transaction::create([
                'user_id'     => $withdrawal->provider_id,
                'booking_id'  => $withdrawal->booking_id,
                'type'        => 'payout',
                'amount'      => $withdrawal->net_provider_amount,
                'currency'    => $withdrawal->currency,
                'direction'   => 'credit',
                'status'      => 'completed',
                'description' => "Auto-released withdrawal for booking #{$withdrawal->booking_id} (7-day window expired)",
                'metadata'    => ['withdrawal_request_id' => $withdrawal->id, 'auto_released' => true],
            ]);

            // Platform fee ledger
            Transaction::create([
                'user_id'     => 1,
                'booking_id'  => $withdrawal->booking_id,
                'type'        => 'platform_fee',
                'amount'      => $withdrawal->platform_fee_total,
                'currency'    => $withdrawal->currency,
                'direction'   => 'credit',
                'status'      => 'completed',
                'description' => "Platform fee for booking #{$withdrawal->booking_id} (auto-released)",
                'metadata'    => [
                    'withdrawal_request_id' => $withdrawal->id,
                    'client_fee'            => $withdrawal->client_fee,
                    'provider_fee'          => $withdrawal->provider_fee,
                    'auto_released'         => true,
                ],
            ]);

            DB::commit();

            Log::info("Auto-released withdrawal #{$withdrawal->id} for provider #{$withdrawal->provider_id}, amount: \${$withdrawal->net_provider_amount}");

            return $withdrawal->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Auto-release failed for withdrawal #{$withdrawal->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a summary of the provider's balance across all withdrawals.
     */
    public function getProviderBalance(int $providerId): array
    {
        // Total earned (net of provider fee across all released/paid withdrawals)
        $totalEarned = WithdrawalRequest::byProvider($providerId)
            ->whereIn('withdrawal_status', ['paid'])
            ->sum('net_provider_amount');

        // In escrow (holding, not yet released)
        $inEscrow = WithdrawalRequest::byProvider($providerId)
            ->holding()
            ->whereIn('withdrawal_status', ['none', 'requested'])
            ->sum('net_provider_amount');

        // Pending withdrawal (requested but not approved)
        $pendingWithdrawal = WithdrawalRequest::byProvider($providerId)
            ->pendingWithdrawal()
            ->sum('net_provider_amount');

        // Available to withdraw (escrow released but not yet paid - shouldn't normally happen in the flow)
        $available = WithdrawalRequest::byProvider($providerId)
            ->holding()
            ->where('withdrawal_status', 'none')
            ->sum('net_provider_amount');

        return [
            'total_earned'       => round($totalEarned, 2),
            'in_escrow'          => round($inEscrow, 2),
            'pending_withdrawal' => round($pendingWithdrawal, 2),
            'available_balance'  => round($available, 2),
            'currency'           => 'USD',
        ];
    }
}
