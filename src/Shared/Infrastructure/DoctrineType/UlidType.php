<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DoctrineType;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;

final class UlidType extends Type
{
    public const NAME = 'ulid';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Converts the given value to a MongoDB Binary for storage.
     */
    public function convertToDatabaseValue(mixed $value): Binary
    {
        if ($value instanceof Binary) {
            return $value;
        }
        if (!$value instanceof Ulid) {
            $value = new Ulid($value);
        }
        return new Binary($value->toBinary(), Binary::TYPE_GENERIC);
    }

    /**
     * Converts the stored MongoDB value back into our Domain Ulid.
     */
    public function convertToPHPValue(mixed $value): ?Ulid
    {
        $ulidTransformer = new UlidTransformer(new UlidFactory());
        $binary = $value instanceof Binary ? $value->getData() : $value;
        if (!$binary instanceof \Symfony\Component\Uid\Ulid) {
            $binary = \Symfony\Component\Uid\Ulid::fromBinary($binary);
        }
        $ulid = $ulidTransformer->transformFromSymfonyUuid($binary);
        return $ulid;
    }

    public function closureToMongo(): string
    {
        return <<<'PHP'
    $return = $value instanceof \App\Shared\Domain\ValueObject\Ulid
        ? new \MongoDB\BSON\Binary($value->toBinary(), \MongoDB\BSON\Binary::TYPE_GENERIC)
        : null;
    PHP;
    }

    public function closureToPHP(): string
    {
        return <<<'PHP'
$return = $value ? (function($value) {
    $ulidTransformer = new \App\Shared\Infrastructure\Transformer\UlidTransformer(new \App\Shared\Infrastructure\Factory\UlidFactory());
    $binary = $value instanceof \MongoDB\BSON\Binary ? $value->getData() : $value;
    if (!$binary instanceof \Symfony\Component\Uid\Ulid) {
        $binary = \Symfony\Component\Uid\Ulid::fromBinary($binary);
    }
    return $ulidTransformer->transformFromSymfonyUuid($binary);
})($value) : null;
PHP;
    }
}
