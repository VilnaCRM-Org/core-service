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
            && $this->isApiPath($request->getPathInfo());
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
}
