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
            $schemas = [];
        } elseif ($schemas instanceof ArrayObject) {
            $schemas = $schemas->getArrayCopy();
        }

        if (! isset($schemas[self::SCHEMA_KEY])) {
            $schemas[self::SCHEMA_KEY] = null;
        }

        $schema = $schemas[self::SCHEMA_KEY];
        $normalized = SchemaNormalizer::normalize($schema);
        $updated = ConstraintViolationPayloadItemsUpdater::update($normalized);
        if ($updated === null) {
            return $openApi;
        }

        $schemas[self::SCHEMA_KEY] = new ArrayObject($updated);

        return $openApi->withComponents($components->withSchemas(new ArrayObject($schemas)));
    }
}
