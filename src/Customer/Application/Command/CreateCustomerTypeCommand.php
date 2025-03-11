<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

final class CreateCustomerTypeCommand implements CommandInterface
{
    private CreateTypeCommandResponse $response;

    public function __construct(
        public readonly string $value,
    ) {
    }

    public function getResponse(): CreateTypeCommandResponse
    {
        return $this->response;
    }

    public function setResponse(CreateTypeCommandResponse $response): void
    {
        $this->response = $response;
    }
}
