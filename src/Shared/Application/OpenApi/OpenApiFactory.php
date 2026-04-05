<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Applier\OpenApiExtensionsApplier;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\Processor\OpenApiProcessorInterface;
use ArrayObject;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    /** @var list<EndpointFactoryInterface> */
    private array $endpointFactories;

    /** @var list<OpenApiProcessorInterface> */
    private array $processors;

    /**
     * @param iterable<EndpointFactoryInterface> $endpointFactories
     * @param iterable<OpenApiProcessorInterface> $processors
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        iterable $endpointFactories,
        iterable $processors,
        private OpenApiExtensionsApplier $extensionsApplier
    ) {
        $this->endpointFactories = is_array($endpointFactories)
            ? $endpointFactories
            : iterator_to_array($endpointFactories);
        $this->processors = is_array($processors)
            ? $processors
            : iterator_to_array($processors);
    }

    /**
     * @param array<string, string> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $this->applyEndpointFactories($openApi);
        $openApi = $this->applyProcessors($openApi);

        return $this->normalizeOpenApi($openApi);
    }

    private function applyEndpointFactories(OpenApi $openApi): void
    {
        array_walk(
            $this->endpointFactories,
            static function (
                EndpointFactoryInterface $factory
            ) use ($openApi): void {
                $factory->createEndpoint($openApi);
            }
        );
    }

    private function applyProcessors(OpenApi $openApi): OpenApi
    {
        foreach ($this->processors as $processor) {
            $openApi = $processor->process($openApi);
        }

        return $openApi;
    }

    private function normalizeOpenApi(OpenApi $openApi): OpenApi
    {
        return $this->extensionsApplier->apply(
            new OpenApi(
                $openApi->getInfo(),
                $openApi->getServers(),
                $openApi->getPaths(),
                $openApi->getComponents(),
                $openApi->getSecurity(),
                $openApi->getTags(),
                $openApi->getExternalDocs(),
                $openApi->getJsonSchemaDialect(),
                $this->normalizeWebhooks($openApi->getWebhooks())
            ),
            $openApi->getExtensionProperties()
        );
    }

    private function normalizeWebhooks(?ArrayObject $webhooks): ArrayObject
    {
        return $webhooks ?? new ArrayObject();
    }
}
