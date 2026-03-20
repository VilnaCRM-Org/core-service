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

        $schemas = $this->updateConstraintViolationSchemas($schemas, $changed);

        if (!$changed) {
            return $openApi;
        }

        return $openApi->withComponents($components->withSchemas(new ArrayObject($schemas)));
    }

    /**
     * @param array<string, mixed>|ArrayObject $schemas
     *
     * @return array<string, mixed>
     */
    private function normalizeSchemas(array|ArrayObject $schemas): array
    {
        if ($schemas instanceof ArrayObject) {
            return $schemas->getArrayCopy();
        }

        return $schemas ?? [];
    }

    /**
     * @param array<string, mixed> $schemas
     *
     * @return array<string, mixed>
     */
    private function updateConstraintViolationSchemas(array $schemas, bool &$changed): array
    {
        foreach ($schemas as $key => $schema) {
            if (!str_starts_with($key, self::SCHEMA_KEY_PREFIX)) {
                continue;
            }

            $updated = $this->updateSchema($schema);
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
