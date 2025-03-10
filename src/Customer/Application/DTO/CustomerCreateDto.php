<?php

declare(strict_types=1);

namespace App\Customer\Application\DTO;

final readonly class CustomerCreateDto
{
    public function __construct(
        public string $initials,
        public string $email,
        public string $phone,
        public string $leadSource,
        public string $type,
        public string $status,
        public ?bool $confirmed = false
    ) {
    }
}
