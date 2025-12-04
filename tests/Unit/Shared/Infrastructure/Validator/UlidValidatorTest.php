<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validator;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Validator\UlidValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidValidatorTest extends UnitTestCase
{
    private UlidValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new UlidValidator();
    }

    public function testIsValidWithValidString(): void
    {
        $validUlid = (string) $this->faker->ulid();

        $this->assertTrue($this->validator->isValid($validUlid));
    }

    public function testIsValidWithUlidInstance(): void
    {
        $ulid = new Ulid((string) $this->faker->ulid());

        $this->assertTrue($this->validator->isValid($ulid));
    }

    public function testIsValidWithSymfonyUlidInstance(): void
    {
        $symfonyUlid = new SymfonyUlid();

        $this->assertTrue($this->validator->isValid($symfonyUlid));
    }

    public function testIsNotValidWithNull(): void
    {
        $this->assertFalse($this->validator->isValid(null));
    }

    public function testIsNotValidWithInvalidString(): void
    {
        $this->assertFalse($this->validator->isValid('invalid-ulid'));
    }

    public function testIsNotValidWithEmptyString(): void
    {
        $this->assertFalse($this->validator->isValid(''));
    }

    public function testIsNotValidWithInteger(): void
    {
        $this->assertFalse($this->validator->isValid(123));
    }

    public function testIsNotValidWithBoolean(): void
    {
        $this->assertFalse($this->validator->isValid(true));
    }
}
