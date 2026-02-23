<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class ConstraintViolationPayloadItemsProcessor
{
    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();
        if ($schemas === null) {
            return $openApi;
        }

        $schema = $schemas['ConstraintViolation'] ?? null;
        $normalized = SchemaNormalizer::normalize($schema);
        $updated = ConstraintViolationPayloadItemsUpdater::update($normalized);
        if ($updated === null) {
            return $openApi;
        }

        $schemas['ConstraintViolation'] = $schema instanceof ArrayObject
            ? new ArrayObject($updated)
            : $updated;

        return $openApi->withComponents($components->withSchemas($schemas));
    }
}
