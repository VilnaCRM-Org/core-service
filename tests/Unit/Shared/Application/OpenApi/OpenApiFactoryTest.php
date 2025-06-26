<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\OpenApiFactory;
use App\Tests\Unit\UnitTestCase;

final class OpenApiFactoryTest extends UnitTestCase
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

        $endpointFactories = $this->createEndpointFactories($openApi);

        $factory = new OpenApiFactory(
            $decoratedFactory,
            $endpointFactories
        );

        $result = $factory->__invoke($context);

        $this->assertSame($openApi, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&EndpointFactoryInterface[]
     *
     * @psalm-return list{\PHPUnit\Framework\MockObject\MockObject&EndpointFactoryInterface, \PHPUnit\Framework\MockObject\MockObject&EndpointFactoryInterface}
     */
    private function createEndpointFactories(OpenApi $openApi): array
    {
        $endpointFactory1 = $this->createMock(EndpointFactoryInterface::class);
        $endpointFactory1->expects($this->once())
            ->method('createEndpoint')
            ->with($openApi);

        $endpointFactory2 = $this->createMock(EndpointFactoryInterface::class);
        $endpointFactory2->expects($this->once())
            ->method('createEndpoint')
            ->with($openApi);

        return [$endpointFactory1, $endpointFactory2];
    }
}
