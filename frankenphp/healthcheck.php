#!/usr/bin/env php
<?php

declare(strict_types=1);

function normalizeTarget(?string $value): string
{
    $trimmedValue = trim((string) $value);

    if ($trimmedValue === '') {
        return 'localhost';
    }

    $segments = array_map('trim', explode(',', $trimmedValue));

    foreach ($segments as $segment) {
        if ($segment !== '') {
            return $segment;
        }
    }

    return 'localhost';
}

function hasScheme(string $value): bool
{
    return preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) === 1;
}

/**
 * @param array<string, int|string> $parts
 */
function withHealthPath(array $parts): array
{
    $path = (string) ($parts['path'] ?? '');

    if ($path === '' || $path === '/') {
        $parts['path'] = '/api/health';
    }

    return $parts;
}

/**
 * @param array<string, int|string> $parts
 */
function buildUrl(array $parts): string
{
    $scheme = (string) ($parts['scheme'] ?? 'https');
    $host = (string) ($parts['host'] ?? 'localhost');
    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
    $user = (string) ($parts['user'] ?? '');
    $password = isset($parts['pass']) ? ':' . (string) $parts['pass'] : '';
    $auth = $user !== '' ? $user . $password . '@' : '';
    $path = (string) ($parts['path'] ?? '/api/health');
    $query = isset($parts['query']) ? '?' . (string) $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . (string) $parts['fragment'] : '';

    return sprintf('%s://%s%s%s%s%s', $scheme, $auth, $host, $port, $path, $query . $fragment);
}

/**
 * @return list<string>
 */
function healthcheckCandidates(): array
{
    $configuredTarget = normalizeTarget(
        getenv('HEALTHCHECK_URL') ?: getenv('DEFAULT_URI') ?: getenv('SERVER_NAME') ?: 'localhost'
    );

    if (hasScheme($configuredTarget)) {
        $parts = parse_url($configuredTarget);

        if ($parts === false) {
            return [$configuredTarget];
        }

        return [buildUrl(withHealthPath($parts))];
    }

    $parts = parse_url('https://' . ltrim($configuredTarget, '/'));

    if ($parts === false) {
        $fallbackTarget = rtrim($configuredTarget, '/');

        if ($fallbackTarget === '') {
            $fallbackTarget = 'localhost';
        }

        if (! str_contains($fallbackTarget, '/')) {
            $fallbackTarget .= '/api/health';
        }

        return [
            'https://' . $fallbackTarget,
            'http://' . $fallbackTarget,
        ];
    }

    $parts = withHealthPath($parts);
    $preferredSchemes = ((int) ($parts['port'] ?? 0) === 80) ? ['http', 'https'] : ['https', 'http'];
    $candidates = [];

    foreach ($preferredSchemes as $scheme) {
        $candidates[] = buildUrl($parts + ['scheme' => $scheme]);
    }

    return $candidates;
}

function isHealthyResponse(string $url): bool
{
    $context = stream_context_create([
        'http' => [
            'ignore_errors' => true,
            'timeout' => 5,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $result = @file_get_contents($url, false, $context);
    $statusCode = null;

    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('{^HTTP/\S+\s+(\d+)}', $header, $matches) === 1) {
            $statusCode = (int) $matches[1];
            break;
        }
    }

    if ($statusCode !== null) {
        return $statusCode >= 200 && $statusCode < 400;
    }

    return $result !== false;
}

foreach (healthcheckCandidates() as $candidate) {
    if (isHealthyResponse($candidate)) {
        exit(0);
    }
}

exit(1);
