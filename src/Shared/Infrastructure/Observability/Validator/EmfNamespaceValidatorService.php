<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\Exception\InvalidEmfNamespaceException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfNamespaceValue;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates EMF namespace values using Symfony Validator.
 *
 * Following SOLID:
 * - Single Responsibility: Only validates EmfNamespaceValue and translates violations
 * - Dependency Inversion: Depends on ValidatorInterface abstraction
 * - Open/Closed: Validation rules in YAML, can extend without modification
 */
final readonly class EmfNamespaceValidatorService implements EmfNamespaceValidatorInterface
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    public function validate(EmfNamespaceValue $namespace): void
    {
        $violations = $this->validator->validate($namespace);

        if ($violations->count() === 0) {
            return;
        }

        throw new InvalidEmfNamespaceException($violations->get(0)->getMessage());
    }
}
