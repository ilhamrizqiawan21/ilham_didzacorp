<?php

namespace App\Support;

class SensitiveDataRedactor
{
    public const REDACTED = '[redacted]';

    private const SENSITIVE_KEYS = [
        '_token',
        'access_token',
        'api_key',
        'api_token',
        'authorization',
        'client_secret',
        'cookie',
        'current_password',
        'key',
        'password',
        'password_confirmation',
        'refresh_token',
        'remember_token',
        'secret',
        'set-cookie',
        'token',
    ];

    public static function text(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $redacted = preg_replace(
            '/\b(Authorization:\s*(?:Bearer|Basic)\s+)[A-Za-z0-9._~+\/=-]+/i',
            '$1'.self::REDACTED,
            $value,
        ) ?? $value;

        $keys = implode('|', array_map(static fn (string $key) => preg_quote($key, '/'), self::SENSITIVE_KEYS));

        return preg_replace(
            '/(["\']?\b(?:'.$keys.')\b["\']?\s*[:=]\s*["\']?)([^"\'\s,&;}\]]+)/i',
            '$1'.self::REDACTED,
            $redacted,
        ) ?? $redacted;
    }

    public static function url(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return self::text($url);
        }

        if (isset($parts['user'])) {
            $parts['user'] = self::REDACTED;
        }

        if (isset($parts['pass'])) {
            $parts['pass'] = self::REDACTED;
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $parts['query'] = http_build_query(self::redactArray($query), '', '&', PHP_QUERY_RFC3986);
        }

        return self::text(self::buildUrl($parts));
    }

    private static function redactArray(array $values): array
    {
        foreach ($values as $key => $value) {
            if (self::isSensitiveKey((string) $key)) {
                $values[$key] = self::REDACTED;
                continue;
            }

            if (is_array($value)) {
                $values[$key] = self::redactArray($value);
            }
        }

        return $values;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($normalized === $sensitiveKey || str_contains($normalized, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private static function buildUrl(array $parts): string
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'].'://';
        }

        if (isset($parts['user'])) {
            $url .= $parts['user'];

            if (isset($parts['pass'])) {
                $url .= ':'.$parts['pass'];
            }

            $url .= '@';
        }

        if (isset($parts['host'])) {
            $url .= $parts['host'];
        }

        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }

        $url .= $parts['path'] ?? '';

        if (($parts['query'] ?? '') !== '') {
            $url .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }
}
