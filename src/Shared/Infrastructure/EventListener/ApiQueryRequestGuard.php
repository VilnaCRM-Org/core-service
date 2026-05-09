<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;

final class ApiQueryRequestGuard
{
    public function allows(Request $request): bool
    {
        return $this->hasQueryString($request)
            && $this->hasMissingApiAttributes($request)
            && $this->isApiPath($this->requestPath($request));
    }

    private function hasQueryString(Request $request): bool
    {
        return $request->server->get('QUERY_STRING', '') !== '';
    }

    private function hasMissingApiAttributes(Request $request): bool
    {
        return ! ($request->attributes->has('_api_query_parameters')
            && $request->attributes->has('_api_filters'));
    }

    private function isApiPath(string $path): bool
    {
        return $path === '/api' || str_starts_with($path, '/api/');
    }

    private function requestPath(Request $request): string
    {
        $requestUri = (string) $request->server->get('REQUEST_URI', '');

        if ($requestUri === '') {
            return $request->getPathInfo();
        }

        if (! str_starts_with($requestUri, '/')) {
            $path = parse_url($requestUri, \PHP_URL_PATH);

            return is_string($path) ? $path : '';
        }

        return $this->relativeRequestPath($requestUri);
    }

    private function relativeRequestPath(string $requestUri): string
    {
        $queryOffset = strpos($requestUri, '?');
        $path = $queryOffset === false
            ? $requestUri
            : substr($requestUri, 0, $queryOffset);

        return $path === '' ? '/' : $path;
    }
}
