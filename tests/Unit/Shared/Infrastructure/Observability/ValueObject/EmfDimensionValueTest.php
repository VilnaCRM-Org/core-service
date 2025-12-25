<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionKeyException;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionValueException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests EmfDimensionValue validation through Symfony Validator
 *
 * Validation is performed by factories using injected ValidatorInterface,
 * following SOLID principles (Dependency Inversion).
 */
final class EmfDimensionValueTest extends UnitTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()
            ->addYamlMapping(__DIR__ . '/../../../../../../config/validator/EmfDimensionValue.yaml')
            ->getValidator();
    }

    public function testCreatesWithKeyAndValue(): void
    {
        $dimension = new EmfDimensionValue('Endpoint', 'Customer');

        self::assertSame('Endpoint', $dimension->key());
        self::assertSame('Customer', $dimension->value());
    }

    public function testValidatesEmptyKey(): void
    {
        $dimension = new EmfDimensionValue('', 'value');
        $violations = $this->validator->validate($dimension);

        self::assertCount(1, $violations);
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
        self::assertSame('key', $violations->get(0)->getPropertyPath());
    }

    public function testValidatesWhitespaceOnlyKey(): void
    {
        $dimension = new EmfDimensionValue('   ', 'value');
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
    }

    public function testValidatesKeyExceeding255Characters(): void
    {
        $dimension = new EmfDimensionValue(str_repeat('a', 256), 'value');
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('must not exceed 255 characters', $violations->get(0)->getMessage());
    }

    public function testValidatesNonAsciiKey(): void
    {
        $dimension = new EmfDimensionValue('Ключ', 'value');
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('ASCII characters', $violations->get(0)->getMessage());
    }

    public function testValidatesKeyWithControlCharacters(): void
    {
        $dimension = new EmfDimensionValue("Key\x00", 'value');
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('control characters', $violations->get(0)->getMessage());
    }

    public function testValidatesKeyStartingWithColon(): void
    {
        $dimension = new EmfDimensionValue(':InvalidKey', 'value');
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('start with colon', $violations->get(0)->getMessage());
    }

    public function testValidatesEmptyValue(): void
    {
        $dimension = new EmfDimensionValue('Key', '');
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
        self::assertSame('value', $violations->get(0)->getPropertyPath());
    }

    public function testValidatesWhitespaceOnlyValue(): void
    {
        $dimension = new EmfDimensionValue('Key', '   ');
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
    }

    public function testValidatesValueExceeding1024Characters(): void
    {
        $dimension = new EmfDimensionValue('Key', str_repeat('a', 1025));
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('must not exceed 1024 characters', $violations->get(0)->getMessage());
    }

    public function testValidatesNonAsciiValue(): void
    {
        $dimension = new EmfDimensionValue('Key', 'Значение');
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('ASCII characters', $violations->get(0)->getMessage());
    }

    public function testValidatesValueWithControlCharacters(): void
    {
        $dimension = new EmfDimensionValue('Key', "Value\x1F");
        $violations = $this->validator->validate($dimension);

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('control characters', $violations->get(0)->getMessage());
    }

    public function testAcceptsMaxLengthKey(): void
    {
        $key = str_repeat('a', 255);
        $dimension = new EmfDimensionValue($key, 'value');

        $violations = $this->validator->validate($dimension);
        self::assertCount(0, $violations);
        self::assertSame($key, $dimension->key());
    }

    public function testAcceptsMaxLengthValue(): void
    {
        $value = str_repeat('a', 1024);
        $dimension = new EmfDimensionValue('Key', $value);

        $violations = $this->validator->validate($dimension);
        self::assertCount(0, $violations);
        self::assertSame($value, $dimension->value());
    }

    public function testAcceptsColonInMiddleOfKey(): void
    {
        $dimension = new EmfDimensionValue('Key:Name', 'value');

        $violations = $this->validator->validate($dimension);
        self::assertCount(0, $violations);
        self::assertSame('Key:Name', $dimension->key());
    }
}
