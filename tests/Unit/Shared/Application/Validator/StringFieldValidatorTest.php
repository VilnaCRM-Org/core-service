<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\StringFieldValidator;
use App\Tests\Unit\UnitTestCase;

final class StringFieldValidatorTest extends UnitTestCase
{
    public function testResolveReturnsNewValueWhenValid(): void
    {
        $validator = new StringFieldValidator();

        $result = $validator->resolve('new value', 'default value');

        $this->assertSame('new value', $result);
    }

    public function testResolveReturnsDefaultValueWhenNewValueIsNull(): void
    {
        $validator = new StringFieldValidator();

        $result = $validator->resolve(null, 'default value');

        $this->assertSame('default value', $result);
    }

    public function testResolveReturnsDefaultValueWhenNewValueIsEmpty(): void
    {
        $validator = new StringFieldValidator();

        $result = $validator->resolve('', 'default value');

        $this->assertSame('default value', $result);
    }

    public function testResolveReturnsDefaultValueWhenNewValueIsWhitespaceOnly(): void
    {
        $validator = new StringFieldValidator();

        $result = $validator->resolve('   ', 'default value');

        $this->assertSame('default value', $result);
    }

    public function testHasValidContentReturnsTrueForValidString(): void
    {
        $validator = new StringFieldValidator();

        $result = $validator->hasValidContent('valid string');

        $this->assertTrue($result);
    }

    public function testHasValidContentReturnsFalseForNull(): void
    {
        $validator = new StringFieldValidator();

        $result = $validator->hasValidContent(null);

        $this->assertFalse($result);
    }

    public function testHasValidContentReturnsFalseForEmptyString(): void
    {
        $validator = new StringFieldValidator();

        $result = $validator->hasValidContent('');

        $this->assertFalse($result);
    }

    public function testHasValidContentReturnsFalseForWhitespaceOnlyString(): void
    {
        $validator = new StringFieldValidator();

        $result = $validator->hasValidContent('   ');

        $this->assertFalse($result);
    }
}
