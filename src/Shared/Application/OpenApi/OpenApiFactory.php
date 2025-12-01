<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Extension\OpenApiExtensionsApplier;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\Processor\IriReferenceTypeProcessor;
use App\Shared\Application\OpenApi\Processor\ParameterDescriptionProcessor;
use App\Shared\Application\OpenApi\Processor\PathParametersProcessor;
use App\Shared\Application\OpenApi\Processor\TagDescriptionProcessor;
use ArrayObject;
use Traversable;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    /**
     * @param iterable<EndpointFactoryInterface> $endpointFactories
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private iterable $endpointFactories,
        private PathParametersProcessor $pathParametersProcessor
            = new PathParametersProcessor(),
        private ParameterDescriptionProcessor $parameterDescriptionProcessor
            = new ParameterDescriptionProcessor(),
        private IriReferenceTypeProcessor $iriReferenceTypeProcessor
            = new IriReferenceTypeProcessor(),
        private TagDescriptionProcessor $tagDescriptionProcessor
            = new TagDescriptionProcessor(),
        private OpenApiExtensionsApplier $extensionsApplier
            = new OpenApiExtensionsApplier()
    ) {
    }

    /**
     * @param array<string, string> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $factories = $this->endpointFactories instanceof Traversable
            ? iterator_to_array($this->endpointFactories)
            : $this->endpointFactories;

        array_walk(
            $factories,
            static function (EndpointFactoryInterface $endpointFactory) use ($openApi): void {
                $endpointFactory->createEndpoint($openApi);
            }
        );

        $openApi = $this->parameterDescriptionProcessor->process($openApi);
        $openApi = $this->tagDescriptionProcessor->process($openApi);
        $openApi = $this->iriReferenceTypeProcessor->process($openApi);
        $openApi = $this->pathParametersProcessor->process($openApi);

        return $this->normalizeOpenApi($openApi);
    }

    private function normalizeOpenApi(OpenApi $openApi): OpenApi
    {
        $webhooks = $openApi->getWebhooks();
        $normalizedOpenApi = new OpenApi(
            $openApi->getInfo(),
            $openApi->getServers(),
            $openApi->getPaths(),
            $openApi->getComponents(),
            $openApi->getSecurity(),
            $openApi->getTags(),
            $openApi->getExternalDocs(),
            $openApi->getJsonSchemaDialect(),
            $webhooks instanceof ArrayObject && $webhooks->count() > 0
                ? $webhooks
                : new ArrayObject()
        );

        return $this->extensionsApplier->apply(
            $normalizedOpenApi,
            $openApi->getExtensionProperties()
        );
    }
}
