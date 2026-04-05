<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class ConstraintViolationPayloadItemsProcessor implements OpenApiProcessorInterface
{
    public function __construct(
        private ConstraintViolationSchemaUpdater $schemaUpdater
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $updatedSchemas = $this->updatedSchemas($components->getSchemas());

        return match ($updatedSchemas) {
            null => $openApi,
            default => $this->withUpdatedSchemas($openApi, $components, $updatedSchemas),
        };
    }

    private function updatedSchemas(?ArrayObject $schemas): ?ArrayObject
    {
        return match ($schemas) {
            null => null,
            default => $this->schemaUpdater->update($schemas),
        };
    }

    private function withUpdatedSchemas(
        OpenApi $openApi,
        Components $components,
        ArrayObject $updatedSchemas
    ): OpenApi {
        return $openApi->withComponents($components->withSchemas($updatedSchemas));
    }
}
