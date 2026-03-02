<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\GraphQL\MutationInput;

interface MutationInputValidatorInterface
{
    public function validate(MutationInput $input): void;
}
