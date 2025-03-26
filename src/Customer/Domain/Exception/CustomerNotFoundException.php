<?php

declare(strict_types=1);

namespace App\Customer\Domain\Exception;

final class CustomerNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Customer not found');
    }
}
