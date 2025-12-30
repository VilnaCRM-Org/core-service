<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Shared\Application\GraphQL\MutationInput;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class MutationInputValidator implements MutationInputValidatorInterface
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
