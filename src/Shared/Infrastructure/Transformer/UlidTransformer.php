<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\Factory\UlidFactoryInterface;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\AbstractUid as SymfonyUuid;
use Symfony\Component\Uid\Ulid;

final readonly class UlidTransformer
{
    public function __construct(
        private UlidFactoryInterface $uuidFactory,
    ) {
    }

    public function transformFromSymfonyUuid(SymfonyUuid $symfonyUuid): \App\Shared\Domain\ValueObject\Ulid
    {
        $ulid = $this->createUlid((string) $symfonyUuid);
        return $ulid;
    }

    public function toDatabase(mixed $value): ?Binary
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Binary) {
            return $value;
        }


        $ulid = $this->convertToUlid($value);
        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    public function toPHP(mixed $value): ?Ulid
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->convertToUlid($value);
    }

    private function convertToUlid(mixed $value): Ulid
    {
        if ($value instanceof Ulid) {
            return $value;
        }

        $string = $value instanceof Binary ? $value->getData() : $value;
        $ulid = Ulid::fromString($string);

        return $ulid;
    }

    private function createUlid(string $uuid): \App\Shared\Domain\ValueObject\Ulid
    {
        return $this->uuidFactory->create($uuid);
    }

    public function transformFromString(string $uuid): \App\Shared\Domain\ValueObject\Ulid
    {
        return $this->createUlid($uuid);
    }
}
