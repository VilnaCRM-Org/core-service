<?php

declare(strict_types=1);

namespace App\Shared\Application\Transformer;

interface IriTransformerInterface
{
    /**
     * Transform IRI to resource identifier or return the value as-is if already an identifier.
     * For example: '/api/customers/01HQZX...' -> '01HQZX...'
     */
    public function transform(string $idOrIri): string;
}
