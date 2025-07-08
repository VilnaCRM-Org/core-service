<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;

final class UlidType extends Type
{
    public function convertToDatabaseValue(mixed $value): ?Binary
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Ulid) {
            return new Binary($value->toBinary(), Binary::TYPE_GENERIC);
        }

        if (is_string($value)) {
            return $this->stringToBinary($value);
        }

        return null;
    }

    public function convertToPHPValue(mixed $value): ?Ulid
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Binary) {
            return $this->binaryToUlid($value);
        }

        if (is_string($value)) {
            return $this->stringToUlid($value);
        }

        return null;
    }

    private function stringToBinary(string $value): Binary
    {
        $ulid = $this->stringToUlid($value);
        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    private function binaryToUlid(Binary $value): Ulid
    {
        $hex = bin2hex($value->getData());
        $ulidString = $this->processHex($hex);
        return $this->stringToUlid($ulidString);
    }

    private function stringToUlid(string $value): Ulid
    {
        return (new UlidFactory())->create($value);
    }

    private function processHex(string $hex): string
    {
        $binary = hex2bin($hex);

        $this->validateBinary($binary);

        return strtoupper(bin2hex($binary));
    }

    private function validateBinary(string|false $binary): void
    {
        if ($binary === false) {
            throw new \InvalidArgumentException('Invalid hex data');
        }

        if (strlen($binary) !== 16) {
            throw new \InvalidArgumentException('Invalid binary length');
        }
    }
}
