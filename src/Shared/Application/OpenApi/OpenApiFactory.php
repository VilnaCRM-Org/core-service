<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Augmenter\ParameterDescriptionAugmenter;
use App\Shared\Application\OpenApi\Augmenter\TagDescriptionAugmenter;
use App\Shared\Application\OpenApi\Extension\OpenApiExtensionsApplier;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\Fixer\ContentPropertyFixer;
use App\Shared\Application\OpenApi\Fixer\IriReferenceTypeFixer;
use App\Shared\Application\OpenApi\Fixer\MediaTypePropertyFixer;
use App\Shared\Application\OpenApi\Fixer\PropertyTypeFixer;
use App\Shared\Application\OpenApi\Sanitizer\PathParametersSanitizer;
use ArrayObject;
use Traversable;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    private IriReferenceTypeFixer $iriReferenceTypeFixer;

    /**
     * @param iterable<EndpointFactoryInterface> $endpointFactories
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private iterable $endpointFactories,
        private PathParametersSanitizer $pathParametersSanitizer
            = new PathParametersSanitizer(),
        private ParameterDescriptionAugmenter $parameterDescriptionAugmenter
            = new ParameterDescriptionAugmenter(),
        ?IriReferenceTypeFixer $iriReferenceTypeFixer = null,
        private TagDescriptionAugmenter $tagDescriptionAugmenter
            = new TagDescriptionAugmenter(),
        private OpenApiExtensionsApplier $extensionsApplier
            = new OpenApiExtensionsApplier()
    ) {
        $this->iriReferenceTypeFixer = $iriReferenceTypeFixer
            ?? $this->createDefaultIriReferenceTypeFixer();
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

        $this->parameterDescriptionAugmenter->augment($openApi);
        $openApi = $this->tagDescriptionAugmenter->augment($openApi);
        $this->iriReferenceTypeFixer->fix($openApi);
        $openApi = $this->pathParametersSanitizer->sanitize($openApi);

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

    private function createDefaultIriReferenceTypeFixer(): IriReferenceTypeFixer
    {
        return new IriReferenceTypeFixer(
            new ContentPropertyFixer(
                new MediaTypePropertyFixer(new PropertyTypeFixer())
            )
        );
    }
}
