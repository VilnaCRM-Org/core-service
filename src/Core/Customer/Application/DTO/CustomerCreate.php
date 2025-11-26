<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiProperty;

final readonly class CustomerCreate
{
    public function __construct(
        #[ApiProperty]
        public ?string $initials = null,
        #[ApiProperty]
        public ?string $email = null,
        #[ApiProperty]
        public ?string $phone = null,
        #[ApiProperty]
        public ?string $leadSource = null,
        #[ApiProperty]
        public ?string $type = null,
        #[ApiProperty]
        public ?string $status = null,
        #[ApiProperty]
        public ?bool $confirmed = null,
    ) {
    }
}
