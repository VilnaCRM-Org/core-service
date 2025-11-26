<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiProperty;

final class StatusCreate
{
    #[ApiProperty]
    public ?string $value = null;
}
