# Refactoring Strategies for DDD/Hexagonal/CQRS

Detailed refactoring patterns specific to this project's hexagonal architecture, Domain-Driven Design, and CQRS implementation.

## 🏛️ Architecture Reference

This project follows the **Hexagonal Architecture (Ports & Adapters)** with **DDD** and **CQRS** patterns based on:

- 📚 **Reference Implementation**: [CodelyTV PHP DDD Example](https://github.com/CodelyTV/php-ddd-example)
- 🛡️ **Architecture Enforcement**: Deptrac validates layer boundaries (see `deptrac.yaml`)

### Key Architectural Principles

1. **No Anemic Domain Models**: Business logic belongs in Domain entities/aggregates, NOT in Application "services"
2. **Application Layer Components**: Use Validators, Transformers, Factories, Processors, Resolvers (NOT generic "Services")
3. **Deptrac Compliance**: All classes MUST match the regex patterns defined in `deptrac.yaml` to be recognized in their layer
4. **Layer Dependencies** (enforced by deptrac):
   - **Domain**: No dependencies (pure business logic)
   - **Application**: Can depend on Domain + Infrastructure
   - **Infrastructure**: Can depend on Domain + Application

> ⚠️ **Critical**: When creating new classes, ensure they match deptrac's layer patterns or they will show as "uncovered" violations!

---

## ⚡ NEW: Modern PHP Refactoring Patterns (Real-World Proven)

These patterns were successfully used to achieve **94% complexity** in PHPInsights (from 93.5%).

### Pattern: Functional Composition with Array Operations

Replace iterative loops with functional array operations to reduce cyclomatic complexity.

#### Example: CustomerUpdateFactory (CCN: 10 → 5)

**BEFORE** (Complexity: 10, 11 methods):

```php
public function create(Customer $customer, array $input): CustomerUpdate
{
    return new CustomerUpdate(
        $this->resolveInitials($input, $customer),
        $this->resolveEmail($input, $customer),
        $this->resolvePhone($input, $customer),
        $this->resolveLeadSource($input, $customer),
        // ... 7 more resolver methods
    );
}

private function resolveInitials(array $input, Customer $customer): string {
    return $this->getStringValue($input['initials'] ?? null, $customer->getInitials());
}
// ... 10 more methods
```

**AFTER** (Complexity: 5, 4 methods using `array_reduce`):

```php
public function create(Customer $customer, array $input): CustomerUpdate
{
    $fields = $this->resolveStringFields($input, $customer);
    return new CustomerUpdate(...$fields, ...$this->resolveRelations($input, $customer));
}

private function resolveStringFields(array $input, Customer $customer): array
{
    $fieldMap = [
        'initials' => ['key' => 'newInitials', 'getter' => 'getInitials'],
        'email' => ['key' => 'newEmail', 'getter' => 'getEmail'],
        'phone' => ['key' => 'newPhone', 'getter' => 'getPhone'],
        'leadSource' => ['key' => 'newLeadSource', 'getter' => 'getLeadSource'],
    ];

    return array_reduce(
        array_keys($fieldMap),
        fn(array $result, string $field) => array_merge($result, [
            $fieldMap[$field]['key'] => $this->fieldResolver->resolve(
                $input[$field] ?? null,
                $customer->{$fieldMap[$field]['getter']}()
            ),
        ]),
        []
    );
}
```

**Results**:

- ✅ Reduced from 11 methods to 4 (-64%)
- ✅ Complexity: 10 → 5 (-50%)
- ✅ Maintainability: 77.13 → 98.01 (+27%)

---

### Pattern: Match Expressions (PHP 8.1+)

Replace nested `if/else` with `match` expressions for cleaner logic.

#### Example: DataCleaner (CCN: 5 → 4)

**BEFORE**:

```php
private function processValue(string|int $key, mixed $value): mixed
{
    if ($this->valueFilter->shouldRemove($key, $value)) {
        return null;
    }

    if (!is_array($value)) {
        return $value;
    }

    return $this->arrayProcessor->process($key, $value, fn($data) => $this->clean($data));
}
```

**AFTER** (Using match):

```php
private function processValue(string|int $key, mixed $value): mixed
{
    return match (true) {
        $this->valueFilter->shouldRemove($key, $value) => null,
        is_array($value) => $this->arrayProcessor->process(
            $key,
            $value,
            fn(array $data): array => $this->clean($data)
        ),
        default => $value,
    };
}
```

**Results**:

- ✅ More declarative and functional
- ✅ Reduced nesting (flat structure)
- ✅ Complexity reduced by 1

---

### Pattern: Template Method with Generics

Eliminate code duplication using generic template methods.

#### Example: CustomerRelationTransformer (CCN: 5 → 4, Methods: 6 → 3)

**BEFORE** (Duplicate methods):

```php
public function resolveType(?string $typeIri, Customer $customer): CustomerType
{
    $iri = $typeIri ?? $this->getDefaultTypeIri($customer);
    $resource = $this->convertIriToResource($iri);

    if (!$resource instanceof CustomerType) {
        throw CustomerTypeNotFoundException::withIri($iri);
    }

    return $resource;
}

public function resolveStatus(?string $statusIri, Customer $customer): CustomerStatus
{
    $iri = $statusIri ?? $this->getDefaultStatusIri($customer);
    $resource = $this->convertIriToResource($iri);

    if (!$resource instanceof CustomerStatus) {
        throw CustomerStatusNotFoundException::withIri($iri);
    }

    return $resource;
}
// + 4 more similar methods
```

**AFTER** (Generic template method):

```php
public function resolveType(?string $typeIri, Customer $customer): CustomerType
{
    return $this->resolveRelation(
        $typeIri,
        $customer->getType(),
        CustomerType::class,
        static fn(string $iri) => CustomerTypeNotFoundException::withIri($iri)
    );
}

public function resolveStatus(?string $statusIri, Customer $customer): CustomerStatus
{
    return $this->resolveRelation(
        $statusIri,
        $customer->getStatus(),
        CustomerStatus::class,
        static fn(string $iri) => CustomerStatusNotFoundException::withIri($iri)
    );
}

/**
 * @template T of object
 * @param class-string<T> $expectedClass
 * @param callable(string): \Exception $exceptionFactory
 * @return T
 */
private function resolveRelation(
    ?string $iri,
    object $default,
    string $expectedClass,
    callable $exceptionFactory
): object {
    $resolvedIri = $iri ?? $this->iriConverter->getIriFromResource($default);
    $resource = $this->iriConverter->getResourceFromIri($resolvedIri);

    if (!$resource instanceof $expectedClass) {
        throw $exceptionFactory($resolvedIri);
    }

    return $resource;
}
```

**Results**:

- ✅ Eliminated 3 duplicate methods
- ✅ Type-safe with PHP generics (`@template`)
- ✅ Complexity: 5 → 4

---

### Pattern: Array Operations Over Loops

Replace loops with `array_diff_key`, `array_filter`, `array_map`, etc.

#### Example: ParameterCleaner (CCN: 5 → 2, Methods: 5 → 2)

**BEFORE**:

```php
private function removeDisallowedProperties(array $parameter): array
{
    foreach (self::DISALLOWED_PATH_PROPERTIES as $property) {
        unset($parameter[$property]);
    }
    return $parameter;
}

private function shouldCleanParameter($parameter): bool
{
    if (!is_array($parameter)) {
        return false;
    }
    return $this->isPathParameter($parameter);
}

private function isPathParameter(array $parameter): bool
{
    if (!isset($parameter['in'])) {
        return false;
    }
    return $parameter['in'] === 'path';
}
```

**AFTER** (One method with `array_diff_key` and `match`):

```php
private function cleanParameter(mixed $parameter): mixed
{
    return match (true) {
        !is_array($parameter) => $parameter,
        !isset($parameter['in']) || $parameter['in'] !== 'path' => $parameter,
        default => array_diff_key($parameter, array_flip(self::DISALLOWED_PATH_PROPERTIES)),
    };
}
```

**Results**:

- ✅ 5 methods → 2 methods (-60%)
- ✅ Complexity: 5 → 2 (-60%)
- ✅ Ultra-clean functional approach

---

### Pattern: Extract to Application Layer (DRY Principle)

Create reusable components in the Application layer to eliminate code duplication. Use the appropriate type based on responsibility: **Validator**, **Transformer**, **Factory**, etc.

> ⚠️ **Important**: We do NOT use "Services" for anemic domain model logic. While Services are valid in DDD (Domain Services for cross-aggregate business logic), in this codebase we use specific Application layer components instead. This prevents the anemic domain model anti-pattern.
>
> **Allowed Application Layer Components** (must match deptrac patterns):
> - `Validator\*` - Validation logic (e.g., StringFieldValidator)
> - `Transformer\*` - Data transformation
> - `Factory\*` - Object creation
> - `Processor\*` - API Platform processors
> - `Resolver\*` - GraphQL resolvers
> - See `deptrac.yaml` for complete list

#### Example: StringFieldValidator (Validation Logic)

**Problem**: Multiple classes had duplicate validation logic:

```php
// In CustomerUpdateFactory
private function getStringValue(?string $newValue, string $default): string {
    return $this->hasValidContent($newValue) ? $newValue : $default;
}

private function hasValidContent(?string $value): bool {
    if ($value === null) return false;
    return strlen(trim($value)) > 0;
}

// In CustomerPatchProcessor (EXACT DUPLICATE!)
private function getNewValue(?string $newValue, string $default): string {
    return $this->hasValidContent($newValue) ? $newValue : $default;
}

private function hasValidContent(?string $value): bool {
    if ($value === null) return false;
    return strlen(trim($value)) > 0;
}
```

**Solution**: Extracted to Application layer component (Validator in this case):

```php
// src/Shared/Application/Validator/StringFieldValidator.php
final readonly class StringFieldValidator
{
    public function resolve(?string $newValue, string $defaultValue): string
    {
        return $this->hasValidContent($newValue) ? $newValue : $defaultValue;
    }

    public function hasValidContent(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return strlen(trim($value)) > 0;
    }
}

// Usage in both classes
public function __construct(
    private StringFieldValidator $fieldResolver,
) {}

$value = $this->fieldResolver->resolve($input['email'] ?? null, $customer->getEmail());
```

**Results**:

- ✅ Eliminated duplicate code in 2+ classes
- ✅ Single source of truth for validation logic
- ✅ 100% test coverage in one place
- ✅ Reusable across entire codebase
- ✅ Complies with deptrac Application layer rules
- ✅ Avoids anemic domain model anti-pattern

**Choosing the Right Component Type**:
- Validation logic → `Validator\*`
- Data transformation → `Transformer\*`
- Object creation → `Factory\*`
- Cross-aggregate business logic → `Domain\Service\*` (Domain layer, NOT Application!)
- API Platform state changes → `Processor\*`
- GraphQL field resolution → `Resolver\*`

---

### Pattern: Functional forEach Replacement with array_reduce

Replace `foreach` with `array_reduce` for cleaner functional code.

#### Example: ContentPropertyProcessor (CCN: 6 → 5)

**BEFORE**:

```php
public function process(ArrayObject $content): bool
{
    $modified = false;

    foreach ($content as $mediaType => $mediaTypeObject) {
        $modified = $this->processProperties(
            $content,
            $mediaType,
            $mediaTypeObject
        ) || $modified;
    }

    return $modified;
}
```

**AFTER** (Using `array_reduce`):

```php
public function process(ArrayObject $content): bool
{
    return array_reduce(
        iterator_to_array($content),
        fn(bool $modified, array $mediaTypeObject) => $this->processMediaType(
            $content,
            array_search($mediaTypeObject, iterator_to_array($content), true),
            $mediaTypeObject
        ) || $modified,
        false
    );
}
```

**Results**:

- ✅ Pure functional approach
- ✅ No mutable state
- ✅ Complexity: 6 → 5

---

## 📋 Quick Reference: Modern PHP Patterns

| Pattern                | When to Use                     | Complexity Reduction | Example                            |
| ---------------------- | ------------------------------- | -------------------- | ---------------------------------- |
| **`array_reduce`**     | Replace loops with accumulation | -1 to -3             | Field resolution, data aggregation |
| **`array_map`**        | Transform collections           | -1 to -2             | Data transformation                |
| **`array_filter`**     | Filter collections              | -1                   | Remove invalid data                |
| **`array_diff_key`**   | Remove keys from arrays         | -2 to -3             | Property removal                   |
| **`match` expression** | Replace if/else chains          | -1 to -3             | State machines, type handling      |
| **PHP Generics**       | Eliminate duplicate methods     | -2 to -5             | Template methods                   |
| **Named Parameters**   | Improve readability             | 0 (but clearer)      | Constructor calls                  |
| **Spread Operator**    | Merge arrays cleanly            | -1                   | Combining results                  |

---

# Refactoring Strategies for DDD/Hexagonal/CQRS

Detailed refactoring patterns specific to this project's hexagonal architecture, Domain-Driven Design, and CQRS implementation.

## Quick Start: Find What to Refactor

Before diving into refactoring patterns, identify which classes actually need refactoring.

### 1. Find Complex Classes

```bash
# Find top 10 classes that need refactoring
make analyze-complexity N=10
```

### 2. Understand the Metrics

The command shows these metrics for each class:

| Metric                          | Critical Threshold | What It Means                                |
| ------------------------------- | ------------------ | -------------------------------------------- |
| **CCN** (Cyclomatic Complexity) | > 15               | Total decision points - REFACTOR IMMEDIATELY |
| **WMC** (Weighted Method Count) | > 50               | Sum of all method complexities               |
| **Avg Complexity**              | > 5                | CCN ÷ Methods - Target is < 5                |
| **Max Complexity**              | > 10               | Highest single method complexity             |
| **Maintainability Index**       | < 65               | 0-100 scale (higher is better)               |

### 3. Apply the Right Pattern

**Once you've identified a complex class**, use the patterns below:

- **Command Handler** (CCN > 5) → [Command Handler Complexity Reduction](#command-handler-complexity-reduction)
- **Domain Entity** (CCN > 10) → [Domain Entity Refactoring](#domain-entity-refactoring)
- **Primitive Validation** → [Value Object Extraction](#value-object-extraction)
- **Cross-Entity Logic** → [Domain Service Patterns](#domain-service-patterns)
- **Complex Queries** → [Repository Complexity Management](#repository-complexity-management)
- **Multi-Responsibility Subscriber** → [Event Subscriber Simplification](#event-subscriber-simplification)
- **Logic in Processor** → [API Platform Processor Patterns](#api-platform-processor-patterns)

### 4. Example Workflow

```bash
# Step 1: Find complex classes
make analyze-complexity N=10

# Output shows:
# #1 - App\Customer\Application\CommandHandler\UpdateCustomerCommandHandler
#   🔢 CCN: 18 (CRITICAL!)
#   ⚡ Avg Complexity: 6.0
#   🔴 Max Method Complexity: 12

# Step 2: Identify the layer
# This is Application layer (CommandHandler) - should have CCN < 5

# Step 3: Apply "Command Handler Complexity Reduction" pattern below

# Step 4: Verify improvement
make analyze-complexity N=1   # Check this specific class
make phpinsights               # Verify overall quality
```

---

## Table of Contents

1. [Command Handler Complexity Reduction](#command-handler-complexity-reduction)
2. [Domain Entity Refactoring](#domain-entity-refactoring)
3. [Value Object Extraction](#value-object-extraction)
4. [Domain Service Patterns](#domain-service-patterns)
5. [Repository Complexity Management](#repository-complexity-management)
6. [Event Subscriber Simplification](#event-subscriber-simplification)
7. [API Platform Processor Patterns](#api-platform-processor-patterns)
8. [Layer-Specific Guidelines](#layer-specific-guidelines)

---

## Command Handler Complexity Reduction

### Pattern: Extract Domain Logic

Command handlers should orchestrate, not contain business logic.

#### ❌ BAD: Business Logic in Handler

```php
// Cyclomatic complexity: 12
final readonly class UpdateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $this->repository->find($command->id);

        // ❌ Business logic in handler
        if ($command->email !== $customer->email()) {
            if (!filter_var($command->email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidEmailException();
            }

            if ($this->repository->emailExists($command->email)) {
                throw new EmailAlreadyExistsException();
            }

            $customer->setEmail($command->email);
        }

        if ($command->status !== $customer->status()) {
            if ($customer->hasActiveOrders() && $command->status === 'inactive') {
                throw new CannotDeactivateWithActiveOrdersException();
            }

            if ($customer->balance() < 0 && $command->status === 'active') {
                throw new CannotActivateWithNegativeBalanceException();
            }

            $customer->setStatus($command->status);
        }

        $this->repository->save($customer);
    }
}
```

**Complexity**: 12 (too high for Application layer)

#### ✅ GOOD: Domain Handles Business Logic

```php
// Cyclomatic complexity: 2
final readonly class UpdateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $this->repository->find($command->id);

        // ✅ Delegate to domain
        if ($command->email !== null) {
            $customer->changeEmail(
                Email::fromString($command->email),
                $this->emailUniquenessChecker
            );
        }

        if ($command->status !== null) {
            $customer->changeStatus(CustomerStatus::from($command->status));
        }

        $this->repository->save($customer);
        $this->eventPublisher->publish(...$customer->pullDomainEvents());
    }
}
```

**Complexity**: 2 (excellent for Application layer)

**Benefits**:

- Handler focuses on orchestration
- Business rules encapsulated in domain
- Easier to test (domain logic isolated)
- Clearer separation of concerns

---

## Domain Entity Refactoring

### Pattern: Extract Complex Validation to Value Objects

Domain entities can have higher complexity, but validation belongs in Value Objects.

#### ❌ BAD: Validation in Entity Methods

```php
class Customer extends AggregateRoot
{
    // Cyclomatic complexity: 8
    public function changeEmail(string $email, EmailUniquenessChecker $checker): void
    {
        if (empty($email)) {
            throw new EmptyEmailException();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException();
        }

        if (strlen($email) > 255) {
            throw new EmailTooLongException();
        }

        if (!preg_match('/^[a-zA-Z0-9._+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            throw new InvalidEmailFormatException();
        }

        if ($checker->exists($email)) {
            throw new EmailAlreadyExistsException();
        }

        $this->email = $email;
        $this->record(new EmailChanged($this->id, $email));
    }
}
```

#### ✅ GOOD: Validation in Value Object

```php
// Value Object handles all validation
final readonly class Email
{
    private function __construct(private string $value)
    {
        $this->validate($value);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    private function validate(string $value): void
    {
        if (empty($value)) {
            throw new EmptyEmailException();
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException();
        }

        if (strlen($value) > 255) {
            throw new EmailTooLongException();
        }

        if (!preg_match('/^[a-zA-Z0-9._+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $value)) {
            throw new InvalidEmailFormatException();
        }
    }

    public function toString(): string
    {
        return $this->value;
    }
}

// Entity method becomes simple
class Customer extends AggregateRoot
{
    // Cyclomatic complexity: 2
    public function changeEmail(Email $email, EmailUniquenessChecker $checker): void
    {
        if ($checker->exists($email)) {
            throw new EmailAlreadyExistsException();
        }

        $this->email = $email;
        $this->record(new EmailChanged($this->id, $email->toString()));
    }
}
```

**Benefits**:

- Entity complexity reduced from 8 to 2
- Value Object is reusable across entities
- Validation tested once in Value Object
- Type safety improved

---

## Value Object Extraction

### Pattern: Replace Primitive Obsession

Extract primitives into Value Objects to reduce conditional complexity.

#### ❌ BAD: Primitive Obsession

```php
class Order extends AggregateRoot
{
    private string $status;

    // Cyclomatic complexity: 6
    public function canBeCancelled(): bool
    {
        if ($this->status === 'pending') {
            return true;
        }

        if ($this->status === 'processing' && $this->paymentStatus === 'unpaid') {
            return true;
        }

        if ($this->status === 'shipped' && $this->shippingDate > new \DateTimeImmutable('-1 day')) {
            return true;
        }

        return false;
    }
}
```

#### ✅ GOOD: Value Object Encapsulates Logic

```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function canBeCancelled(PaymentStatus $paymentStatus, ?\DateTimeImmutable $shippingDate): bool
    {
        return match($this) {
            self::PENDING => true,
            self::PROCESSING => $paymentStatus === PaymentStatus::UNPAID,
            self::SHIPPED => $shippingDate && $shippingDate > new \DateTimeImmutable('-1 day'),
            default => false,
        };
    }

    public function allowsRefund(): bool
    {
        return match($this) {
            self::DELIVERED, self::SHIPPED => true,
            default => false,
        };
    }
}

class Order extends AggregateRoot
{
    private OrderStatus $status;

    // Cyclomatic complexity: 1
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled($this->paymentStatus, $this->shippingDate);
    }
}
```

**Benefits**:

- Entity complexity reduced from 6 to 1
- Status behavior centralized
- Type-safe status transitions
- Easier to add new statuses

---

## Domain Service Patterns

### Pattern: Extract Complex Cross-Entity Logic

When multiple entities interact, use Domain Services.

#### ❌ BAD: Complex Logic in Entity

```php
class Order extends AggregateRoot
{
    // Cyclomatic complexity: 15
    public function applyDiscount(Customer $customer, array $promotions): void
    {
        $totalDiscount = 0;

        if ($customer->isVip()) {
            $totalDiscount += 0.10;
        }

        if ($customer->orderCount() > 10) {
            $totalDiscount += 0.05;
        }

        foreach ($promotions as $promotion) {
            if ($promotion->isActive() && $promotion->appliesTo($this)) {
                if ($promotion->type() === 'percentage') {
                    $totalDiscount += $promotion->value();
                } elseif ($promotion->type() === 'fixed' && $this->total() > $promotion->minimum()) {
                    $this->fixedDiscount += $promotion->value();
                }
            }
        }

        if ($totalDiscount > 0.30) {
            $totalDiscount = 0.30; // Cap at 30%
        }

        if ($this->total() > 1000 && $totalDiscount < 0.15) {
            $totalDiscount = 0.15; // Minimum 15% for orders > 1000
        }

        $this->discount = $totalDiscount;
    }
}
```

#### ✅ GOOD: Domain Service Handles Complexity

```php
// Domain Service
final readonly class DiscountCalculator
{
    public function calculate(Order $order, Customer $customer, PromotionCollection $promotions): Discount
    {
        $customerDiscount = $this->calculateCustomerDiscount($customer);
        $promotionDiscount = $this->calculatePromotionDiscount($order, $promotions);
        $bulkDiscount = $this->calculateBulkDiscount($order);

        return Discount::combine([$customerDiscount, $promotionDiscount, $bulkDiscount])
            ->capped(Percentage::fromFloat(0.30))
            ->withMinimum(Percentage::fromFloat(0.15), Money::fromFloat(1000));
    }

    private function calculateCustomerDiscount(Customer $customer): Discount
    {
        // Simple, focused logic
    }

    private function calculatePromotionDiscount(Order $order, PromotionCollection $promotions): Discount
    {
        // Simple, focused logic
    }

    private function calculateBulkDiscount(Order $order): Discount
    {
        // Simple, focused logic
    }
}

class Order extends AggregateRoot
{
    // Cyclomatic complexity: 1
    public function applyDiscount(Discount $discount): void
    {
        $this->discount = $discount;
        $this->record(new DiscountApplied($this->id, $discount));
    }
}
```

**Benefits**:

- Entity complexity: 15 → 1
- Logic broken into testable units
- Discount calculation reusable
- Clear single responsibility

---

## Repository Complexity Management

### Pattern: Specification Pattern for Complex Queries

Replace complex conditional queries with Specifications.

#### ❌ BAD: Complex Query Building

```php
final class CustomerRepository
{
    // Cyclomatic complexity: 10
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('c');

        if (isset($filters['status'])) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['minBalance'])) {
            $qb->andWhere('c.balance >= :minBalance')
               ->setParameter('minBalance', $filters['minBalance']);
        }

        if (isset($filters['vipOnly']) && $filters['vipOnly']) {
            $qb->andWhere('c.vipStatus = true');
        }

        if (isset($filters['hasOrders'])) {
            if ($filters['hasOrders']) {
                $qb->andWhere('SIZE(c.orders) > 0');
            } else {
                $qb->andWhere('SIZE(c.orders) = 0');
            }
        }

        if (isset($filters['registeredAfter'])) {
            $qb->andWhere('c.createdAt >= :after')
               ->setParameter('after', $filters['registeredAfter']);
        }

        return $qb->getQuery()->getResult();
    }
}
```

#### ✅ GOOD: Specification Pattern

```php
// Specification interface (Domain layer)
interface CustomerSpecification
{
    public function isSatisfiedBy(Customer $customer): bool;
    public function applyToQueryBuilder(QueryBuilder $qb): void;
}

// Concrete specifications
final readonly class ActiveCustomersSpec implements CustomerSpecification
{
    public function applyToQueryBuilder(QueryBuilder $qb): void
    {
        $qb->andWhere('c.status = :status')
           ->setParameter('status', CustomerStatus::ACTIVE->value);
    }
}

final readonly class VipCustomersSpec implements CustomerSpecification
{
    public function applyToQueryBuilder(QueryBuilder $qb): void
    {
        $qb->andWhere('c.vipStatus = true');
    }
}

final readonly class MinimumBalanceSpec implements CustomerSpecification
{
    public function __construct(private Money $minimum) {}

    public function applyToQueryBuilder(QueryBuilder $qb): void
    {
        $qb->andWhere('c.balance >= :minBalance')
           ->setParameter('minBalance', $this->minimum->toFloat());
    }
}

// Repository method becomes simple
final class CustomerRepository
{
    // Cyclomatic complexity: 1
    public function findBySpecification(CustomerSpecification ...$specifications): array
    {
        $qb = $this->createQueryBuilder('c');

        foreach ($specifications as $spec) {
            $spec->applyToQueryBuilder($qb);
        }

        return $qb->getQuery()->getResult();
    }
}

// Usage in Application layer
$customers = $this->repository->findBySpecification(
    new ActiveCustomersSpec(),
    new VipCustomersSpec(),
    new MinimumBalanceSpec(Money::fromFloat(1000))
);
```

**Benefits**:

- Repository complexity: 10 → 1
- Specifications are composable
- Each specification is testable
- Easy to add new criteria

---

## Event Subscriber Simplification

### Pattern: Single Responsibility per Subscriber

Split complex subscribers into focused ones.

#### ❌ BAD: God Subscriber

```php
final readonly class CustomerEventSubscriber implements DomainEventSubscriberInterface
{
    // Cyclomatic complexity: 12
    public function __invoke(DomainEvent $event): void
    {
        if ($event instanceof CustomerCreated) {
            $this->sendWelcomeEmail($event);
            $this->createLoyaltyAccount($event);
            $this->notifySlack($event);
            $this->updateAnalytics($event);
        } elseif ($event instanceof CustomerEmailChanged) {
            $this->updateMailingList($event);
            $this->verifyNewEmail($event);
            $this->notifyOldEmail($event);
        } elseif ($event instanceof CustomerDeleted) {
            $this->anonymizeData($event);
            $this->cancelSubscriptions($event);
            $this->refundBalance($event);
        }
        // ... more event types
    }

    public static function subscribedTo(): array
    {
        return [CustomerCreated::class, CustomerEmailChanged::class, CustomerDeleted::class];
    }
}
```

#### ✅ GOOD: Focused Subscribers

```php
// One subscriber per responsibility
final readonly class SendWelcomeEmailOnCustomerCreated implements DomainEventSubscriberInterface
{
    // Cyclomatic complexity: 1
    public function __invoke(DomainEvent $event): void
    {
        assert($event instanceof CustomerCreated);

        $this->mailer->send(
            WelcomeEmail::for($event->customerId(), $event->email())
        );
    }

    public static function subscribedTo(): array
    {
        return [CustomerCreated::class];
    }
}

final readonly class CreateLoyaltyAccountOnCustomerCreated implements DomainEventSubscriberInterface
{
    // Cyclomatic complexity: 1
    public function __invoke(DomainEvent $event): void
    {
        assert($event instanceof CustomerCreated);

        $this->loyaltyService->createAccount($event->customerId());
    }

    public static function subscribedTo(): array
    {
        return [CustomerCreated::class];
    }
}

final readonly class UpdateMailingListOnEmailChanged implements DomainEventSubscriberInterface
{
    // Cyclomatic complexity: 1
    public function __invoke(DomainEvent $event): void
    {
        assert($event instanceof CustomerEmailChanged);

        $this->mailingListService->updateEmail(
            $event->customerId(),
            $event->oldEmail(),
            $event->newEmail()
        );
    }

    public static function subscribedTo(): array
    {
        return [CustomerEmailChanged::class];
    }
}
```

**Benefits**:

- Each subscriber: complexity 1
- Easy to test in isolation
- Easy to enable/disable features
- Clear responsibilities

---

## API Platform Processor Patterns

### Pattern: Delegate to Command Handlers

API Platform Processors should only map and dispatch.

#### ❌ BAD: Business Logic in Processor

```php
final readonly class CustomerProcessor implements ProcessorInterface
{
    // Cyclomatic complexity: 8
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof Customer) {
            throw new \InvalidArgumentException();
        }

        // ❌ Validation in processor
        if (empty($data->email)) {
            throw new ValidationException('Email required');
        }

        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email');
        }

        // ❌ Business logic in processor
        if ($this->repository->emailExists($data->email)) {
            throw new ConflictException('Email already exists');
        }

        $this->repository->save($data);

        // ❌ Event publishing in processor
        $this->eventBus->publish(new CustomerCreated($data->id, $data->email));

        return $data;
    }
}
```

#### ✅ GOOD: Thin Processor, Delegate to Command

```php
final readonly class CustomerProcessor implements ProcessorInterface
{
    // Cyclomatic complexity: 1
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $command = CreateCustomerCommand::fromApiResource($data);

        $this->commandBus->dispatch($command);

        return $this->repository->find($command->id);
    }
}

// Command Handler handles everything
final readonly class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __invoke(CreateCustomerCommand $command): void
    {
        $email = Email::fromString($command->email); // Validates

        $customer = Customer::create(
            CustomerId::fromString($command->id),
            $email,
            $this->emailUniquenessChecker
        );

        $this->repository->save($customer);
        $this->eventPublisher->publish(...$customer->pullDomainEvents());
    }
}
```

**Benefits**:

- Processor complexity: 8 → 1
- Business logic in domain
- Consistent with CQRS pattern
- Command handler reusable

---

## Layer-Specific Guidelines

### Domain Layer

**Acceptable complexity**: 5-10 for business logic

**When to refactor**:

- Extract to Value Objects if > 10
- Use Domain Services if multiple entities involved
- Apply Strategy pattern for complex conditionals

### Application Layer

**Acceptable complexity**: 1-3 for orchestration

**When to refactor**:

- Always extract to Domain if complexity > 3
- Command Handlers should just orchestrate
- Event Subscribers should do one thing

### Infrastructure Layer

**Acceptable complexity**: 3-5 for technical concerns

**When to refactor**:

- Use Specification pattern for query complexity
- Extract to separate classes if > 5
- Repository methods should be simple

---

## Deptrac Architecture Enforcement

Deptrac validates that your code follows the hexagonal architecture rules defined in `deptrac.yaml`.

### Running Deptrac

```bash
make deptrac
```

### Understanding Deptrac Output

**Violations** (❌ MUST BE 0):
- Classes in one layer accessing forbidden layers
- Example: Domain layer depending on Infrastructure

**Uncovered** (❌ MUST BE 0):
- Classes that don't match ANY layer regex pattern
- This happens when using wrong naming conventions

**Example of Uncovered Violation**:
```
Uncovered: App\Shared\Application\Service\StringFieldResolver
```

This class was in the wrong namespace. The fix:
- ❌ `Shared\Application\Service\*` → Not in deptrac patterns
- ✅ `Shared\Application\Validator\*` → Matches Application layer regex

### Deptrac Layer Patterns (from `deptrac.yaml`)

**Application Layer** must match:
```regex
.*\\Application\\(Transformer|Command|CommandHandler|DTO|EventListener|EventSubscriber|Factory|MutationInput|Processor|Resolver|ExceptionMessageHandler|Message).*
.*\\Shared\\Application\\(Validator|Transformer|ErrorProvider|DomainExceptionNormalizer|NotFoundExceptionNormalizer).*
```

**Domain Layer** must match:
```regex
.*\\Domain\\(Aggregate|Entity|ValueObject|Event|Exception|Factory|Repository|Collection).*
```

**Infrastructure Layer** must match:
```regex
.*\\Infrastructure\\(Bus|Transformer|Factory|Repository).*
```

### Common Deptrac Fixes

1. **"Uncovered" error**: Class doesn't match any layer pattern
   - Solution: Use correct namespace (Validator, Transformer, Factory, etc.)
   - Don't use generic "Service" namespace

2. **"Violation" error**: Layer dependency rules broken
   - Solution: Move logic to correct layer
   - Domain should NEVER depend on Application or Infrastructure

3. **New Application Layer class**:
   - ✅ Use: `Application\Validator\*`, `Application\Transformer\*`, `Application\Factory\*`
   - ❌ Avoid: `Application\Service\*`, `Application\Helper\*`

> 💡 **Tip**: Always run `make deptrac` after refactoring to ensure architectural compliance!

---

## Refactoring Checklist

Before refactoring:

- [ ] Run tests to establish baseline: `make unit-tests && make integration-tests`
- [ ] Run PHPInsights to measure current complexity: `make phpinsights`
- [ ] Identify hotspots: Methods with complexity > 10

During refactoring:

- [ ] Maintain test coverage (don't delete tests)
- [ ] Refactor one method at a time
- [ ] Run tests after each change
- [ ] Verify PHPInsights score improves

After refactoring:

- [ ] All tests pass: `make unit-tests && make integration-tests`
- [ ] PHPInsights passes: `make phpinsights` (94%+ complexity, 100% other metrics)
- [ ] Deptrac passes: `make deptrac` (no layer violations)
- [ ] Code review: Verify business logic unchanged

---

## Quick Reference: Complexity Targets by Layer

| Layer            | Acceptable Complexity | Refactor If > | Strategy                                  |
| ---------------- | --------------------- | ------------- | ----------------------------------------- |
| Domain Entity    | 5-10                  | 10            | Extract to Value Objects, Domain Services |
| Domain Service   | 3-7                   | 7             | Split responsibilities, Strategy pattern  |
| Command Handler  | 1-3                   | 3             | Move logic to Domain                      |
| Event Subscriber | 1-2                   | 2             | One subscriber per responsibility         |
| Repository       | 1-5                   | 5             | Specification pattern                     |
| API Processor    | 1-2                   | 2             | Delegate to Command Handlers              |
| Value Object     | 1-5                   | 5             | Split validation logic                    |

---

**Last Updated**: 2025-11-08
**Maintained By**: Development Team
**Review**: Update when new patterns emerge
