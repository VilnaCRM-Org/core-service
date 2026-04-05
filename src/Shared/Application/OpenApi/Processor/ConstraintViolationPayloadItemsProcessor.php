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
        $components = $openApi->getComponents();
        if ($components === null) {
            return $openApi;
        }

        $schemas = $components->getSchemas();
        if ($schemas === null) {
            return $openApi;
        }

        $updatedSchemas = $this->schemaUpdater->update($schemas);
        if ($updatedSchemas === null) {
            return $openApi;
        }

        return $openApi->withComponents($components->withSchemas($updatedSchemas));
    }
}
