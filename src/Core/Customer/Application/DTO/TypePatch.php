<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiProperty;

final class TypePatch
{
    #[ApiProperty]
    public ?string $value = null;

    #[ApiProperty]
    public ?string $id = null;
}
