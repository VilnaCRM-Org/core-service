<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Exception;

use RuntimeException;

final class CustomerStatusNotFoundException extends RuntimeException
{
    public static function withIri(string $iri): self
    {
        return new self(
            sprintf('Customer status with IRI "%s" not found', $iri)
        );
    }
}
