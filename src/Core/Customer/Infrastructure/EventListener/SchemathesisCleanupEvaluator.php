<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SchemathesisCleanupEvaluator
{
    public const HEADER_NAME = 'X-Schemathesis-Test';
    public const HEADER_VALUE = 'cleanup-customers';
    private const HANDLED_PATH = '/api/customers';

    public function shouldCleanup(Request $request, Response $response): bool
    {
        return $this->hasCleanupHeader($request)
            && $request->isMethod(Request::METHOD_POST)
            && $request->getPathInfo() === self::HANDLED_PATH
            && $response->isSuccessful();
    }

    private function hasCleanupHeader(Request $request): bool
    {
        return $request->headers->get(self::HEADER_NAME) === self::HEADER_VALUE;
    }
}
