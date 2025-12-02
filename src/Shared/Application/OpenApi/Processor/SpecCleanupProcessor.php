<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class SpecCleanupProcessor
{
    private readonly SpecMetadataCleaner $metadataCleaner;
    private readonly SpecExtensionPropertyApplier $extensionApplier;

    public function __construct(
        ?SpecMetadataCleaner $metadataCleaner = null,
        ?SpecExtensionPropertyApplier $extensionApplier = null,
    ) {
        $this->metadataCleaner = $metadataCleaner ?? new SpecMetadataCleaner();
        $this->extensionApplier = $extensionApplier ?? new SpecExtensionPropertyApplier();
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $normalizedOpenApi = $this->createNormalizedOpenApi($openApi);

        return $this->applyExtensionProperties(
            $openApi->getExtensionProperties(),
            $normalizedOpenApi
        );
    }

    private function createNormalizedOpenApi(OpenApi $openApi): OpenApi
    {
        return $this->metadataCleaner->createNormalizedOpenApi($openApi);
    }

    private function applyExtensionProperties(
        array|ArrayObject|null $extensionProperties,
        OpenApi $openApi
    ): OpenApi {
        return $this->extensionApplier->apply($extensionProperties, $openApi);
    }

    private function cleanComponents(?Components $components): ?Components
    {
        return $this->metadataCleaner->cleanComponents($components);
    }
}
