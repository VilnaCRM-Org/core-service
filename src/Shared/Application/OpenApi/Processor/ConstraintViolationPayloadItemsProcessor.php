<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class ConstraintViolationPayloadItemsProcessor
{
    private const SCHEMA_KEY = 'ConstraintViolation';

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();
        if ($schemas === null) {
            return $openApi;
        }

        if (!array_key_exists(self::SCHEMA_KEY, $schemas)) {
            return $openApi;
        }

        $schema = $schemas[self::SCHEMA_KEY];
        $normalized = SchemaNormalizer::normalize($schema);
        $updated = ConstraintViolationPayloadItemsUpdater::update($normalized);

        $schemas[self::SCHEMA_KEY] = $schema instanceof ArrayObject
            ? new ArrayObject($updated)
            : $updated;

        return $openApi->withComponents($components->withSchemas($schemas));
    }
}
