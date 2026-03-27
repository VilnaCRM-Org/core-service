<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Applier\OpenApiExtensionsApplier;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\OpenApiFactory;
use App\Shared\Application\OpenApi\Processor\OpenApiProcessorInterface;
use App\Tests\Unit\UnitTestCase;
use ArrayIterator;
use ArrayObject;

final class OpenApiFactoryTest extends UnitTestCase
{
    public function testOpenApiProcessorInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(OpenApiProcessorInterface::class));
    }

    public function testConstructor(): void
    {
        $decoratedFactory = $this->createMock(OpenApiFactoryInterface::class);
        $endpointFactory = $this->createMock(EndpointFactoryInterface::class);
        $processor = $this->createMock(OpenApiProcessorInterface::class);

        $factory = new OpenApiFactory(
            $decoratedFactory,
            [$endpointFactory],
            new ArrayIterator([$processor]),
            $this->createMock(OpenApiExtensionsApplier::class)
        );

        $this->assertInstanceOf(OpenApiFactory::class, $factory);
    }

    public function testInvoke(): void
    {
        $context = ['key' => 'value'];
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths()
        );

        $decoratedFactory = $this->createMock(OpenApiFactoryInterface::class);
        $decoratedFactory->expects($this->once())
            ->method('__invoke')
            ->with($context)
            ->willReturn($openApi);

        $endpointFactories = $this->createEndpointFactories($openApi);

        $payloadOutput = new OpenApi(
            new Info('Payload', '1.0.0'),
            [],
            new Paths()
        );
        $parameterOutput = new OpenApi(
            new Info('Parameters', '1.0.0'),
            [],
            new Paths()
        );
        $tagOutput = new OpenApi(
            new Info('Tags', '1.0.0'),
            [],
            new Paths()
        );
        $iriOutput = new OpenApi(
            new Info('Iri', '1.0.0'),
            [],
            new Paths()
        );
        $pathOutput = new OpenApi(
            new Info('Paths', '1.0.0'),
            [],
            new Paths()
        );
        $schemaFixesOutput = new OpenApi(
            new Info('Schema fixes', '1.0.0'),
            [],
            new Paths()
        );
        $components = new Components(
            new ArrayObject([
                'CustomerUlid' => ['type' => 'string'],
            ])
        );
        $ulidOutput = (new OpenApi(
            new Info('Ulid', '1.0.0'),
            [],
            new Paths(),
            $components
        ))->withExtensionProperty('x-stage', 'ulid');
        $finalOpenApi = new OpenApi(
            new Info('Final', '1.0.0'),
            [],
            new Paths()
        );
        $parameterProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $parameterProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($openApi))
            ->willReturn($parameterOutput);
        $tagProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $tagProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($parameterOutput))
            ->willReturn($tagOutput);
        $iriProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $iriProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($tagOutput))
            ->willReturn($iriOutput);
        $pathProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $pathProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($iriOutput))
            ->willReturn($pathOutput);
        $payloadProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $payloadProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($pathOutput))
            ->willReturn($payloadOutput);
        $schemaFixesProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $schemaFixesProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($payloadOutput))
            ->willReturn($schemaFixesOutput);
        $ulidFixer = $this->createMock(OpenApiProcessorInterface::class);
        $ulidFixer->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($schemaFixesOutput))
            ->willReturn($ulidOutput);
        $extensionsApplier = $this->createMock(OpenApiExtensionsApplier::class);
        $extensionsApplier->expects($this->once())
            ->method('apply')
            ->with(
                $this->callback(static fn (OpenApi $normalizedOpenApi): bool => $normalizedOpenApi !== $ulidOutput
                    && $normalizedOpenApi->getInfo() === $ulidOutput->getInfo()
                    && $normalizedOpenApi->getPaths() === $ulidOutput->getPaths()
                    && $normalizedOpenApi->getComponents() === $ulidOutput->getComponents()),
                $this->identicalTo(['x-stage' => 'ulid'])
            )
            ->willReturn($finalOpenApi);

        $factory = new OpenApiFactory(
            $decoratedFactory,
            $endpointFactories,
            [
                $parameterProcessor,
                $tagProcessor,
                $iriProcessor,
                $pathProcessor,
                $payloadProcessor,
                $schemaFixesProcessor,
                $ulidFixer,
            ],
            $extensionsApplier
        );

        $result = $factory->__invoke($context);

        $this->assertSame($finalOpenApi, $result);
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
            ->with($this->identicalTo($openApi));

        $pathProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $pathProcessor->expects($this->once())
            ->method('process')
            ->willReturnArgument(0);

        $paramProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $paramProcessor->expects($this->once())
            ->method('process')
            ->willReturnArgument(0);

        $iriProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $iriProcessor->expects($this->once())
            ->method('process')
            ->willReturnArgument(0);

        $tagProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $tagProcessor->expects($this->once())
            ->method('process')
            ->willReturnArgument(0);

        $payloadProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $payloadProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($openApi))
            ->willReturn($openApi);

        $schemaFixesProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $schemaFixesProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($openApi))
            ->willReturn($openApi);

        $ulidFixer = $this->createMock(OpenApiProcessorInterface::class);
        $ulidFixer->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($openApi))
            ->willReturn($openApi);

        $extensionsApplier = new OpenApiExtensionsApplier();

        $factory = new OpenApiFactory(
            $decoratedFactory,
            new ArrayIterator([$endpointFactory]),
            [
                $paramProcessor,
                $tagProcessor,
                $iriProcessor,
                $pathProcessor,
                $payloadProcessor,
                $schemaFixesProcessor,
                $ulidFixer,
            ],
            $extensionsApplier
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

        $pathProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $pathProcessor->expects($this->once())
            ->method('process')
            ->willReturnArgument(0);

        $paramProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $paramProcessor->expects($this->once())
            ->method('process')
            ->willReturnArgument(0);

        $iriProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $iriProcessor->expects($this->once())
            ->method('process')
            ->willReturnArgument(0);

        $tagProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $tagProcessor->expects($this->once())
            ->method('process')
            ->willReturnArgument(0);

        $payloadProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $payloadProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($openApi))
            ->willReturn($openApi);

        $schemaFixesProcessor = $this->createMock(OpenApiProcessorInterface::class);
        $schemaFixesProcessor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($openApi))
            ->willReturn($openApi);

        $ulidFixer = $this->createMock(OpenApiProcessorInterface::class);
        $ulidFixer->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($openApi))
            ->willReturn($openApi);

        $extensionsApplier = new OpenApiExtensionsApplier();

        $factory = new OpenApiFactory(
            $decoratedFactory,
            [],
            [
                $paramProcessor,
                $tagProcessor,
                $iriProcessor,
                $pathProcessor,
                $payloadProcessor,
                $schemaFixesProcessor,
                $ulidFixer,
            ],
            $extensionsApplier
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
