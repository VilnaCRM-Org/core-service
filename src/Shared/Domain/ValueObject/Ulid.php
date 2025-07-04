<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class Ulid implements UlidInterface
{
    private string $uid;

    public function __construct(string $uid)
    {
        $this->uid = $uid;
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

        $binary = hex2bin($ulid);
        if ($binary === false) {
            throw new \RuntimeException('Failed to convert ULID to binary');
        }

        return $binary;
    }

    public function getValue(): string
    {
        return $this->uid;
    }
}
