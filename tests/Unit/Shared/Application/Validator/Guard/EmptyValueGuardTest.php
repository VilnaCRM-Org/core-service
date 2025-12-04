<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Guard;

use App\Shared\Application\Validator\Guard\EmptyValueGuard;
use App\Shared\Application\Validator\Initials;
use App\Tests\Unit\UnitTestCase;

final class EmptyValueGuardTest extends UnitTestCase
{
    private EmptyValueGuard $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new EmptyValueGuard();
    }

    public function testShouldSkipNullValue(): void
    {
        $constraint = new Initials();
        $this->assertTrue($this->checker->shouldSkip(null, $constraint));
    }

    public function testShouldNotSkipEmptyString(): void
    {
        $constraint = new Initials();
        $this->assertFalse($this->checker->shouldSkip('', $constraint));
    }

    public function testShouldNotSkipValidString(): void
    {
        $constraint = new Initials();
        $this->assertFalse($this->checker->shouldSkip('valid', $constraint));
    }

    public function testShouldNotSkipWhitespaceString(): void
    {
        $constraint = new Initials();
        $this->assertFalse($this->checker->shouldSkip('   ', $constraint));
    }

    public function testShouldNotSkipNonStringValue(): void
    {
        $constraint = new Initials();
        $this->assertFalse($this->checker->shouldSkip(123, $constraint));
    }
}
