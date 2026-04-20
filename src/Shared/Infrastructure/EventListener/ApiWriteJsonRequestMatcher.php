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

        return ($path === '/api' || str_starts_with($path, '/api/'))
            && \in_array($request->getMethod(), $writeMethods, true)
            && str_contains((string) $request->headers->get('Content-Type', ''), 'json');
    }
}
