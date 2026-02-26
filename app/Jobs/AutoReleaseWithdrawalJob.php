<?php

namespace App\Jobs;

use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoReleaseWithdrawalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Execute the job.
     * Finds all escrow records that have passed their 7-day auto-release window
     * and haven't been manually released by admin.
     */
    public function handle(WithdrawalService $withdrawalService): void
    {
        Log::info('AutoReleaseWithdrawalJob: Starting auto-release sweep at ' . now());

        $eligible = WithdrawalRequest::eligibleForAutoRelease()
            ->with('provider', 'booking')
            ->get();

        if ($eligible->isEmpty()) {
            Log::info('AutoReleaseWithdrawalJob: No eligible withdrawals to auto-release.');
            return;
        }

        $releasedCount = 0;
        $failedCount   = 0;

        foreach ($eligible as $withdrawal) {
            try {
                $withdrawalService->autoReleaseEscrow($withdrawal);
                $releasedCount++;
                Log::info("AutoReleaseWithdrawalJob: Released withdrawal #{$withdrawal->id} for provider #{$withdrawal->provider_id}, amount: \${$withdrawal->net_provider_amount}");
            } catch (\Exception $e) {
                $failedCount++;
                Log::error("AutoReleaseWithdrawalJob: Failed to release withdrawal #{$withdrawal->id}: " . $e->getMessage());
            }
        }

        Log::info("AutoReleaseWithdrawalJob: Complete. Released: {$releasedCount}, Failed: {$failedCount}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('AutoReleaseWithdrawalJob: Job failed completely - ' . $exception->getMessage());
    }
}
