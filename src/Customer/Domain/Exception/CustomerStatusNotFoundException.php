<?php

declare(strict_types=1);

namespace App\Customer\Domain\Exception;

use RuntimeException;

final class CustomerStatusNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Customer status not found');
    }
}
