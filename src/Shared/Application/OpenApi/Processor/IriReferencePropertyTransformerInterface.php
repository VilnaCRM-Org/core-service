<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * @internal Transformation contract extracted to simplify unit testing with custom doubles.
 */
interface IriReferencePropertyTransformerInterface
{
    /**
     * @param array<string, scalar|array<string, scalar|null>> $schema
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    public function transform(array $schema): array;
}
