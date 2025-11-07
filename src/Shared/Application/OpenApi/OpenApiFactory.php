<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\EndpointFactoryInterface;
use App\Shared\Application\OpenApi\Processor\IriReferenceTypeFixer;
use App\Shared\Application\OpenApi\Processor\OperationDescriptionAugmenter;
use App\Shared\Application\OpenApi\Processor\ParameterDescriptionAugmenter;
use App\Shared\Application\OpenApi\Processor\PathParametersSanitizer;
use App\Shared\Application\OpenApi\Processor\TagDescriptionAugmenter;

final class OpenApiFactory implements OpenApiFactoryInterface
{
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
        private OperationDescriptionAugmenter $operationDescriptionAugmenter
            = new OperationDescriptionAugmenter(),
        private IriReferenceTypeFixer $iriReferenceTypeFixer
            = new IriReferenceTypeFixer(),
        private TagDescriptionAugmenter $tagDescriptionAugmenter
            = new TagDescriptionAugmenter()
    ) {
    }

    /**
     * @param array<string, string> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        foreach ($this->endpointFactories as $endpointFactory) {
            $endpointFactory->createEndpoint($openApi);
        }

        $this->parameterDescriptionAugmenter->augment($openApi);
        $this->operationDescriptionAugmenter->augment($openApi);
        $openApi = $this->tagDescriptionAugmenter->augment($openApi);
        $this->iriReferenceTypeFixer->fix($openApi);
        return $this->pathParametersSanitizer->sanitize($openApi);
    }
}
