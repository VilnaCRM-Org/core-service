<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

final class CustomerPatch
{
    public ?string $id = null;

    public ?string $initials = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $leadSource = null;

    public ?string $type = null;

    public ?string $status = null;

    public ?bool $confirmed = null;
}
