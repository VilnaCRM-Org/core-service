<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\MutationInput;

use App\Shared\Application\GraphQL\MutationInput;

final readonly class UpdateStatusMutationInput implements MutationInput
{
    public function __construct(
        public ?string $value = null,
    ) {
    }
}
