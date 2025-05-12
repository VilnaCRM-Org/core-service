<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Type;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Core\Customer\Application\OpenApi\Request\Type\TypeCreateFactory;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Tests\Unit\UnitTestCase;

final class TypeCreateFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilderMock = $this->createMock(RequestBuilder::class);

        $requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->isType('array'))
            ->willReturn($this->createMock(RequestBody::class));

        $typeCreateFactory = new TypeCreateFactory($requestBuilderMock);
        $requestBody = $typeCreateFactory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
