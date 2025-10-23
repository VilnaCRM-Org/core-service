<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final readonly class UlidTransformer
{
    public function __construct(private UlidFactory $ulidFactory)
    {
    }

    public function toDatabaseValue(mixed $value): ?Binary
    {
        if ($value === null) {
            return null;
        }

        if ($this->isInvalidString($value)) {
            return null;
        }

        if (!($value instanceof Ulid)) {
            $value = $this->ulidFactory->create($value);
        }

        return new Binary($value->toBinary(), Binary::TYPE_GENERIC);
    }

    public function toPhpValue(mixed $binary): ?Ulid
    {
        if ($binary instanceof SymfonyUlid) {
            return $this->transformFromSymfonyUlid($binary);
        }

        $symfonyUlid = SymfonyUlid::fromBinary($binary);
        return $this->transformFromSymfonyUlid($symfonyUlid);
    }

    public function transformFromSymfonyUlid(SymfonyUlid $symfonyUlid): Ulid
    {
        return $this->ulidFactory->create((string) $symfonyUlid);
    }

    private function isInvalidString(mixed $value): bool
    {
        return is_string($value) && !SymfonyUlid::isValid($value);
    }
}
