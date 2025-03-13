<?php

declare(strict_types=1);

namespace App\Customer\Application\DTO;

final readonly class StatusCreateDto
{
    public function __construct(
        public string $value,
    ) {
    }
}
