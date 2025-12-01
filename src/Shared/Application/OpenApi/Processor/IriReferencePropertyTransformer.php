<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

final class IriReferencePropertyTransformer
{
    /**
     * @param array<string, scalar|array<string, scalar|null>> $schema
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    public function transform(array $schema): array
    {
        if (($schema['type'] ?? null) !== 'iri-reference') {
            return $schema;
        }

        return array_merge(
            $schema,
            ['type' => 'string', 'format' => 'iri-reference']
        );
    }
}
