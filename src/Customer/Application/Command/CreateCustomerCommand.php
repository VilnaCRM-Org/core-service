<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

class CreateCustomerCommand implements CommandInterface
{
    private CreateCustomerCommandResponse $response;

    public function __construct(
        public readonly string $initials,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $leadSource,
        public readonly string $type,
        public readonly string $status,
    ) {
    }

    public function getResponse(): CreateCustomerCommandResponse
    {
        return $this->response;
    }

    public function setResponse(CreateCustomerCommandResponse $response): void
    {
        $this->response = $response;
    }
}
