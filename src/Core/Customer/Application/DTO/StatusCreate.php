<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiProperty;

final readonly class StatusCreate
{
    public function __construct(
        #[ApiProperty]
        public ?string $value = null,
    ) {
    }
}
