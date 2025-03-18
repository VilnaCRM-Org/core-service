<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\OpenApiFactory;
use PHPUnit\Framework\TestCase;

final class OpenApiFactoryTest extends TestCase
{
    public function testConstructor(): void
    {
        $decoratedFactory = $this->createMock(OpenApiFactoryInterface::class);
        $endpointFactory = $this->createMock(EndpointFactoryInterface::class);

        $factory = new OpenApiFactory($decoratedFactory, [$endpointFactory]);

        $this->assertInstanceOf(OpenApiFactory::class, $factory);
    }

    public function testInvoke(): void
    {
        $context = ['key' => 'value'];
        $openApi = $this->createMock(OpenApi::class);

        $decoratedFactory = $this->createMock(OpenApiFactoryInterface::class);
        $decoratedFactory->expects($this->once())
            ->method('__invoke')
            ->with($context)
            ->willReturn($openApi);

        $endpointFactory1 = $this->createMock(EndpointFactoryInterface::class);
        $endpointFactory1->expects($this->once())
            ->method('createEndpoint')
            ->with($openApi);

        $endpointFactory2 = $this->createMock(EndpointFactoryInterface::class);
        $endpointFactory2->expects($this->once())
            ->method('createEndpoint')
            ->with($openApi);

        $factory = new OpenApiFactory($decoratedFactory, [$endpointFactory1, $endpointFactory2]);

        $result = $factory->__invoke($context);

        $this->assertSame($openApi, $result);
    }
}
