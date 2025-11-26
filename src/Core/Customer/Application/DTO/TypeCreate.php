<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiProperty;

final class TypeCreate
{
    #[ApiProperty]
    public ?string $value = null;
}
