<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateTypeCommand;

interface CreateTypeFactoryInterface
{
    public function create(
        string $value
    ): CreateTypeCommand;
}
