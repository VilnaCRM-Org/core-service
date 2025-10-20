<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Type;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\UpdateCustomerTypeRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class TypeUpdateFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilderMock = $this->createMock(RequestPatchBuilder::class);

        $requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->isType('array'))
            ->willReturn($this->createMock(RequestBody::class));

        $typeUpdateFactory = new UpdateCustomerTypeRequestFactory($requestBuilderMock);
        $requestBody = $typeUpdateFactory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
