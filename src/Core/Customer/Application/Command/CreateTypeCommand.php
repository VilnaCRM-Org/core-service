<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;

final class CreateTypeCommand implements CommandInterface
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
