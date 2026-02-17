<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

final class IriReferencePropertyTransformer implements IriReferencePropertyTransformerInterface
{
    /**
     * @param array<string, scalar|array<string, scalar|null>> $schema
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    #[\Override]
    public function transform(array $schema): array
    {
        return ($schema['type'] ?? null) === 'iri-reference'
            ? array_merge(
                $schema,
                ['type' => 'string', 'format' => 'iri-reference']
            )
            : $schema;
    }
}
