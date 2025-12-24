<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionKeyException;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionValueException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Tests\Unit\UnitTestCase;

final class EmfDimensionValueTest extends UnitTestCase
{
    public function testCreatesWithKeyAndValue(): void
    {
        $dimension = new EmfDimensionValue('Endpoint', 'Customer');

        self::assertSame('Endpoint', $dimension->key());
        self::assertSame('Customer', $dimension->value());
    }

    public function testThrowsExceptionForEmptyKey(): void
    {
        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('non-whitespace character');

        new EmfDimensionValue('', 'value');
    }

    public function testThrowsExceptionForWhitespaceOnlyKey(): void
    {
        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('non-whitespace character');

        new EmfDimensionValue('   ', 'value');
    }

    public function testThrowsExceptionForKeyExceeding255Characters(): void
    {
        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('must not exceed 255 characters');

        new EmfDimensionValue(str_repeat('a', 256), 'value');
    }

    public function testThrowsExceptionForNonAsciiKey(): void
    {
        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('ASCII characters');

        new EmfDimensionValue('Ключ', 'value');
    }

    public function testThrowsExceptionForKeyWithControlCharacters(): void
    {
        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('control characters');

        new EmfDimensionValue("Key\x00", 'value');
    }

    public function testThrowsExceptionForKeyStartingWithColon(): void
    {
        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('start with colon');

        new EmfDimensionValue(':InvalidKey', 'value');
    }

    public function testThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidEmfDimensionValueException::class);
        $this->expectExceptionMessage('non-whitespace character');

        new EmfDimensionValue('Key', '');
    }

    public function testThrowsExceptionForWhitespaceOnlyValue(): void
    {
        $this->expectException(InvalidEmfDimensionValueException::class);
        $this->expectExceptionMessage('non-whitespace character');

        new EmfDimensionValue('Key', '   ');
    }

    public function testThrowsExceptionForValueExceeding1024Characters(): void
    {
        $this->expectException(InvalidEmfDimensionValueException::class);
        $this->expectExceptionMessage('must not exceed 1024 characters');

        new EmfDimensionValue('Key', str_repeat('a', 1025));
    }

    public function testThrowsExceptionForNonAsciiValue(): void
    {
        $this->expectException(InvalidEmfDimensionValueException::class);
        $this->expectExceptionMessage('ASCII characters');

        new EmfDimensionValue('Key', 'Значение');
    }

    public function testThrowsExceptionForValueWithControlCharacters(): void
    {
        $this->expectException(InvalidEmfDimensionValueException::class);
        $this->expectExceptionMessage('control characters');

        new EmfDimensionValue('Key', "Value\x1F");
    }

    public function testAcceptsMaxLengthKey(): void
    {
        $key = str_repeat('a', 255);
        $dimension = new EmfDimensionValue($key, 'value');

        self::assertSame($key, $dimension->key());
    }

    public function testAcceptsMaxLengthValue(): void
    {
        $value = str_repeat('a', 1024);
        $dimension = new EmfDimensionValue('Key', $value);

        self::assertSame($value, $dimension->value());
    }

    public function testAcceptsColonInMiddleOfKey(): void
    {
        $dimension = new EmfDimensionValue('Key:Name', 'value');

        self::assertSame('Key:Name', $dimension->key());
    }
}
