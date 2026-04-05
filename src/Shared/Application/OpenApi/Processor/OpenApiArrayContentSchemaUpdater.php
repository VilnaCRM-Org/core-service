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
    public function update(?array $definition): ?array
    {
        $normalizedDefinition = SchemaNormalizer::normalize($definition);
        $schema = $this->schemaValue($normalizedDefinition);
        $normalizedSchema = SchemaNormalizer::normalize($schema);
        $updatedSchema = $this->hydraCollectionSchemaFixer->fixSchema($normalizedSchema);

        return match (true) {
            $normalizedSchema === [] => null,
            $updatedSchema !== null => ['schema' => $updatedSchema] + $normalizedDefinition,
            $normalizedSchema === $schema => null,
            default => ['schema' => $normalizedSchema] + $normalizedDefinition,
        };
    }

    /**
     * @param array<string, SchemaValue> $definition
     */
    private function schemaValue(array $definition): ArrayObject|array|string|int|float|bool|null
    {
        return match (true) {
            array_key_exists('schema', $definition) => $definition['schema'],
            default => null,
        };
    }
}
