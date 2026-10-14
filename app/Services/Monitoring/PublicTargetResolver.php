<?php

namespace App\Services\Monitoring;

use InvalidArgumentException;

class PublicTargetResolver
{
    /**
     * Resolve a URL to a public IP address and port.
     *
     * Every returned address must be public. We reject mixed
     * public/private DNS answers rather than choosing one silently.
     *
     * @return array{host: string, port: int, ip: string}
     */
    public function resolve(string $url): array
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
            throw new InvalidArgumentException('Invalid monitoring URL.');
        }

        $host = strtolower(rtrim(trim($parts['host'], '[]'), '.'));
        $scheme = strtolower($parts['scheme']);
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        // Restrict checks to standard website ports.
        if (! in_array($port, [80, 443], true)) {
            throw new InvalidArgumentException('Unsupported website port.');
        }

        if (
            $host === '' ||
            str_contains($host, '%') ||
            $host === 'localhost' ||
            str_ends_with($host, '.localhost') ||
            str_ends_with($host, '.local') ||
            str_ends_with($host, '.internal') ||
            str_ends_with($host, '.test')
        ) {
            throw new InvalidArgumentException('Restricted hostname.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! $this->isPublicIp($host)) {
                throw new InvalidArgumentException('Restricted IP address.');
            }

            return [
                'host' => $host,
                'port' => $port,
                'ip' => $host,
            ];
        }

        if (! filter_var(
            $host,
            FILTER_VALIDATE_DOMAIN,
            FILTER_FLAG_HOSTNAME
        )) {
            throw new InvalidArgumentException('Invalid hostname.');
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if (! is_array($records) || $records === []) {
            throw new InvalidArgumentException('Hostname did not resolve.');
        }

        $addresses = [];

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (! $ip) {
                continue;
            }

            if (! $this->isPublicIp($ip)) {
                throw new InvalidArgumentException(
                    'Hostname resolves to a restricted address.'
                );
            }

            $addresses[] = $ip;
        }

        if ($addresses === []) {
            throw new InvalidArgumentException('No usable IP addresses.');
        }

        return [
            'host' => $host,
            'port' => $port,
            'ip' => $addresses[0],
        ];
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
