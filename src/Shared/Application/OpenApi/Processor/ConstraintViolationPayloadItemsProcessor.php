<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class ConstraintViolationPayloadItemsProcessor
{
    private const SCHEMA_KEY = "ConstraintViolation";

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();
        if ($schemas === null) {
            return $openApi;
        }

        $schema = $schemas[self::SCHEMA_KEY] ?? null;
        if ($schema === null) {
            return $openApi;
        }

        $normalized = SchemaNormalizer::normalize($schema);
        $updated = ConstraintViolationPayloadItemsUpdater::update($normalized);
        if ($updated === null) {
            return $openApi;
        }

        $schemas[self::SCHEMA_KEY] = $schema instanceof ArrayObject
            ? new ArrayObject($updated)
            : $updated;

        return $openApi->withComponents($components->withSchemas($schemas));
    }
}
