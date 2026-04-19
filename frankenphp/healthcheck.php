#!/usr/bin/env php
<?php

declare(strict_types=1);

$normalizeTarget = static function (?string $value): string {
    $trimmedValue = trim((string) $value);

    if ($trimmedValue === '') {
        return 'localhost';
    }

    foreach (array_map('trim', explode(',', $trimmedValue)) as $segment) {
        if ($segment !== '') {
            return $segment;
        }
    }

    return 'localhost';
};

$configuredTarget = (static function () use ($normalizeTarget): string {
    foreach (['HEALTHCHECK_URL', 'DEFAULT_URI', 'SERVER_NAME'] as $envName) {
        $value = getenv($envName);

        if (! is_string($value) || trim($value) === '') {
            continue;
        }

        return $normalizeTarget($value);
    }

    return 'localhost';
})();

$hasScheme = static function (string $value): bool {
    return preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) === 1;
};

/**
 * @param array<string, int|string> $parts
 *
 * @return array<string, int|string>
 */
$withHealthPath = static function (array $parts): array {
    $path = (string) ($parts['path'] ?? '');

    if ($path === '' || $path === '/') {
        $parts['path'] = '/api/health';
    }

    return $parts;
};

/**
 * @param array<string, int|string> $parts
 */
$buildUrl = static function (array $parts): string {
    $scheme = (string) ($parts['scheme'] ?? 'https');
    $host = (string) ($parts['host'] ?? 'localhost');
    $port = isset($parts['port']) ? ':' . (string) ((int) $parts['port']) : '';
    $user = (string) ($parts['user'] ?? '');
    $password = isset($parts['pass']) ? ':' . (string) $parts['pass'] : '';
    $auth = $user === '' ? '' : $user . $password . '@';
    $path = (string) ($parts['path'] ?? '/api/health');
    $query = isset($parts['query']) ? '?' . (string) $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . (string) $parts['fragment'] : '';

    return sprintf(
        '%s://%s%s%s%s%s',
        $scheme,
        $auth,
        $host,
        $port,
        $path,
        $query . $fragment
    );
};

$fallbackTarget = static function (string $target): string {
    $normalizedTarget = rtrim($target, '/');

    if ($normalizedTarget === '') {
        $normalizedTarget = 'localhost';
    }

    if (! str_contains($normalizedTarget, '/')) {
        $normalizedTarget .= '/api/health';
    }

    return $normalizedTarget;
};

/**
 * @param array<string, int|string> $parts
 *
 * @return list<string>
 */
$buildSchemeCandidates = static function (array $parts) use ($buildUrl): array {
    $schemes = (int) ($parts['port'] ?? 0) === 80 ? ['http', 'https'] : ['https', 'http'];
    $candidates = [];

    foreach ($schemes as $scheme) {
        $candidates[] = $buildUrl($parts + ['scheme' => $scheme]);
    }

    return $candidates;
};

$healthcheckCandidates = (static function () use (
    $buildSchemeCandidates,
    $buildUrl,
    $configuredTarget,
    $fallbackTarget,
    $hasScheme,
    $withHealthPath
): array {
    if ($hasScheme($configuredTarget)) {
        $parts = parse_url($configuredTarget);

        if ($parts === false) {
            return [$configuredTarget];
        }

        return [$buildUrl($withHealthPath($parts))];
    }

    $parts = parse_url('https://' . ltrim($configuredTarget, '/'));

    if ($parts === false) {
        $normalizedTarget = $fallbackTarget($configuredTarget);

        return [
            'https://' . $normalizedTarget,
            'http://' . $normalizedTarget,
        ];
    }

    return $buildSchemeCandidates($withHealthPath($parts));
})();

/**
 * @return array{0: false|string, 1: list<string>}
 */
$fetchUrl = static function (string $url): array {
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
    $headers = [];

    set_error_handler(static fn (): bool => true);

    try {
        $result = file_get_contents($url, false, $context);
        $headers = is_array($http_response_header ?? null)
            ? array_values($http_response_header)
            : [];
    } finally {
        restore_error_handler();
    }

    return [$result, $headers];
};

$extractStatusCode = static function (array $headers): ?int {
    foreach ($headers as $header) {
        if (preg_match('{^HTTP/\S+\s+(\d+)}', $header, $matches) === 1) {
            return (int) $matches[1];
        }
    }

    return null;
};

$isHealthyResponse = static function (string $url) use ($extractStatusCode, $fetchUrl): bool {
    [$result, $headers] = $fetchUrl($url);
    $statusCode = $extractStatusCode($headers);

    if ($statusCode !== null) {
        return $statusCode >= 200 && $statusCode < 400;
    }

    return $result !== false;
};

foreach ($healthcheckCandidates as $candidate) {
    if ($isHealthyResponse($candidate)) {
        exit(0);
    }
}

exit(1);
