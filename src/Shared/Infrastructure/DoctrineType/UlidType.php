<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;

final class UlidType extends Type
{
    public function convertToDatabaseValue($value): ?Binary
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Ulid) {
            return $this->createBinary($value);
        }

        if (is_string($value)) {
            return $this->handleStringValue($value);
        }

        return null;
    }

    public function convertToPHPValue($value): ?Ulid
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Binary) {
            return $this->handleBinaryValue($value);
        }

        if (is_string($value)) {
            return $this->getUlidFactory()->create($value);
        }

        return null;
    }

    private function createBinary(Ulid $ulid): Binary
    {
        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    private function handleStringValue(string $value): Binary
    {
        $ulid = $this->getUlidFactory()->create($value);
        return $this->createBinary($ulid);
    }

    private function handleBinaryValue(Binary $value): Ulid
    {
        $binaryData = $value->getData();
        $hex = bin2hex($binaryData);
        $ulidString = $this->hexToUlid($hex);
        return $this->getUlidFactory()->create($ulidString);
    }

    private function getUlidFactory(): UlidFactory
    {
        return new UlidFactory();
    }

    private function hexToUlid(string $hex): string
    {
        $binaryString = hex2bin($hex);
        if ($binaryString === false || strlen($binaryString) !== 16) {
            throw new \InvalidArgumentException(
                'Invalid binary data for ULID conversion'
            );
        }

        $timestamp = substr($binaryString, 0, 6);
        $randomness = substr($binaryString, 6, 10);

        $timestampHex = bin2hex($timestamp);
        $randomnessHex = bin2hex($randomness);

        return strtoupper($timestampHex . $randomnessHex);
    }
}
