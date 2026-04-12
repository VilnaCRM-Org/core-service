<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * @phpstan-type SchemaValue array|bool|float|int|object|string|null
 * @phpstan-type ContentDefinition array<string, SchemaValue>
 */
final class RequestBodySchemaRefDefinitionUpdater
{
    private const CUSTOMER_TYPE_CREATE_SCHEMA_REF = '#/components/schemas/CustomerType.TypeCreate';

    /**
     * @param ContentDefinition $definition
     *
     * @return ContentDefinition|null
     */
    public function update(array $definition): ?array
    {
        $schema = SchemaNormalizer::normalize($definition['schema'] ?? []);

        if ($schema === [] || $schema === ['$ref' => self::CUSTOMER_TYPE_CREATE_SCHEMA_REF]) {
            return null;
        }

        $definition['schema'] = ['$ref' => self::CUSTOMER_TYPE_CREATE_SCHEMA_REF];

        return $definition;
    }
}
