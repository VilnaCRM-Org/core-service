<?php

namespace App\Customer\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

class PutCustomerCommand implements CommandInterface
{
    private PutCustomerCommandResponse $response;

    public function __construct(
        public readonly string $customerId,
        public readonly ?string $initials,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $leadSource,
        public readonly ?string $type,
        public readonly ?string $status
    ) {}

    public function getResponse(): PutCustomerCommandResponse
    {
        return $this->response;
    }

    public function setResponse(PutCustomerCommandResponse $response): void
    {
        $this->response = $response;
    }
}