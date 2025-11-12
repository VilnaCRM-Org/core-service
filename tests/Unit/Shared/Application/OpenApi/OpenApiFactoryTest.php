<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\OpenApiFactory;
use App\Tests\Unit\UnitTestCase;
use ArrayIterator;
use ArrayObject;

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

        $this->assertInstanceOf(OpenApi::class, $result);
    }

    public function testInvokeNormalizesWebhooksAndExtensions(): void
    {
        $openApi = (new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            null,
            [],
            [],
            null,
            null,
            new ArrayObject()
        ))->withExtensionProperty('x-sample', ['foo' => 'bar']);

        $decoratedFactory = $this->createMock(OpenApiFactoryInterface::class);
        $decoratedFactory->expects($this->once())
            ->method('__invoke')
            ->with([])
            ->willReturn($openApi);

        $endpointFactory = $this->createMock(EndpointFactoryInterface::class);
        $endpointFactory->expects($this->once())
            ->method('createEndpoint')
            ->with($this->isInstanceOf(OpenApi::class));

        $factory = new OpenApiFactory(
            $decoratedFactory,
            new ArrayIterator([$endpointFactory])
        );

        $result = $factory->__invoke([]);

        $this->assertNotSame($openApi, $result);
        $this->assertInstanceOf(ArrayObject::class, $result->getWebhooks());
        $this->assertSame(0, $result->getWebhooks()->count());
        $this->assertSame(
            ['x-sample' => ['foo' => 'bar']],
            $result->getExtensionProperties()
        );
    }

    public function testInvokePreservesExistingWebhooks(): void
    {
        $webhooks = new ArrayObject(['sample' => ['post' => []]]);
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            null,
            [],
            [],
            null,
            null,
            $webhooks
        );

        $decoratedFactory = $this->createMock(OpenApiFactoryInterface::class);
        $decoratedFactory->expects($this->once())
            ->method('__invoke')
            ->with([])
            ->willReturn($openApi);

        $factory = new OpenApiFactory(
            $decoratedFactory,
            []
        );

        $result = $factory->__invoke([]);

        $this->assertSame($webhooks, $result->getWebhooks());
    }

    /**
     * @return array<EndpointFactoryInterface>
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
