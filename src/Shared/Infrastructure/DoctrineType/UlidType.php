<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid;

class UlidType extends Type
{
    public const NAME = 'ulid';

    public function convertToDatabaseValue($value)
    {
        if (!$value) {
            return null;
        }
        if ($value instanceof Binary) {
            return $value;
        }
        if (!$value instanceof Ulid) {
            $value = Ulid::fromString($value);
        }
        return new Binary($value->toBinary(), Binary::TYPE_GENERIC);
    }

    public function convertToPHPValue($value)
    {
        if (!$value) {
            return null;
        }
        if ($value instanceof Ulid) {
            return $value;
        }
        $data = $value instanceof Binary ? $value->getData() : $value;
        return Ulid::fromString($data);
    }

    public function closureToMongo(): string
    {
        return '$return = $value instanceof \Symfony\Component\Uid\Ulid
        ? new \MongoDB\BSON\Binary($value->toBinary(), \MongoDB\BSON\Binary::TYPE_GENERIC)
        : null;';
    }

    public function closureToPHP(): string
    {
        return '$return = $value ? ($value instanceof \MongoDB\BSON\Binary ? \Symfony\Component\Uid\Ulid::fromString($value->getData()) : \Symfony\Component\Uid\Ulid::fromString($value)) : null;';
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
