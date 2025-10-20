<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Status;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;
use App\Shared\Application\OpenApi\Factory\Request\CustomerStatus\UpdateCustomerStatusRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class StatusUpdateFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilderMock = $this->createMock(RequestPatchBuilder::class);

        $requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->isType('array'))
            ->willReturn($this->createMock(RequestBody::class));

        $statusUpdateFactory = new UpdateCustomerStatusRequestFactory($requestBuilderMock);

        $requestBody = $statusUpdateFactory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
