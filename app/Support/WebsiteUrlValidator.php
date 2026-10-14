<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class WebsiteUrlValidator
{
    public function validate(string $url): string
    {
        $parts = parse_url($url);

        if (
            $parts === false ||
            ! in_array(
                strtolower($parts['scheme'] ?? ''),
                ['http', 'https'],
                true
            ) ||
            empty($parts['host']) ||
            isset($parts['user']) ||
            isset($parts['pass']) ||
            isset($parts['fragment'])
        ) {
            $this->reject();
        }

        $host = strtolower(rtrim($parts['host'], '.'));

        if (
            $host === '' ||
            str_contains($host, '%') ||
            str_contains($host, ':') && ! filter_var(
                trim($host, '[]'),
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV6
            )
        ) {
            $this->reject();
        }

        if (
            $host === 'localhost' ||
            str_ends_with($host, '.localhost') ||
            str_ends_with($host, '.local') ||
            str_ends_with($host, '.internal') ||
            str_ends_with($host, '.test')
        ) {
            $this->reject();
        }

        $ip = trim($host, '[]');

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            if (! $this->isPublicIp($ip)) {
                $this->reject();
            }
        } else {
            if (
                ! filter_var(
                    $host,
                    FILTER_VALIDATE_DOMAIN,
                    FILTER_FLAG_HOSTNAME
                )
            ) {
                $this->reject();
            }

            $this->rejectIfPrivateDnsTarget($host);
        }

        return $host;
    }

    private function rejectIfPrivateDnsTarget(string $host): void
    {
        $records = @dns_get_record(
            $host,
            DNS_A | DNS_AAAA
        );

        if ($records === false) {
            return;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if ($ip && ! $this->isPublicIp($ip)) {
                $this->reject();
            }
        }
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private function reject(): never
    {
        throw ValidationException::withMessages([
            'url' => 'Enter a valid public HTTP or HTTPS website URL.',
        ]);
    }
}
