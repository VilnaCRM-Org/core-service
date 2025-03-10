<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Command;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;

class CreateCustomerCommand implements CommandInterface
{
    private CommandResponseInterface $response;

    public function __construct(
        public readonly string $initials,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $leadSource,
        public readonly CustomerType $type,
        public readonly CustomerStatus $status,
        public readonly bool $confirmed = false,
    ) {
    }

    public function getResponse(): CommandResponseInterface
    {
        return $this->response;
    }

    public function setResponse(CommandResponseInterface $response): void
    {
        $this->response = $response;
    }
}
