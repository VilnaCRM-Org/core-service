<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

final readonly class StatusPatch
{
    public function __construct(
        public ?string $value,
    ) {
    }
}
