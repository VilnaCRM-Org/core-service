<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Guard;

use App\Shared\Application\Validator\Guard\EmptyValueGuard;
use App\Shared\Application\Validator\Initials;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;

final class EmptyValueGuardTest extends UnitTestCase
{
    private EmptyValueGuard $checker;
    private Constraint $constraint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new EmptyValueGuard();
        $this->constraint = $this->createMock(Initials::class);
    }

    public function testShouldSkipNullValue(): void
    {
        $this->assertTrue($this->checker->shouldSkip(null, $this->constraint));
    }

    public function testShouldSkipEmptyStringWhenOptional(): void
    {
        $this->constraint->expects($this->once())
            ->method('isOptional')
            ->willReturn(true);

        $this->assertTrue($this->checker->shouldSkip('', $this->constraint));
    }

    public function testShouldSkipEmptyString(): void
    {
        $this->constraint->expects($this->once())
            ->method('isOptional')
            ->willReturn(false);

        $this->assertTrue($this->checker->shouldSkip('', $this->constraint));
    }

    public function testShouldNotSkipValidString(): void
    {
        $this->constraint->expects($this->never())
            ->method('isOptional');

        $this->assertFalse($this->checker->shouldSkip('valid', $this->constraint));
    }

    public function testShouldNotSkipWhitespaceString(): void
    {
        $this->constraint->expects($this->never())
            ->method('isOptional');

        $this->assertFalse($this->checker->shouldSkip('   ', $this->constraint));
    }

    public function testShouldNotSkipNonStringValue(): void
    {
        $this->constraint->expects($this->never())
            ->method('isOptional');

        $this->assertFalse($this->checker->shouldSkip(123, $this->constraint));
    }
}
