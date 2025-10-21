<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\GraphQL;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Shared\Application\GraphQL\MutationInput;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MutationInputValidatorTest extends UnitTestCase
{
    public function testValidateSucceedsWhenNoViolations(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations
            ->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $input = $this->createMutationInput();

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($input)
            ->willReturn($violations);

        $mutationInputValidator = new MutationInputValidator($validator);

        $mutationInputValidator->validate($input);

        $this->addToAssertionCount(1);
    }

    public function testValidateThrowsWhenViolationsExist(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations
            ->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $input = $this->createMutationInput();

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($input)
            ->willReturn($violations);

        $mutationInputValidator = new MutationInputValidator($validator);

        try {
            $mutationInputValidator->validate($input);
            self::fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            self::assertSame($violations, $exception->getConstraintViolationList());
        }
    }

    private function createMutationInput(): MutationInput
    {
        return new class() implements MutationInput {
        };
    }
}
