<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class ConstraintViolationPayloadItemsProcessor
{
    private const SCHEMA_KEY_PREFIX = 'ConstraintViolation';

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();

        if ($schemas instanceof ArrayObject) {
            $schemas = $schemas->getArrayCopy();
        }

        $schemas ??= [];

        $changed = false;
        foreach ($schemas as $key => $schema) {
            if (!str_starts_with($key, self::SCHEMA_KEY_PREFIX)) {
                continue;
            }

            $normalized = SchemaNormalizer::normalize($schema);
            $updated = ConstraintViolationPayloadItemsUpdater::update($normalized);
            if ($updated === null) {
                continue;
            }

            $schemas[$key] = new ArrayObject($updated);
            $changed = true;
        }

        if (!$changed) {
            return $openApi;
        }

        return $openApi->withComponents($components->withSchemas(new ArrayObject($schemas)));
    }
}
