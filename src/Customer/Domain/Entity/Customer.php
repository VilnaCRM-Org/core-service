<?php

declare(strict_types=1);

namespace App\Customer\Domain\Entity;

use DateTime;

final class Customer
{
    public function __construct(
        private string $id,
        private string $initials,
        private string $email,
        private string $phone,
        private string $leadSource,
        private string $type,
        private string $status,
        private DateTime $dateCreated,
        private DateTime $lastModifiedDate,
        private bool $confirmed
    ) {
        $this->dateCreated = $dateCreated ?? new DateTime();
        $this->lastModifiedDate = $lastModifiedDate ?? new DateTime();
    }
}
