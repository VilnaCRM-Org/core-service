<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

final class StatusPatch
{
    public ?string $value = null;

    public ?string $id = null;

    public function __construct(?string $value = null, ?string $id = null)
    {
        $this->value = $value;
        $this->id = $id;
    }
}
