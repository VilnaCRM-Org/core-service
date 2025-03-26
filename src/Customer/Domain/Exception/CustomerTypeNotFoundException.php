<?php

declare(strict_types=1);

namespace App\Customer\Domain\Exception;

use RuntimeException;

final class CustomerTypeNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Customer type not found');
    }
}
