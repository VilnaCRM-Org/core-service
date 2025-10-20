<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Ulid;
use App\Tests\Unit\UnitTestCase;

final class UlidTest extends UnitTestCase
{
    public function testToStringReturnsOriginalUid(): void
    {
        $uid = (string) $this->faker->ulid();
        $ulid = new Ulid($uid);
        $this->assertSame(
            $uid,
            (string) $ulid,
            'The __toString() method should return the original UID.'
        );
    }

    public function testToBinaryReturns16Bytes(): void
    {
        $uid = (string) $this->faker->ulid();
        $ulid = new Ulid($uid);
        $binary = $ulid->toBinary();

        $this->assertIsString($binary, 'toBinary() should return a string.');
        $this->assertEquals(
            16,
            strlen($binary),
            'The binary representation should be 16 bytes.'
        );
    }

    public function testBinaryHexRepresentationIs32CharactersLong(): void
    {
        $uid = (string) $this->faker->ulid();
        $ulid = new Ulid($uid);
        $binary = $ulid->toBinary();
        $hex = bin2hex($binary);

        $this->assertEquals(
            32,
            strlen($hex),
            'The hexadecimal representation should be 32 characters long.'
        );
    }
}
