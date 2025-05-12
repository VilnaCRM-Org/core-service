<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;

interface CustomerTransformerInterface
{
    public function transform(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed,
    ): Customer;
}
