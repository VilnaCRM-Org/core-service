<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\Bus\Command\CommandInterface;

final readonly class UpdateCustomerStatusCommand implements CommandInterface
{
    public function __construct(
        public CustomerStatus $customerStatus,
        public CustomerStatusUpdate $update,
    ) {
    }
}
