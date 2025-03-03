<?php

declare(strict_types=1);

namespace App\Customer\Domain\Factory;

namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\CustomerType;
use Symfony\Component\Uid\Ulid;

class CustomerTypeFactory
{
    /**
     * Create a new CustomerType with a specific Ulid.
     *
     * @param string $value
     * @param Ulid|null $ulid Optionally pass a pre-generated Ulid
     * @return CustomerType
     */
    public function create(string $value, ?Ulid $ulid = null): CustomerType
    {
        $ulid = $ulid ?? new Ulid();
        return new CustomerType($value, $ulid);
    }
}
