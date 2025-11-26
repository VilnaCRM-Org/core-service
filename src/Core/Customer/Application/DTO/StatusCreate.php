<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

final class StatusCreate
{
    public ?string $value = null;

    public function __construct(?string $value = null)
    {
        $this->value = $value;
    }
}
