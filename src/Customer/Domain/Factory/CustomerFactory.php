<?php

namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerInterface;
use App\Shared\Domain\ValueObject\Uuid;

final readonly class CustomerFactory implements CustomerFactoryInterface
{
    public function create(Uuid $id, string $initials,
                           string $email,
                           string $phone,
                           string $leadSource,
                           string $type,
                           string $status): CustomerInterface
    {
     return new Customer(
         $id,
         $initials,
         $email,
         $phone,
         $leadSource,
         $type,
         $status
     );
    }

}