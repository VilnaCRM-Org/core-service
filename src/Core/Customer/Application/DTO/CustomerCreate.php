<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

final class CustomerCreate
{
    public ?string $initials = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $leadSource = null;

    public ?string $type = null;

    public ?string $status = null;

    public ?bool $confirmed = null;

    public function __construct(
        ?string $initials = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $leadSource = null,
        ?string $type = null,
        ?string $status = null,
        ?bool $confirmed = null,
    ) {
        $this->initials = $initials;
        $this->email = $email;
        $this->phone = $phone;
        $this->leadSource = $leadSource;
        $this->type = $type;
        $this->status = $status;
        $this->confirmed = $confirmed;
    }
}
