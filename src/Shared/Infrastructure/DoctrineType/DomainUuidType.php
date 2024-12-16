<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Domain\ValueObject\UuidInterface;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;

final class DomainUuidType extends Type
{
    public const NAME = 'domain_uuid';

    public function convertToDatabaseValue($value)
    {
        if ($value instanceof UuidInterface) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }
        if ($value instanceof Binary) {
            $uuid = $value->getData();
                return new Uuid($uuid);
        }
    }

    public function convertToPHPValue($value)
    {
        if ($value instanceof UuidInterface) {
            return $value; // Already converted.
        }

        if (is_string($value)) {
            return new Uuid($value); // Convert string to Uuid object.
        }

        return null; // Handle null values gracefully.
    }

    public function closureToMongo(): string
    {
        return '$return = (string) $value;';
    }

    public function closureToPHP(): string
    {
        return '$return = new \App\Shared\Domain\ValueObject\Uuid($value);';
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
