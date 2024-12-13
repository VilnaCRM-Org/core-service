<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Domain\ValueObject\UuidInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use Doctrine\ODM\MongoDB\Types\Type;

final class DomainUuidType extends Type
{
    public const NAME = 'domain_uuid';

    public function convertToDatabaseValue($value): ?string
    {
        return (string) $value;
    }

    public function convertToPHPValue($value): ?Uuid
    {
        if ($value === null) {
            return null;
        }

        $uuidFactory = new UuidFactory();
        $transformer = new UuidTransformer($uuidFactory);

        return $transformer->transformFromSymfonyUuid($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
