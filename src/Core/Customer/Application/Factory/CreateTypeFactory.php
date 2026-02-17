<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Domain\Entity\CustomerType;

final class CreateTypeFactory implements CreateTypeFactoryInterface
{
    #[\Override]
    public function create(CustomerType $type): CreateTypeCommand
    {
        return new CreateTypeCommand($type);
    }
}
