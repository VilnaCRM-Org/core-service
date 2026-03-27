<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class CustomerUlidRefReplacer
{
    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    public function replace(array $schemas, string $schemaName): array
    {
        $schema = $this->toArray($schemas[$schemaName] ?? []);
        $properties = $this->toArray($schema['properties'] ?? []);
        $ulidProperty = $this->toArray($properties['ulid'] ?? []);
        $ref = is_string($ulidProperty['$ref'] ?? null)
            ? $ulidProperty['$ref']
            : '';

        if (! $this->isSupportedUlidReference($ref)) {
            return $schemas;
        }

        $properties['ulid'] = ['type' => 'string'];
        $schema['properties'] = $properties;
        $schemas[$schemaName] = $schema;

        return $schemas;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function toArray(ArrayObject|array|string|int|float|bool|null $value): array
    {
        return match (true) {
            $value instanceof ArrayObject => $value->getArrayCopy(),
            is_array($value) => $value,
            default => [],
        };
    }

    private function isSupportedUlidReference(string $ref): bool
    {
        return preg_match('~^#/components/schemas/UlidInterface(?:\.jsonld-output)?$~', $ref) === 1;
    }
}
