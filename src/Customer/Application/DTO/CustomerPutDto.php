<?php

namespace App\Customer\Application\DTO;

final readonly class CustomerPutDto
{
    public function __construct(
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $leadSource = null,
        public ?string $type = null,
        public ?string $status = null,
    ) {
    }

}