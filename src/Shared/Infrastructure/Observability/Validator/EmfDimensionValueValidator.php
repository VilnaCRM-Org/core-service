<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Validator;

use App\Shared\Application\Validator\EmfKey;
use App\Shared\Application\Validator\EmfValue;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionKeyException;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionValueException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates EMF dimension key-value pairs using Symfony Validator.
 *
 * Uses Symfony constraints to validate AWS CloudWatch EMF dimension requirements.
 */
final class EmfDimensionValueValidator
{
    private static ?ValidatorInterface $validator = null;

    public static function validateKey(string $key): void
    {
        $violations = self::getValidator()->validate($key, new EmfKey());

        if ($violations->count() > 0) {
            throw new InvalidEmfDimensionKeyException($violations->get(0)->getMessage());
        }
    }

    public static function validateValue(string $value): void
    {
        $violations = self::getValidator()->validate($value, new EmfValue());

        if ($violations->count() > 0) {
            throw new InvalidEmfDimensionValueException($violations->get(0)->getMessage());
        }
    }

    private static function getValidator(): ValidatorInterface
    {
        if (self::$validator === null) {
            self::$validator = Validation::createValidator();
        }

        return self::$validator;
    }
}
