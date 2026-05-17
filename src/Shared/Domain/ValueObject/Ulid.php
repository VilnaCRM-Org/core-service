<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use InvalidArgumentException;

final class Ulid implements UlidInterface
{
    private string $uid;

    public function __construct(string $uid, bool $fromBinary = false)
    {
        if (! $fromBinary) {
            $this->uid = $uid;

            return;
        }

        $this->assertBinaryLength($uid);
        $hex = bin2hex($uid);
        $base32 = $this->hexToBase32($hex);
        $this->uid = $this->normalizeBase32($base32);
    }

    public function __toString(): string
    {
        return $this->uid;
    }

    public function toBinary(): string
    {
        $ulid = strtr(
            $this->uid,
            'ABCDEFGHJKMNPQRSTVWXYZ',
            'abcdefghijklmnopqrstuv'
        );

        $ulid = sprintf(
            '%02s%05s%05s%05s%05s%05s%05s',
            base_convert(substr($ulid, 0, 2), 32, 16),
            base_convert(substr($ulid, 2, 4), 32, 16),
            base_convert(substr($ulid, 6, 4), 32, 16),
            base_convert(substr($ulid, 10, 4), 32, 16),
            base_convert(substr($ulid, 14, 4), 32, 16),
            base_convert(substr($ulid, 18, 4), 32, 16),
            base_convert(substr($ulid, 22, 4), 32, 16)
        );

        return hex2bin($ulid);
    }

    private function assertBinaryLength(string $binary): void
    {
        $length = strlen($binary);
        if ($length !== 16) {
            throw new InvalidArgumentException(sprintf(
                'fromBinary expects a 16-byte binary string, got %d bytes.',
                $length
            ));
        }
    }

    private function hexToBase32(string $hex): string
    {
        $parts = [
            substr($hex, 0, 2),
            substr($hex, 2, 5),
            substr($hex, 7, 5),
            substr($hex, 12, 5),
            substr($hex, 17, 5),
            substr($hex, 22, 5),
            substr($hex, 27, 5),
        ];

        $base32 = '';
        foreach ($parts as $index => $part) {
            $length = $index === 0 ? 2 : 4;
            $chunk = base_convert($part, 16, 32);
            $base32 .= str_pad($chunk, $length, '0', STR_PAD_LEFT);
        }

        return $base32;
    }

    private function normalizeBase32(string $base32): string
    {
        return strtr($base32, 'abcdefghijklmnopqrstuv', 'ABCDEFGHJKMNPQRSTVWXYZ');
    }
}
