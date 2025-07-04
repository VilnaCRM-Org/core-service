<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\Factory\UlidFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidTransformer
{
    private const PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private UlidFactoryInterface $ulidFactory
    ) {
    }

    public function transformFromSymfonyUlid(SymfonyUlid $ulid): Ulid
    {
        return $this->ulidFactory->create($ulid->toBase32());
    }

    public function transformToSymfonyUlid(Ulid $ulid): SymfonyUlid
    {
        return SymfonyUlid::fromBase32($ulid->getValue());
    }

    public function transformFromString(string $ulidString): Ulid
    {
        $this->validateUlidString($ulidString);
        return $this->ulidFactory->create($ulidString);
    }

    public function toDatabaseValue(mixed $value): ?Binary
    {
        return match (true) {
            $value === null => null,
            $value instanceof Ulid => $this->createBinary($value),
            is_string($value) => $this->handleStringValue($value),
            default => null,
        };
    }

    public function toPhpValue(mixed $value): ?Ulid
    {
        return match (true) {
            $value === null => null,
            $value instanceof SymfonyUlid => $this->fromSymfonyUlid($value),
            is_string($value) => $this->fromBinaryString($value),
            default => null,
        };
    }

    private function handleStringValue(string $value): ?Binary
    {
        if (!$this->isValidUlidFormat($value)) {
            return null;
        }

        $ulid = $this->ulidFactory->create($value);
        return $this->createBinary($ulid);
    }

    private function createBinary(Ulid $ulid): Binary
    {
        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    private function fromSymfonyUlid(SymfonyUlid $value): Ulid
    {
        return $this->ulidFactory->create((string) $value);
    }

    private function fromBinaryString(string $value): Ulid
    {
        $symfonyUlid = SymfonyUlid::fromBinary($value);
        return $this->ulidFactory->create((string) $symfonyUlid);
    }

    private function validateUlidString(string $ulidString): void
    {
        if (!$this->isValidUlidFormat($ulidString)) {
            throw new \InvalidArgumentException('Invalid ULID format');
        }
    }

    private function isValidUlidFormat(string $ulidString): bool
    {
        return preg_match(self::PATTERN, $ulidString) === 1;
    }
}
