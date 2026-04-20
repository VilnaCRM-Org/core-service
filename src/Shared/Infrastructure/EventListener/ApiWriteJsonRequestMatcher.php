<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;

final class ApiWriteJsonRequestMatcher
{
    public function matches(Request $request): bool
    {
        $path = $request->getPathInfo();
        $writeMethods = [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH];
        $contentType = strtolower((string) $request->headers->get('Content-Type', ''));

        return ($path === '/api' || str_starts_with($path, '/api/'))
            && \in_array($request->getMethod(), $writeMethods, true)
            && str_contains($contentType, 'json');
    }
}
