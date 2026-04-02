<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class ConstraintViolationPayloadItemsProcessor implements OpenApiProcessorInterface
{
    private const SCHEMA_KEY_PREFIX = 'ConstraintViolation';

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        if ($components !== null) {
            $schemas = $this->normalizeSchemas($components->getSchemas());

            if ($schemas === []) {
                return $openApi;
            }

            $changed = false;
            $schemas = $this->updateConstraintViolationSchemas($schemas, $changed);

            if ($changed) {
                $updatedComponents = $components->withSchemas($this->createArrayObject($schemas));

                return $openApi->withComponents($updatedComponents);
            }
        }

        return $openApi;
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

            $schemaArray = $this->normalizeSchema($schema);
            if ($schemaArray === null) {
                continue;
            }

            $updated = $this->updateSchema($schemaArray);
            if ($updated === null) {
                continue;
            }

            $schemas[$key] = $this->createArrayObject($updated);
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

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeSchema(mixed $schema): ?array
    {
        return match (true) {
            $schema instanceof ArrayObject => $schema->getArrayCopy(),
            is_array($schema) => $schema,
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $items
     */
    private function createArrayObject(array $items): ArrayObject
    {
        return new ArrayObject($items);
    }
}
