<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class UlidInterfaceSchemaNormalizer
{
    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    public function normalize(array $schemas): array
    {
        $ulidInterface = $this->toArray($schemas['UlidInterface.jsonld-output'] ?? []);
        $properties = $this->toArray($ulidInterface['properties'] ?? []);

        if (isset($properties['ulid'])) {
            return $schemas;
        }

        $properties['ulid'] = ['type' => 'string'];
        $ulidInterface['properties'] = $properties;
        $schemas['UlidInterface.jsonld-output'] = $ulidInterface;

        return $schemas;
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    #[\ReturnTypeWillChange]
    private function toArray(ArrayObject|array|string|int|float|bool|null $value): array
    {
        /** @infection-ignore-all */
        if ($value instanceof ArrayObject) {
            return $value->getArrayCopy();
        }

        return is_array($value) ? $value : [];
    }
}
