<?php

namespace App\Console\Commands;

use App\Jobs\CheckWebsiteUptime;
use App\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DispatchDueWebsiteChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitepulse:dispatch-due-checks
                            {--limit=50 : Maximum websites to dispatch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue uptime checks for websites that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $staleBefore = $now->copy()->subMinutes(10);
        $limit = max(1, min(100, (int) $this->option('limit')));

        $ids = Website::query()
            ->where('is_active', true)
            ->where(function ($query) use ($now, $staleBefore) {
                $query
                    ->where(function ($due) use ($now) {
                        $due->whereNull('check_claim_token')
                            ->whereNotNull('next_check_at')
                            ->where('next_check_at', '<=', $now);
                    })
                    ->orWhere(function ($stale) use ($staleBefore) {
                        $stale->whereNotNull('check_claim_token')
                            ->where('check_claimed_at', '<=', $staleBefore);
                    });
            })
            ->orderBy('next_check_at')
            ->limit($limit)
            ->pluck('id');

        $dispatched = 0;

        foreach ($ids as $id) {
            $token = (string) Str::uuid();

            $claimed = DB::transaction(function () use (
                $id,
                $token,
                $staleBefore
            ) {
                $website = Website::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (! $website || ! $website->is_active) {
                    return false;
                }

                $isDue = $website->check_claim_token === null
                    && $website->next_check_at !== null
                    && $website->next_check_at->lte(now());

                $isStale = $website->check_claim_token !== null
                    && $website->check_claimed_at !== null
                    && $website->check_claimed_at->lte($staleBefore);

                if (! $isDue && ! $isStale) {
                    return false;
                }

                $website->check_claim_token = $token;
                $website->check_claimed_at = now();

                // Reserve the next slot while this attempt is queued.
                $website->next_check_at = now()->addMinutes(
                    $website->check_interval_minutes
                );

                $website->save();

                return true;
            });

            if (! $claimed) {
                continue;
            }

            try {
                CheckWebsiteUptime::dispatch((int) $id, $token);
                $dispatched++;
            } catch (Throwable $exception) {
                // Queue insertion failed; make this website eligible again.
                DB::transaction(function () use ($id, $token) {
                    $website = Website::query()
                        ->whereKey($id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        $website &&
                        $website->check_claim_token === $token
                    ) {
                        $website->check_claim_token = null;
                        $website->check_claimed_at = null;
                        $website->next_check_at = $website->is_active
                            ? now()
                            : null;
                        $website->save();
                    }
                });

                report($exception);
            }
        }

        $this->info("Queued {$dispatched} uptime check(s).");

        return self::SUCCESS;
    }
}
