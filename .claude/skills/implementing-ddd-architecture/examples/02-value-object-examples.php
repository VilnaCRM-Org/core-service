<?php

declare(strict_types=1);

/**
 * Example: Value Objects following DDD principles
 *
 * Location: src/Catalog/Domain/ValueObject/
 * Layer: Domain
 * Characteristics:
 * - Immutable
 * - Self-validating
 * - Equality based on value, not identity
 * - NO external dependencies
 */

namespace App\Catalog\Domain\ValueObject;

use App\Catalog\Domain\Exception\InvalidProductNameException;
use App\Catalog\Domain\Exception\InvalidMoneyException;
use App\Catalog\Domain\Exception\InvalidProductStatusException;

/**
 * Example 1: ProductName Value Object
 *
 * Encapsulates validation and business rules for product names
 */
final readonly class ProductName
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 255;

    private string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = trim($value);
    }

    private function ensureIsValid(string $value): void
    {
        $trimmed = trim($value);

        if (empty($trimmed)) {
            throw new InvalidProductNameException(
                "Product name cannot be empty"
            );
        }

        if (strlen($trimmed) < self::MIN_LENGTH) {
            throw new InvalidProductNameException(
                sprintf(
                    "Product name must be at least %d characters, got %d",
                    self::MIN_LENGTH,
                    strlen($trimmed)
                )
            );
        }

        if (strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidProductNameException(
                sprintf(
                    "Product name cannot exceed %d characters, got %d",
                    self::MAX_LENGTH,
                    strlen($trimmed)
                )
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ProductName $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

/**
 * Example 2: Money Value Object
 *
 * Encapsulates monetary values with currency
 */
final readonly class Money
{
    private int $amountInCents;
    private string $currency;

    public function __construct(int $amountInCents, string $currency = 'USD')
    {
        $this->ensureIsValid($amountInCents, $currency);
        $this->amountInCents = $amountInCents;
        $this->currency = strtoupper($currency);
    }

    private function ensureIsValid(int $amountInCents, string $currency): void
    {
        if ($amountInCents < 0) {
            throw new InvalidMoneyException(
                "Money amount cannot be negative: {$amountInCents}"
            );
        }

        $validCurrencies = ['USD', 'EUR', 'GBP'];
        if (!in_array(strtoupper($currency), $validCurrencies, true)) {
            throw new InvalidMoneyException(
                "Invalid currency: {$currency}. Must be one of: " . implode(', ', $validCurrencies)
            );
        }
    }

    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function amountInDollars(): float
    {
        return $this->amountInCents / 100;
    }

    public function isNegative(): bool
    {
        return $this->amountInCents < 0;
    }

    public function isZero(): bool
    {
        return $this->amountInCents === 0;
    }

    public function equals(Money $other): bool
    {
        return $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }

    /**
     * Value objects can have business logic!
     */
    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            $this->amountInCents + $other->amountInCents,
            $this->currency
        );
    }

    public function subtract(Money $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            $this->amountInCents - $other->amountInCents,
            $this->currency
        );
    }

    public function multiplyBy(float $multiplier): self
    {
        return new self(
            (int) round($this->amountInCents * $multiplier),
            $this->currency
        );
    }

    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidMoneyException(
                "Cannot operate on different currencies: {$this->currency} and {$other->currency}"
            );
        }
    }

    public function __toString(): string
    {
        return sprintf(
            "%s %.2f",
            $this->currency,
            $this->amountInDollars()
        );
    }
}

/**
 * Example 3: ProductStatus Enum-like Value Object
 *
 * Represents product status as a type-safe value object
 */
final readonly class ProductStatus
{
    private const DRAFT = 'draft';
    private const PUBLISHED = 'published';
    private const ARCHIVED = 'archived';

    private const VALID_STATUSES = [
        self::DRAFT,
        self::PUBLISHED,
        self::ARCHIVED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = $value;
    }

    private function ensureIsValid(string $value): void
    {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw new InvalidProductStatusException(
                "Invalid product status: {$value}. Must be one of: " . implode(', ', self::VALID_STATUSES)
            );
        }
    }

    /**
     * Named constructors for each status - type safe!
     */
    public static function draft(): self
    {
        return new self(self::DRAFT);
    }

    public static function published(): self
    {
        return new self(self::PUBLISHED);
    }

    public static function archived(): self
    {
        return new self(self::ARCHIVED);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Query methods - expressive and type-safe
     */
    public function isDraft(): bool
    {
        return $this->value === self::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->value === self::PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->value === self::ARCHIVED;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ProductStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

/**
 * Example 4: Email Value Object
 *
 * Simple but crucial - validates email format
 */
final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValidEmail($value);
        $this->value = strtolower(trim($value));
    }

    private function ensureIsValidEmail(string $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                "Invalid email address: {$value}"
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function localPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

/**
 * KEY TAKEAWAYS:
 *
 * 1. Value Objects are IMMUTABLE (readonly class or private setters)
 * 2. Validation happens in constructor - once created, always valid
 * 3. Equality based on VALUE, not identity (equals() method)
 * 4. Can contain business logic related to the value
 * 5. NO external dependencies (pure PHP)
 * 6. Use named constructors for clarity (Money::zero(), Status::draft())
 * 7. Self-documenting through type system
 */
