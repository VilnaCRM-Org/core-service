<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Applier\OpenApiExtensionsApplier;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\Processor\IriReferenceTypeProcessor;
use App\Shared\Application\OpenApi\Processor\ParameterDescriptionProcessor;
use App\Shared\Application\OpenApi\Processor\PathParametersProcessor;
use App\Shared\Application\OpenApi\Processor\TagDescriptionProcessor;
use ArrayObject;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    /** @var list<EndpointFactoryInterface> */
    private array $endpointFactories;

    /**
     * @param iterable<EndpointFactoryInterface> $endpointFactories
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        iterable $endpointFactories,
        private PathParametersProcessor $pathParametersProcessor,
        private ParameterDescriptionProcessor $parameterDescriptionProcessor,
        private IriReferenceTypeProcessor $iriReferenceTypeProcessor,
        private TagDescriptionProcessor $tagDescriptionProcessor,
        private OpenApiExtensionsApplier $extensionsApplier
    ) {
        $this->endpointFactories = is_array($endpointFactories)
            ? $endpointFactories
            : iterator_to_array($endpointFactories);
    }

    /**
     * @param array<string, string> $context
     */
    #[Override]
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $this->applyEndpointFactories($openApi);

        return $this->normalizeOpenApi(
            $this->applyAugmenters($openApi)
        );
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

    private function applyAugmenters(OpenApi $openApi): OpenApi
    {
        $openApi = $this->parameterDescriptionProcessor->process($openApi);
        $openApi = $this->tagDescriptionProcessor->process($openApi);
        $openApi = $this->iriReferenceTypeProcessor->process($openApi);

        return $this->pathParametersProcessor->process($openApi);
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
        return $webhooks instanceof ArrayObject && $webhooks->count() > 0
            ? $webhooks
            : new ArrayObject();
    }
}
