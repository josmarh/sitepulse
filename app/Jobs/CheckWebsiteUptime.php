<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\Monitoring\UptimeMonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use DB;

class CheckWebsiteUptime implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 40;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $websiteId,
        public readonly string $claimToken
    ) {
        $this->onQueue('monitoring');
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Execute the job.
     */
    public function handle(
        UptimeMonitoringService $monitoringService
    ): void
    {
        $website = Website::find($this->websiteId);

        // A website may have been deleted or paused after dispatch.
        if (! $website) {
            return;
        }

        if ($website->check_claim_token !== $this->claimToken) {
            return;
        }

        if (! $website->is_active) {
            $this->releaseClaim($website, false);
            return;
        }

        $monitoringService->check($website);

        // Only release after the monitoring service returns.
        // If it throws unexpectedly, Laravel can retry the job.
        $this->releaseClaim($website, true);
    }

    public function failed(?Throwable $exception): void
    {
        $website = Website::find($this->websiteId);

        if ($website) {
            $this->releaseClaim($website, $website->is_active);
        }

        logger()->warning('SitePulse uptime job exhausted retries.', [
            'website_id' => $this->websiteId,
            'exception' => $exception?->getMessage(),
        ]);
    }

    private function releaseClaim(
        Website $website,
        bool $scheduleNextCheck
    ): void {
        DB::transaction(function () use (
            $website,
            $scheduleNextCheck
        ) {
            $locked = Website::query()
                ->whereKey($website->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $locked ||
                $locked->check_claim_token !== $this->claimToken
            ) {
                return;
            }

            $locked->check_claim_token = null;
            $locked->check_claimed_at = null;

            $locked->next_check_at = $scheduleNextCheck
                && $locked->is_active
                    ? now()->addMinutes(
                        $locked->check_interval_minutes
                    )
                    : null;

            $locked->save();
        });
    }
}
