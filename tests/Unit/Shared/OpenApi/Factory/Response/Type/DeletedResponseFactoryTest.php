<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Response\Type;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\CustomerType\CustomerTypeDeletedResponseFactory;
use App\Tests\Unit\UnitTestCase;

final class DeletedResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $response = $this->createMock(Response::class);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'CustomerType resource deleted',
                [],
                []
            )
            ->willReturn($response);

        $factory = new CustomerTypeDeletedResponseFactory($responseBuilder);

        $this->assertSame($response, $factory->getResponse());
    }
}
