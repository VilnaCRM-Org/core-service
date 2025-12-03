<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Applier\OpenApiExtensionsApplier;
use App\Shared\Application\OpenApi\Augmenter\ParameterDescriptionAugmenter;
use App\Shared\Application\OpenApi\Augmenter\TagDescriptionAugmenter;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\Fixer\ContentPropertyFixer;
use App\Shared\Application\OpenApi\Fixer\IriReferenceTypeFixer;
use App\Shared\Application\OpenApi\Fixer\MediaTypePropertyFixer;
use App\Shared\Application\OpenApi\Fixer\PropertyTypeFixer;
use App\Shared\Application\OpenApi\Sanitizer\PathParametersSanitizer;
use ArrayObject;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    private IriReferenceTypeFixer $iriReferenceTypeFixer;
    /** @var list<EndpointFactoryInterface> */
    private array $endpointFactories;

    /**
     * @param iterable<EndpointFactoryInterface> $endpointFactories
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        iterable $endpointFactories,
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
        $this->endpointFactories = is_array($endpointFactories)
            ? $endpointFactories
            : iterator_to_array($endpointFactories);
        $this->iriReferenceTypeFixer = $iriReferenceTypeFixer
            ?? $this->createDefaultIriReferenceTypeFixer();
    }

    /**
     * @param array<string, string> $context
     */
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
        $this->parameterDescriptionAugmenter->augment($openApi);
        $openApi = $this->tagDescriptionAugmenter->augment($openApi);
        $this->iriReferenceTypeFixer->fix($openApi);

        return $this->pathParametersSanitizer->sanitize($openApi);
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

    private function createDefaultIriReferenceTypeFixer(): IriReferenceTypeFixer
    {
        return new IriReferenceTypeFixer(
            new ContentPropertyFixer(
                new MediaTypePropertyFixer(new PropertyTypeFixer())
            )
        );
    }
}
