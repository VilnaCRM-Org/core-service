<?php

declare(strict_types=1);

/**
 * Example 1: Fixing Domain → Symfony Validator Constraint Violations
 *
 * VIOLATION:
 * Domain must not depend on Symfony
 *   src/Customer/Domain/Entity/Customer.php:8
 *     uses Symfony\Component\Validator\Constraints as Assert
 */

// ============================================================================
// BEFORE (WRONG) - Domain entity with Symfony validation attributes
// ============================================================================

namespace App\Customer\Domain\Entity;

use Symfony\Component\Validator\Constraints as Assert;  // VIOLATION!
use Symfony\Component\Uid\Ulid;

class CustomerBefore
{
    #[Assert\NotNull]
    private Ulid $id;

    #[Assert\Email(message: 'Invalid email format')]  // VIOLATION!
    #[Assert\NotBlank]
    private string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]  // VIOLATION!
    private string $name;

    #[Assert\PositiveOrZero]  // VIOLATION!
    private int $loyaltyPoints;
}

// ============================================================================
// AFTER (CORRECT) - Pure domain entity with Value Objects
// ============================================================================

namespace App\Customer\Domain\Entity;

use App\Customer\Domain\ValueObject\CustomerId;
use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Customer\Domain\ValueObject\LoyaltyPoints;
use App\Shared\Domain\Aggregate\AggregateRoot;

final class Customer extends AggregateRoot
{
    private CustomerId $id;
    private Email $email;
    private CustomerName $name;
    private LoyaltyPoints $loyaltyPoints;

    public function __construct(
        CustomerId $id,
        Email $email,
        CustomerName $name,
        LoyaltyPoints $loyaltyPoints
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->loyaltyPoints = $loyaltyPoints;
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    public function loyaltyPoints(): LoyaltyPoints
    {
        return $this->loyaltyPoints;
    }

    // Business methods (not setters!)
    public function changeEmail(Email $newEmail): void
    {
        $this->email = $newEmail;
        $this->record(new CustomerEmailChanged($this->id, $newEmail));
    }

    public function addLoyaltyPoints(int $points): void
    {
        $this->loyaltyPoints = $this->loyaltyPoints->add($points);
    }
}

// ============================================================================
// VALUE OBJECTS - Self-validating, immutable
// ============================================================================

namespace App\Customer\Domain\ValueObject;

use App\Customer\Domain\Exception\InvalidEmailException;

/**
 * Email Value Object - validates itself
 */
final readonly class Email
{
    public function __construct(private string $value)
    {
        $this->ensureIsValid($value);
    }

    private function ensureIsValid(string $value): void
    {
        if ($value === '') {
            throw new InvalidEmailException('Email cannot be empty');
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email format: {$value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function domain(): string
    {
        return explode('@', $this->value)[1];
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

namespace App\Customer\Domain\ValueObject;

use App\Customer\Domain\Exception\InvalidCustomerNameException;

/**
 * CustomerName Value Object - validates length and format
 */
final readonly class CustomerName
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 100;

    public function __construct(private string $value)
    {
        $this->ensureIsValid($value);
    }

    private function ensureIsValid(string $value): void
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidCustomerNameException('Customer name cannot be empty');
        }

        $length = mb_strlen($trimmed);

        if ($length < self::MIN_LENGTH) {
            throw new InvalidCustomerNameException(
                sprintf('Customer name must be at least %d characters, got %d', self::MIN_LENGTH, $length)
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidCustomerNameException(
                sprintf('Customer name cannot exceed %d characters, got %d', self::MAX_LENGTH, $length)
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

namespace App\Customer\Domain\ValueObject;

use App\Customer\Domain\Exception\InvalidLoyaltyPointsException;

/**
 * LoyaltyPoints Value Object - ensures non-negative value
 */
final readonly class LoyaltyPoints
{
    public function __construct(private int $value)
    {
        $this->ensureIsValid($value);
    }

    private function ensureIsValid(int $value): void
    {
        if ($value < 0) {
            throw new InvalidLoyaltyPointsException(
                "Loyalty points cannot be negative, got: {$value}"
            );
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function add(int $points): self
    {
        if ($points < 0) {
            throw new InvalidLoyaltyPointsException('Cannot add negative points');
        }

        return new self($this->value + $points);
    }

    public function subtract(int $points): self
    {
        $newValue = $this->value - $points;

        if ($newValue < 0) {
            throw new InvalidLoyaltyPointsException('Insufficient loyalty points');
        }

        return new self($newValue);
    }

    public function isGreaterThan(int $threshold): bool
    {
        return $this->value > $threshold;
    }
}

// ============================================================================
// DOMAIN EXCEPTIONS - Specific, meaningful errors
// ============================================================================

namespace App\Customer\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidEmailException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

final class InvalidCustomerNameException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

final class InvalidLoyaltyPointsException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

// ============================================================================
// USAGE IN COMMAND HANDLER
// ============================================================================

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerId;
use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Customer\Domain\ValueObject\LoyaltyPoints;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final readonly class CreateCustomerHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        // Transform primitives to Value Objects (validation happens here)
        $customer = new Customer(
            new CustomerId($command->id),
            new Email($command->email),             // Validates email format
            new CustomerName($command->name),       // Validates length
            new LoyaltyPoints($command->loyaltyPoints) // Validates non-negative
        );

        $this->repository->save($customer);
    }
}

// ============================================================================
// OPTIONAL: Application Layer DTO with Symfony Validation (for API input)
// ============================================================================

namespace App\Customer\Application\DTO;

/**
 * Application layer DTOs - validation configured via YAML
 * See config/validator/Customer.yaml for validation rules
 */
final class CreateCustomerInput
{
    public string $email;
    public string $name;
    public int $loyaltyPoints = 0;
}

/**
 * Validation configuration (PREFERRED APPROACH)
 * File: config/validator/Customer.yaml
 *
 * App\Customer\Application\DTO\CreateCustomerInput:
 *   properties:
 *     email:
 *       - NotBlank: { message: 'not.blank' }
 *       - Email: { message: 'email.invalid' }
 *     name:
 *       - NotBlank: { message: 'not.blank' }
 *       - Length:
 *           min: 2
 *           max: 100
 *     loyaltyPoints:
 *       - PositiveOrZero: { message: 'positive.or.zero' }
 */
