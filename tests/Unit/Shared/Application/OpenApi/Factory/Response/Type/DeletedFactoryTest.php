<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response\Type;

use ApiPlatform\OpenApi\Model\Response;
use App\Core\Customer\Application\OpenApi\Response\Type\DeletedFactory;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Tests\Unit\UnitTestCase;

final class DeletedFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilderMock = $this->createMock(ResponseBuilder::class);

        $responseBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                'CustomerType resource deleted',
            )
            ->willReturn($this->createStub(Response::class));

        $factory = new DeletedFactory($responseBuilderMock);
        $response = $factory->getResponse();

        $this->assertInstanceOf(Response::class, $response);
    }
}
