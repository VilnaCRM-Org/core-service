<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final readonly class UlidTransformer
{
    public function __construct(
        private UlidFactory $ulidFactory,
    ) {
    }

    public function toDatabaseValue(mixed $value): ?Binary
    {
        if ($value === null) {
            return null;
        }

        $ulid = $this->convertToUlid($value);
        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    public function toPhpValue(mixed $binary): ?Ulid
    {
        if ($binary === null) {
            return null;
        }

        $symfonyUlid = $this->convertToBinary($binary);
        return $this->ulidFactory->create((string) $symfonyUlid);
    }

    private function convertToUlid(mixed $value): Ulid
    {
        if ($value instanceof Ulid) {
            return $value;
        }

        return $this->ulidFactory->create((string) $value);
    }

    private function convertToBinary(mixed $binary): SymfonyUlid
    {
        if ($binary instanceof SymfonyUlid) {
            return $binary;
        }

        return SymfonyUlid::fromBinary($binary);
    }
}
