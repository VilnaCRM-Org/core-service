<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Initials;
use App\Shared\Application\Validator\InitialsValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidatorTest extends UnitTestCase
{
    private InitialsValidator $validator;
    private ExecutionContextInterface $context;
    private Constraint $constraint;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = new InitialsValidator($this->translator);
        $this->validator->initialize($this->context);
        $this->constraint = $this->createMock(Initials::class);
    }

    public function testValidValue(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(
            $this->faker->firstName() . ' ' . $this->faker->lastName(),
            $this->constraint
        );
    }

    public function testOptional(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(
            '',
            $this->constraint
        );
    }

    public function testOptionalDefaultValue(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(
            '',
            new Initials()
        );
    }

    public function testNullValue(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(
            null,
            $this->constraint
        );
    }

    public function testInvalidType(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('The value must be a string.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            123,
            $this->constraint
        );
    }

    public function testPassedSpaces(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $error = $this->faker->word();
        $this->translator->method('trans')
            ->with('initials.spaces')
            ->willReturn($error);
        $this->context->method('buildViolation')
            ->with($error)
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            ' ',
            $this->constraint
        );
    }

    public function testMinLengthBoundary(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(
            'A',
            $this->constraint
        );
    }

    public function testEmptyStringAfterTrimIsInvalid(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $error = $this->faker->word();
        $this->translator->method('trans')
            ->with('initials.spaces')
            ->willReturn($error);
        $this->context->method('buildViolation')
            ->with($error)
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            '   ',
            $this->constraint
        );
    }

    public function testMaxLengthBoundary(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(
            str_repeat('A', 255),
            $this->constraint
        );
    }

    public function testExceedsMaxLength(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('Invalid initials length.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            str_repeat('A', 256),
            $this->constraint
        );
    }

    public function testStringCastingWithNonString(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('The value must be a string.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            ['not', 'string'],
            $this->constraint
        );
    }

    public function testInvalidLength(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('Invalid initials length.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            str_repeat('a', 256),
            $this->constraint
        );
    }

    public function testInvalidPattern(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('Invalid initials format.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            'John123',
            $this->constraint
        );
    }

    public function testValidPatternWithSpaces(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(
            'John Doe Smith',
            $this->constraint
        );
    }

    public function testInvalidPatternWithNumbers(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('Invalid initials format.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            'John 123',
            $this->constraint
        );
    }

    public function testValidateWithFloatValue(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('The value must be a string.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            3.14,
            $this->constraint
        );
    }

    public function testValidateWithObjectValue(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('The value must be a string.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            new \stdClass(),
            $this->constraint
        );
    }

    public function testValidateWithBooleanValue(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('The value must be a string.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            true,
            $this->constraint
        );
    }

    public function testValidateWithStringableObject(): void
    {
        $stringableObject = new class() {
            public function __toString(): string
            {
                return 'ValidName';
            }
        };

        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('The value must be a string.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            $stringableObject,
            $this->constraint
        );
    }

    public function testShouldSkipValidationReturnsFalseForNonString(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $this->context->method('buildViolation')
            ->with('The value must be a string.')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            42,
            $this->constraint
        );
    }

    public function testPerformValidationBothInvalidLengthAndPattern(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );

        $this->context->expects($this->exactly(2))
            ->method('buildViolation')
            ->withConsecutive(
                ['Invalid initials length.'],
                ['Invalid initials format.']
            )
            ->willReturn($constraintViolationBuilder);

        $constraintViolationBuilder->expects($this->exactly(2))
            ->method('addViolation');

        $this->validator->validate(
            str_repeat('1', 256),
            $this->constraint
        );
    }
}
