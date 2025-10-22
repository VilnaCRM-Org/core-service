<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Exception;

use RuntimeException;

final class CustomerTypeNotFoundException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function withIri(string $iri): self
    {
        return new self(sprintf('Customer type with IRI "%s" not found', $iri));
    }
}
