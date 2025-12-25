<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionKeyException;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionValueException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates EMF dimension values using Symfony Validator.
 *
 * Following SOLID:
 * - Single Responsibility: Only validates EmfDimensionValue and translates violations
 * - Dependency Inversion: Depends on ValidatorInterface abstraction
 * - Open/Closed: Validation rules in YAML, can extend without modification
 */
final readonly class EmfDimensionValueValidatorService implements EmfDimensionValueValidatorInterface
{
    private const string PROPERTY_KEY = 'key';

    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    public function validate(EmfDimensionValue $dimensionValue): void
    {
        $violations = $this->validator->validate($dimensionValue);

        if ($violations->count() === 0) {
            return;
        }

        $firstViolation = $violations->get(0);
        $propertyPath = $firstViolation->getPropertyPath();
        $message = $firstViolation->getMessage();

        if ($propertyPath === self::PROPERTY_KEY) {
            throw new InvalidEmfDimensionKeyException($message);
        }

        throw new InvalidEmfDimensionValueException($message);
    }
}
