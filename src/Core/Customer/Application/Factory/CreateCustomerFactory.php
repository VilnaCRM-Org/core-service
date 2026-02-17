<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Domain\Entity\CustomerInterface;

final class CreateCustomerFactory implements CreateCustomerFactoryInterface
{
    #[Override]
    public function create(
        CustomerInterface $customer
    ): CreateCustomerCommand {
        return new CreateCustomerCommand(
            $customer
        );
    }
}
