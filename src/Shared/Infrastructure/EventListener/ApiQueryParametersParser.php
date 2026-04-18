<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;

final class ApiQueryParametersParser
{
    /**
     * @return array<array-key, array|scalar|null>
     */
    public function parse(Request $request): array
    {
        return HeaderUtils::parseQuery($request->server->get('QUERY_STRING', ''));
    }
}
