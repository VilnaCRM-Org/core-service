<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Shared\Application\GraphQL\MutationInput;
use App\Shared\Application\Validator\LoggingMutationInputValidator;
use App\Shared\Application\Validator\MutationInputValidatorInterface;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class LoggingMutationInputValidatorTest extends UnitTestCase
{
    public function testDelegatesToInnerValidator(): void
    {
        $inner = $this->createMock(MutationInputValidatorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $input = new class() implements MutationInput {
        };

        $inner->expects(self::once())->method('validate')->with($input);
        $logger->expects(self::never())->method('notice');

        (new LoggingMutationInputValidator($inner, $logger))->validate($input);
    }

    public function testLogsOnValidationExceptionAndRethrows(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                message: 'error',
                messageTemplate: 'error',
                parameters: [],
                root: null,
                propertyPath: 'value',
                invalidValue: null
            ),
        ]);

        $inner = $this->createMock(MutationInputValidatorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $input = new class() implements MutationInput {
        };

        $inner->expects(self::once())
            ->method('validate')
            ->with($input)
            ->willThrowException(new ValidationException($violations));

        $logger->expects(self::once())
            ->method('notice')
            ->with(
                'Mutation input validation failed',
                [
                    'input_class' => $input::class,
                    'violations_count' => 1,
                ]
            );

        $this->expectException(ValidationException::class);

        (new LoggingMutationInputValidator($inner, $logger))->validate($input);
    }
}
