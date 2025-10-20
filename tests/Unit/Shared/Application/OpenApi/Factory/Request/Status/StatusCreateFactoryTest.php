<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Status;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\CustomerStatus\CrCStReq;
use App\Tests\Unit\UnitTestCase;

final class StatusCreateFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilderMock = $this->createMock(RequestBuilder::class);

        $requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->isType('array'))
            ->willReturn($this->createMock(RequestBody::class));

        $statusCreateFactory = new CrCStReq($requestBuilderMock);

        $requestBody = $statusCreateFactory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
