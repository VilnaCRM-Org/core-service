<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * Fixes property type from 'iri-reference' to proper string format.
 */
final class PropertyTypeFixer
{
    /**
     * @param array<string, mixed> $propSchema
     *
     * @return array<string, mixed>
     */
    public function fix(array $propSchema): array
    {
        return array_merge(
            $propSchema,
            ['type' => 'string', 'format' => 'iri-reference']
        );
    }

    /**
     * @param array<string, mixed> $propSchema
     */
    public function needsFix(array $propSchema): bool
    {
        return ($propSchema['type'] ?? null) === 'iri-reference';
    }
}
