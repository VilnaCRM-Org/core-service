<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Exception;

final class CustomerNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Customer with id "%s" not found', $id));
    }
}
