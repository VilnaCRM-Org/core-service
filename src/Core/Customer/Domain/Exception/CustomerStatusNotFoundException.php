<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Exception;

use RuntimeException;

final class CustomerStatusNotFoundException extends RuntimeException
{
    public function __construct(string $iri)
    {
        parent::__construct(sprintf('Customer status with IRI "%s" not found', $iri));
    }
}
