<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactory;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class EndpointFactoryTest extends UnitTestCase
{
    public function testMergeResponsesNormalizesArrayObjectKeys(): void
    {
        $base = new ArrayObject([
            '200' => $this->createMock(Response::class),
        ]);
        $override = [
            400 => $this->createMock(Response::class),
        ];

        $factory = new class() extends EndpointFactory {
            public function createEndpoint(OpenApi $openApi): void
            {
            }
        };

        $result = $factory->mergeResponses($base, $override);

        self::assertSame([200, 400], array_keys($result));
    }
}
