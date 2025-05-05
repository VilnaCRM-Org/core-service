<?php

declare(strict_types=1);

namespace App\Customer\Application\DTO;

final readonly class StatusPatch
{
    public function __construct(
        public ?string $value,
    ) {
    }
}
