<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Factory;

use App\Shared\Infrastructure\Factory\ApiProblemJsonResponseFactory;
use App\Tests\Unit\UnitTestCase;

final class ApiProblemJsonResponseFactoryTest extends UnitTestCase
{
    public function testCreateNotFoundResponseBuildsProblemJsonPayload(): void
    {
        $factory = new ApiProblemJsonResponseFactory();

        $response = $factory->createNotFoundResponse();

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json; charset=utf-8', $response->headers->get('Content-Type'));
        self::assertSame(
            [
                'title' => 'An error occurred',
                'detail' => 'Not Found',
                'status' => 404,
                'type' => '/errors/404',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }
}
