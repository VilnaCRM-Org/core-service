<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Command;

use App\Core\Customer\Domain\Entity\CustomerTypeInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;

final class CreateTypeCommand implements CommandInterface
{
    public function __construct(
        public readonly CustomerTypeInterface $type,
    ) {
    }
}
