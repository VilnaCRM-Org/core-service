<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Factory\UlidFactoryInterface;
use InvalidArgumentException;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final readonly class Ulid implements UlidInterface
{
    public function __construct(private string $value)
    {
        $this->validate($value);
    }

    public static function random(): self
    {
        return new self(SymfonyUlid::generate());
    }

    public function toBinary(): string
    {
        return SymfonyUlid::fromString($this->value)->toBinary();
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function equals(UlidInterface $other): bool
    {
        return $this->value === $other->value();
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function fromFactory(UlidFactoryInterface $factory): self
    {
        return $factory->createFromString($factory->generate());
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(string $value): void
    {
        if (!SymfonyUlid::isValid($value)) {
            throw new InvalidArgumentException(
                sprintf('Invalid ULID format: %s', $value)
            );
        }
    }
}
