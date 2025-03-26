<?php

declare(strict_types=1);

namespace App\Customer\Domain\ValueObject;

final readonly class CustomerStatusUpdate
{
    public function __construct(
        public string $value,
    ) {
    }
}
