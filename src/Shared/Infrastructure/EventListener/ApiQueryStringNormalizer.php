<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;

final class ApiQueryStringNormalizer
{
    /**
     * @param array<array-key, array|scalar|null> $parameters
     */
    public function normalize(Request $request, mixed $parameters): void
    {
        if (! is_array($parameters)) {
            return;
        }

        $queryString = http_build_query($parameters, '', '&', \PHP_QUERY_RFC3986);

        $request->query->replace($parameters);
        $request->server->set('QUERY_STRING', $queryString);
        $request->server->set(
            'REQUEST_URI',
            $this->requestUriWithQueryString($request, $queryString)
        );
        $this->resetUriCache($request);
    }

    private function requestUriWithQueryString(Request $request, string $queryString): string
    {
        $path = $this->requestPath($request);

        if ($queryString === '') {
            return $path;
        }

        return $path . '?' . $queryString;
    }

    private function requestPath(Request $request): string
    {
        $requestUri = (string) $request->server->get('REQUEST_URI', '');

        if (! str_starts_with($requestUri, '/')) {
            return $this->absoluteRequestPath($request, $requestUri);
        }

        $path = $this->relativeRequestPath($requestUri);

        return $path === '' ? '/' : $path;
    }

    private function relativeRequestPath(string $requestUri): string
    {
        $queryOffset = strpos($requestUri, '?');

        return $queryOffset === false
            ? $requestUri
            : substr($requestUri, 0, $queryOffset);
    }

    private function absoluteRequestPath(Request $request, string $requestUri): string
    {
        $path = parse_url($requestUri, \PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            return $path;
        }

        return $request->getPathInfo();
    }

    private function resetUriCache(Request $request): void
    {
        (function (): void {
            $this->requestUri = null;
            $this->pathInfo = null;
        })->call($request);
    }
}
