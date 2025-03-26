<?php

declare(strict_types=1);

namespace App\Customer\Domain\ValueObject;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;

final readonly class CustomerUpdate
{
    public function __construct(
        public string $newInitials,
        public string $newEmail,
        public string $newPhone,
        public string $newLeadSource,
        public CustomerType $newType,
        public CustomerStatus $newStatus,
        public bool $newConfirmed,
    ) {
    }
}
