<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Command;

class CreateCustomerStatusCommand implements CommandInterface
{
    private CommandResponseInterface $response;

    public function __construct(
        public readonly string $value,
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
