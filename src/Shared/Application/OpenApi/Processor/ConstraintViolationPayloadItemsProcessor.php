<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;

final class ConstraintViolationPayloadItemsProcessor implements OpenApiProcessorInterface
{
    public function __construct(
        private ConstraintViolationSchemaUpdater $schemaUpdater
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        return match (true) {
            ($components = $openApi->getComponents()) === null => $openApi,
            ($schemas = $components->getSchemas()) === null => $openApi,
            ($updatedSchemas = $this->schemaUpdater->update($schemas)) === null => $openApi,
            default => $openApi->withComponents($components->withSchemas($updatedSchemas)),
        };
    }
}
