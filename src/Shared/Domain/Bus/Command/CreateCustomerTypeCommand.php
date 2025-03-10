<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Command;

use App\Customer\Application\Command\CreateTypeCommandResponse;

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
