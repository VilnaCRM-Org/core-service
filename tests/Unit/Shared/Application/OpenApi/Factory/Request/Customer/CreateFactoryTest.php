<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Customer;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\Customer\CreateFactory;
use PHPUnit\Framework\TestCase;

final class CreateFactoryTest extends TestCase
{
    public function testGetRequest(): void
    {
        $requestBuilderMock = $this->createMock(RequestBuilder::class);

        $requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->isType('array'))
            ->willReturn($this->createMock(RequestBody::class));

        $createFactory = new CreateFactory($requestBuilderMock);

        $requestBody = $createFactory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
