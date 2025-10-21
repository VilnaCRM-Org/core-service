<?php

declare(strict_types=1);

namespace App\Shared\Application\GraphQL;

use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class MutationInputValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function validate(MutationInput $input): void
    {
        $violations = $this->validator->validate($input);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }
    }
}
