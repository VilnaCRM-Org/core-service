<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 512)]
final class ApiQueryParametersListener
{
    private const SAFE_QUERY_KEY_PATTERN = '/^[A-Za-z0-9_.:-]+$/';

    public function __invoke(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (! $this->shouldHandle($request)) {
            return;
        }

        $sanitizedParameters = $this->sanitizeQueryParameters($request->query->all());

        if (! $request->attributes->has('_api_query_parameters')) {
            $request->attributes->set('_api_query_parameters', $sanitizedParameters);
        }

        if (! $request->attributes->has('_api_filters')) {
            $request->attributes->set('_api_filters', $sanitizedParameters);
        }
    }

    private function shouldHandle(Request $request): bool
    {
        if ($request->query->count() === 0) {
            return false;
        }

        if ($request->attributes->has('_api_query_parameters') && $request->attributes->has('_api_filters')) {
            return false;
        }

        return $this->isApiPath($request->getPathInfo());
    }

    private function isApiPath(string $path): bool
    {
        return $path === '/api' || str_starts_with($path, '/api/');
    }

    /**
     * @param array<array-key, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function sanitizeQueryParameters(array $parameters, bool $allowIntegerKeys = false): array
    {
        $sanitized = [];

        foreach ($parameters as $key => $value) {
            if (is_int($key)) {
                if (! $allowIntegerKeys) {
                    continue;
                }

                $sanitized[$key] = is_array($value)
                    ? $this->sanitizeQueryParameters($value, true)
                    : $value;

                continue;
            }

            if (! $this->isSafeKey($key)) {
                continue;
            }

            $sanitized[$key] = is_array($value)
                ? $this->sanitizeQueryParameters($value, true)
                : $value;
        }

        return $sanitized;
    }

    private function isSafeKey(string $key): bool
    {
        if ($key === '') {
            return false;
        }

        return preg_match(self::SAFE_QUERY_KEY_PATTERN, $key) === 1;
    }
}
