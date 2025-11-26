<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiProperty;

final readonly class TypePatch
{
    public function __construct(
        #[ApiProperty]
        public ?string $value,
        #[ApiProperty]
        public ?string $id = null,
    ) {
    }
}
