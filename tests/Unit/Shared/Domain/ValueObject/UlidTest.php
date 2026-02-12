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
        // Use a 33-char hex string where the 33rd char differs to catch mutants
        // that change substr($hex, 27, 5) to substr($hex, 27, 6)
        $hex = str_repeat('f', 32) . 'a'; // 32 valid chars + 1 extra

        $method = new \ReflectionMethod(Ulid::class, 'hexToBase32');

        $actual = $method->invoke(null, $hex);

        // Expected calculation using exactly 5 chars from position 27
        $parts = [
            substr($hex, 0, 2),   // 'ff'
            substr($hex, 2, 5),   // 'fffff'
            substr($hex, 7, 5),   // 'fffff'
            substr($hex, 12, 5),  // 'fffff'
            substr($hex, 17, 5),  // 'fffff'
            substr($hex, 22, 5),  // 'fffff'
            substr($hex, 27, 5),  // 'fffff' NOT 'fffffa'
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
