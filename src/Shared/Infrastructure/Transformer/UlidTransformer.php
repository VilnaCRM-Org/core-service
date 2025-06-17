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
        if ($this->shouldReturnNull($value)) {
            return null;
        }

        $ulid = $this->createUlidFromValue($value);
        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    public function toPhpValue(mixed $binary): ?Ulid
    {
        if (!$binary instanceof SymfonyUlid) {
            $binary = SymfonyUlid::fromBinary(
                $binary
            );
        }
        return $this->transformFromSymfonyUlid($binary);
    }

    public function transformFromSymfonyUlid(SymfonyUlid $symfonyUlid): Ulid
    {
        return $this->createUlid((string) $symfonyUlid);
    }

    private function shouldReturnNull(mixed $value): bool
    {
        return $value === null || $this->isInvalidUlidString($value);
    }

    private function createUlidFromValue(mixed $value): Ulid
    {
        return $value instanceof Ulid ? $value : $this->ulidFactory->create($value);
    }

    private function createUlid(string $ulid): Ulid
    {
        return $this->ulidFactory->create($ulid);
    }

    private function isInvalidUlidString(mixed $value): bool
    {
        return is_string($value)
            && !SymfonyUlid::isValid($value);
    }
}
