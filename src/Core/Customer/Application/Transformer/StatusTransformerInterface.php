<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Domain\Entity\CustomerStatus;

interface StatusTransformerInterface
{
    public function transform(
        string $value
    ): CustomerStatus;
}
