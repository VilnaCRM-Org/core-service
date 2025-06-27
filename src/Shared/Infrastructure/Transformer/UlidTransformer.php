<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\Factory\UlidFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidTransformer
{
    public function __construct(
        private UlidFactoryInterface $ulidFactory
    ) {
    }

    public function toDatabaseValue(?Ulid $ulid): ?Binary
    {
        if ($ulid === null) {
            return null;
        }

        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    /**
     * @param Binary|string|null $value
     */
    public function toPhpValue($value): ?Ulid
    {
        if ($value === null) {
            return null;
        }

        $binary = $value instanceof Binary ? $value->getData() : $value;

        if (!$binary instanceof SymfonyUlid) {
            $binary = SymfonyUlid::fromBinary($binary);
        }

        return $this->transformFromSymfonyUlid($binary);
    }

    public function transformFromSymfonyUlid(SymfonyUlid $symfonyUlid): Ulid
    {
        return $this->ulidFactory->createFromString($symfonyUlid->__toString());
    }
}
