<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * @phpstan-type SchemaValue array|bool|float|int|object|string|null
 * @phpstan-type ContentDefinition array<string, SchemaValue>
 */
final class RequestBodySchemaRefDefinitionUpdater
{
    /**
     * @param ContentDefinition $definition
     *
     * @return ContentDefinition|null
     */
    public function update(array $definition, string $schemaRef): ?array
    {
        $schema = SchemaNormalizer::normalize($definition['schema'] ?? []);

        if ($schema === [] || $schema === ['$ref' => $schemaRef]) {
            return null;
        }

        $definition['schema'] = ['$ref' => $schemaRef];

        return $definition;
    }
}
