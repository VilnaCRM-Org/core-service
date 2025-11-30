<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Exception;

final class CustomerNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Customer with id "%s" not found', $id));
    }
}
