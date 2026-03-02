<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Customer;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\Customer\CreateCustomerRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class CreateFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilderMock = $this->createMock(RequestBuilder::class);

        $requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->isType('array'))
            ->willReturn($this->createMock(RequestBody::class));

        $createFactory = new CreateCustomerRequestFactory($requestBuilderMock);

        $requestBody = $createFactory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
