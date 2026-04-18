<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\EventListener\ApiQueryParametersParser;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiQueryParametersParserTest extends UnitTestCase
{
    public function testParseUsesRawQueryString(): void
    {
        $parser = new ApiQueryParametersParser();
        $request = Request::create(
            '/api/customers?status.value=Active&order%5Bstatus.value%5D=asc',
            Request::METHOD_GET
        );

        self::assertSame(
            [
                'status.value' => 'Active',
                'order' => ['status.value' => 'asc'],
            ],
            $parser->parse($request)
        );
    }
}
