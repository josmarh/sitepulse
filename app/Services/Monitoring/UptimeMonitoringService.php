<?php

namespace App\Services\Monitoring;

use App\Models\MonitoringCheck;
use App\Models\Website;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class UptimeMonitoringService
{
    public function __construct(
        private readonly PublicTargetResolver $targetResolver
    ) {}

    public function check(Website $website): MonitoringCheck
    {
        $startedAt = hrtime(true);

        $status = 'down';
        $httpStatusCode = null;
        $errorMessage = null;
        $resolvedIp = null;

        try {
            $target = $this->targetResolver->resolve($website->url);
            $resolvedIp = $target['ip'];

            $host = $target['host'];
            $port = $target['port'];
            $ip = $target['ip'];

            // Pin the hostname to the validated IP address.
            // TLS certificate validation still uses the original hostname.
            $resolveAddress = str_contains($ip, ':')
                ? "{$host}:{$port}:[{$ip}]"
                : "{$host}:{$port}:{$ip}";

            $response = Http::withOptions([
                'allow_redirects' => false,
                'connect_timeout' => min(5, $website->timeout_seconds),
                'timeout' => $website->timeout_seconds,
                'stream' => true,
                'verify' => true,
                'curl' => [
                    CURLOPT_RESOLVE => [$resolveAddress],
                    CURLOPT_PROXY => '',
                ],
            ])
                ->withHeaders([
                    'User-Agent' => 'SitePulse-Monitor/1.0',
                    'Range' => 'bytes=0-0',
                ])
                ->get($website->url);

            $httpStatusCode = $response->status();

            // The streaming response is no longer needed.
            // Close it without downloading an unnecessary response body.
            $response->toPsrResponse()->getBody()->close();

            $status = $httpStatusCode >= 200
                && $httpStatusCode < 400
                    ? 'up'
                    : 'down';

            if ($status === 'down') {
                $errorMessage = "HTTP status code: {$httpStatusCode}";
            }
        } catch (InvalidArgumentException $exception) {
            $errorMessage = 'Target rejected by network safety checks.';
        } catch (ConnectionException $exception) {
            $errorMessage = 'Connection failed or timed out.';
        } catch (Throwable $exception) {
            Log::warning('SitePulse uptime check failed.', [
                'website_id' => $website->id,
                'exception' => $exception::class,
            ]);

            $errorMessage = 'The website check could not be completed.';
        }

        $responseTimeMs = (int) round(
            (hrtime(true) - $startedAt) / 1_000_000
        );

        return DB::transaction(function () use (
            $website,
            $status,
            $httpStatusCode,
            $errorMessage,
            $responseTimeMs,
            $resolvedIp
        ) {
            $check = $website->monitoringChecks()->create([
                'check_type' => 'uptime',
                'status' => $status,
                'http_status_code' => $httpStatusCode,
                'response_time_ms' => $responseTimeMs,
                'error_message' => $errorMessage,
                'metadata' => [
                    'resolved_ip' => $resolvedIp,
                ],
                'checked_at' => now(),
            ]);

            $website->update([
                'current_status' => $status,
                'last_checked_at' => $check->checked_at,
                'next_check_at' => $website->is_active
                    ? now()->addMinutes(
                        $website->check_interval_minutes
                    )
                    : null,
            ]);

            return $check;
        });
    }
}
