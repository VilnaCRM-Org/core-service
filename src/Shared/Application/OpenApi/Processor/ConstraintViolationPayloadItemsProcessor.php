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
        $schemas = $this->normalizeSchemas($components->getSchemas());

        if ($schemas === []) {
            return $openApi;
        }

        $changed = false;
        $schemas = $this->updateConstraintViolationSchemas($schemas, $changed);

        if (! $changed) {
            return $openApi;
        }

        return $openApi->withComponents($components->withSchemas(new ArrayObject($schemas)));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSchemas(?ArrayObject $schemas): array
    {
        if ($schemas === null) {
            return [];
        }

        return $schemas->getArrayCopy();
    }

    /**
     * @param array<string, mixed> $schemas
     *
     * @return array<string, mixed>
     */
    private function updateConstraintViolationSchemas(array $schemas, bool &$changed): array
    {
        foreach ($schemas as $key => $schema) {
            if (! str_starts_with($key, self::SCHEMA_KEY_PREFIX)) {
                continue;
            }

            $schemaArray = null;
            if ($schema instanceof ArrayObject) {
                $schemaArray = $schema->getArrayCopy();
            }

            if ($schemaArray === null && ! is_array($schema)) {
                continue;
            }

            $schemaArray ??= $schema;
            $updated = $this->updateSchema($schemaArray);
            if ($updated === null) {
                continue;
            }

            $schemas[$key] = new ArrayObject($updated);
            $changed = true;
        }

        return $schemas;
    }

    /**
     * @param array<string, mixed>|null $schema
     *
     * @return array<string, mixed>|null
     */
    private function updateSchema(?array $schema): ?array
    {
        $normalized = SchemaNormalizer::normalize($schema);

        return ConstraintViolationPayloadItemsUpdater::update($normalized);
    }
}
