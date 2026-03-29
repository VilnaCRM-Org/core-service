<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Applier\SpecExtensionPropertyApplier;
use App\Shared\Application\OpenApi\Cleaner\SpecMetadataCleaner;
use ArrayObject;

final class SpecCleanupProcessor
{
    public function __construct(
        private readonly SpecMetadataCleaner $metadataCleaner,
        private readonly SpecExtensionPropertyApplier $extensionApplier,
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $normalizedOpenApi = $this->metadataCleaner->createNormalizedOpenApi($openApi);

        return $this->applyExtensionProperties(
            $openApi->getExtensionProperties(),
            $normalizedOpenApi
        );
    }

    private function applyExtensionProperties(
        array|ArrayObject|null $extensionProperties,
        OpenApi $openApi
    ): OpenApi {
        return $this->extensionApplier->apply($extensionProperties, $openApi);
    }
}
