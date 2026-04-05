<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class OpenApiArrayContentSchemaUpdater
{
    public function __construct(
        private HydraCollectionSchemaFixer $hydraCollectionSchemaFixer
    ) {
    }

    /**
     * @param array<string, SchemaValue>|null $definition
     *
     * @return array<string, SchemaValue>|null
     */
    public function update($definition)
    {
        $normalizedDefinition = SchemaNormalizer::normalize($definition);
        if (! array_key_exists('schema', $normalizedDefinition)) {
            return null;
        }

        $schema = $normalizedDefinition['schema'];
        if (! is_array($schema) && ! $schema instanceof ArrayObject) {
            return null;
        }

        $normalizedSchema = SchemaNormalizer::normalize($schema);
        $updatedSchema = $this->hydraCollectionSchemaFixer->fixSchema($normalizedSchema);

        return match (true) {
            $updatedSchema !== null => ['schema' => $updatedSchema] + $normalizedDefinition,
            $normalizedSchema === $schema => null,
            default => ['schema' => $normalizedSchema] + $normalizedDefinition,
        };
    }
}
