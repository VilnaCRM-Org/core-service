<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\ValueObject;

final readonly class CustomerTypeUpdate
{
    public function __construct(
        public string $value,
    ) {
    }
}
