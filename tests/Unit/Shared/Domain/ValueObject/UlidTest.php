<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Ulid;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

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

    public function testFromBinaryReturnsOriginalUlid(): void
    {
        $uid = (string) $this->faker->ulid();
        $ulid = new Ulid($uid);
        $binary = $ulid->toBinary();

        $recreatedUlid = Ulid::fromBinary($binary);

        $this->assertSame($uid, (string) $recreatedUlid);
    }

    public function testFromBinaryThrowsForInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('fromBinary expects a 16-byte binary string');

        Ulid::fromBinary('too-short');
    }

    public function testFromBinaryPreservesLeadingZeros(): void
    {
        $uid = '00000000000000000000000000';
        $ulid = new Ulid($uid);
        $binary = $ulid->toBinary();

        $recreatedUlid = Ulid::fromBinary($binary);

        $this->assertSame($uid, (string) $recreatedUlid);
        $this->assertSame(26, strlen((string) $recreatedUlid));
    }

    public function testFromBinaryMatchesSymfonyUlid(): void
    {
        $hex = '00112233445566778899aabbccddeeff';
        $binary = hex2bin($hex);

        $this->assertIsString($binary, 'hex2bin should return a binary string.');

        $expected = (string) SymfonyUlid::fromBinary($binary);
        $actual = (string) Ulid::fromBinary($binary);

        $this->assertSame($expected, $actual);
    }

    public function testFromBinaryMatchesKnownUlid(): void
    {
        $hex = '00112233445566778899aabbccddeeff';
        $binary = hex2bin($hex);

        $this->assertIsString($binary, 'hex2bin should return a binary string.');

        $this->assertSame(
            '0024H36H2NCSVRH6DAQF6DVVQZ',
            (string) Ulid::fromBinary($binary)
        );
    }

    public function testHexToBase32UsesFiveCharacterLastSlice(): void
    {
        $hex = bin2hex($this->faker->randomBytes(17));

        $method = new \ReflectionMethod(Ulid::class, 'hexToBase32');
        $method->setAccessible(true);

        $actual = $method->invoke(null, $hex);

        $parts = [
            substr($hex, 0, 2),
            substr($hex, 2, 5),
            substr($hex, 7, 5),
            substr($hex, 12, 5),
            substr($hex, 17, 5),
            substr($hex, 22, 5),
            substr($hex, 27, 5),
        ];

        $expected = '';
        foreach ($parts as $index => $part) {
            $length = $index === 0 ? 2 : 4;
            $chunk = base_convert($part, 16, 32);
            $expected .= str_pad($chunk, $length, '0', STR_PAD_LEFT);
        }

        $this->assertSame($expected, $actual);
    }
}
