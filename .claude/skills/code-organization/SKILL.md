---
name: code-organization
description: Ensure proper code organization with class names, directories, namespaces, and naming consistency following the principle "Directory X contains ONLY class type X"
---

# Code Organization Skill

## Purpose

Maintain proper code organization where:

- **Directory X contains ONLY class type X**
- Class names match their functionality
- Namespaces match directory structure
- Variables and parameters are clearly named
- Comments accurately describe functionality

## Core Principle

**"Directory X should contain ONLY class type X"**

Examples:

- `Converter/` → Contains ONLY converters
- `Transformer/` → Contains ONLY transformers
- `Validator/` → Contains ONLY validators
- `Builder/` → Contains ONLY builders
- `Fixer/` → Contains ONLY fixers
- `Cleaner/` → Contains ONLY cleaners
- `Factory/` → Contains ONLY factories

## When This Skill Activates

- Creating new classes
- Refactoring existing code
- Code review feedback about organization
- Moving classes between directories
- Renaming classes or methods

## Organization Checklist

### 1. Class Location

✅ **CORRECT:**

```php
// File: src/Shared/Infrastructure/Converter/UlidTypeConverter.php
namespace App\Shared\Infrastructure\Converter;

final class UlidTypeConverter  // IS a Converter
{
    public function toUlid(...): Ulid { }      // Converts
    public function fromBinary(...): Ulid { }  // Converts
}
```

❌ **WRONG:**

```php
// File: src/Shared/Infrastructure/Transformer/UlidTypeConverter.php
namespace App\Shared\Infrastructure\Transformer;

final class UlidTypeConverter  // IS a Converter, NOT a Transformer!
{
    // This belongs in Converter/, not Transformer/
}
```

### 2. Class Name Consistency

Class name MUST match what it does:

✅ **CORRECT:**

- `UlidValidator` → validates ULIDs
- `UlidTransformer` → transforms for Doctrine
- `UlidTypeConverter` → converts between types
- `ArrayResponseBuilder` → builds array responses
- `ContentPropertyFixer` → fixes content properties

❌ **WRONG:**

- `UlidHelper` → Too vague, what does it help with?
- `UlidConverter` → What does it convert? Be specific: `UlidTypeConverter`
- `UlidUtils` → What utilities? Extract to specific classes

### 3. Namespace Consistency

Namespace MUST match directory structure:

✅ **CORRECT:**

```php
// File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Validator;
```

❌ **WRONG:**

```php
// File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Transformer;  // Wrong namespace!
```

### 4. Variable Naming

Variables should be specific and descriptive:

✅ **CORRECT:**

```php
private UlidTypeConverter $typeConverter;  // Specific
public function fromBinary(...$value)      // Accepts any type
```

❌ **WRONG:**

```php
private UlidTypeConverter $converter;      // Vague - converter of what?
public function fromBinary(...$binary)     // Misleading - accepts ANY type
```

### 5. Parameter Naming

Parameter names MUST match their actual type/purpose:

✅ **CORRECT:**

```php
public function toPhpValue(mixed $value): Ulid  // Accepts mixed, name is generic
public function fromBinary(mixed $value): Ulid  // Accepts mixed despite method name
```

❌ **WRONG:**

```php
public function fromBinary(mixed $binary): Ulid  // Name suggests only binary accepted
```

### 6. Comment Accuracy

Comments MUST accurately describe functionality:

✅ **CORRECT:**

```php
/**
 * Validates ULID values.
 */
final class UlidValidator
```

❌ **WRONG:**

```php
/**
 * Validates ULID values before transformation.  // Misleading - it's used BY transformer
 */
final class UlidValidator
```

## Common Patterns by Layer

### Infrastructure Layer

```
src/Shared/Infrastructure/
├── Converter/       → Type conversion (Type A → Type B)
├── Transformer/     → Data transformation (DB ↔ PHP, format changes)
├── Validator/       → Validation logic
├── Factory/         → Object creation
├── Filter/          → Data filtering
├── DoctrineType/    → Doctrine custom types
└── Bus/             → Message bus implementations
```

### Application Layer

```
src/Shared/Application/OpenApi/
├── Builder/         → Building/constructing components
├── Fixer/           → Fixing/modifying properties
├── Cleaner/         → Cleaning/filtering data
├── Serializer/      → Serialization/normalization
├── Factory/         → Creating instances
├── Augmenter/       → Augmenting/enhancing data
├── Sanitizer/       → Sanitizing input
├── ValueObject/     → Value objects (data holders)
└── Extension/       → Extensions/plugins
```

### Domain Layer

```
src/Core/Customer/Domain/
├── Entity/          → Domain entities
├── ValueObject/     → Value objects
├── Repository/      → Repository interfaces
├── Factory/         → Domain factories
├── Event/           → Domain events
└── Exception/       → Domain exceptions
```

## Refactoring Workflow

When refactoring code organization:

### Step 1: Identify What the Class Does

Ask yourself:

- What is the PRIMARY responsibility of this class?
- Does it convert? → `Converter/`
- Does it transform? → `Transformer/`
- Does it validate? → `Validator/`
- Does it build? → `Builder/`
- Does it fix? → `Fixer/`
- Does it clean? → `Cleaner/`

### Step 2: Check the Directory

Current location matches responsibility?

- ✅ YES → Class is correctly placed
- ❌ NO → Move to appropriate directory

### Step 3: Update All References

1. Move the file:

   ```bash
   mv src/Path/OldDir/Class.php src/Path/NewDir/Class.php
   ```

2. Update namespace in the file:

   ```php
   namespace App\Path\NewDir;
   ```

3. Update all imports:

   ```bash
   # Find all files using this class
   grep -r "use.*OldDir\\ClassName" src/ tests/

   # Update them
   sed -i 's|OldDir\\ClassName|NewDir\\ClassName|g' affected_files
   ```

4. Run quality checks:
   ```bash
   make phpcsfixer
   make psalm
   make unit-tests
   ```

### Step 4: Verify Consistency

Check that:

- [ ] Class is in correct directory
- [ ] Namespace matches directory
- [ ] Class name matches functionality
- [ ] Variable names are specific
- [ ] Parameter names are accurate
- [ ] Comments are correct
- [ ] All tests pass

## Examples from Recent Refactoring

### Example 1: UlidValidator

**Before:**

```php
// src/Shared/Infrastructure/Transformer/UlidValidator.php
namespace App\Shared\Infrastructure\Transformer;

/**
 * Validates ULID values before transformation.
 */
final class UlidValidator { }
```

**Issues:**

- ❌ In Transformer/ directory but it's a VALIDATOR
- ❌ Comment says "before transformation" (misleading)

**After:**

```php
// src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Validator;

/**
 * Validates ULID values.
 */
final class UlidValidator { }
```

**Fixed:**

- ✅ In Validator/ directory (correct)
- ✅ Comment is accurate

### Example 2: UlidTypeConverter

**Before:**

```php
// src/Shared/Infrastructure/Transformer/UlidConverter.php
namespace App\Shared\Infrastructure\Transformer;

final class UlidConverter
{
    public function fromBinary(mixed $binary): SymfonyUlid { }
}
```

**Issues:**

- ❌ In Transformer/ directory but it's a CONVERTER
- ❌ Name "UlidConverter" too generic
- ❌ Parameter "$binary" misleading (accepts any type)

**After:**

```php
// src/Shared/Infrastructure/Converter/UlidTypeConverter.php
namespace App\Shared\Infrastructure\Converter;

final class UlidTypeConverter
{
    public function fromBinary(mixed $value): SymfonyUlid { }
}
```

**Fixed:**

- ✅ In Converter/ directory (correct)
- ✅ Name "UlidTypeConverter" is specific
- ✅ Parameter "$value" is accurate (accepts any type)

### Example 3: UlidTransformer

**Before:**

```php
final readonly class UlidTransformer
{
    public function __construct(
        private UlidTypeConverter $converter  // Vague
    ) { }

    public function toPhpValue(mixed $binary): Ulid  // Misleading name
    {
        $this->converter->fromBinary($binary);
    }
}
```

**Issues:**

- ❌ Variable "$converter" too vague
- ❌ Parameter "$binary" misleading (accepts any type)

**After:**

```php
final readonly class UlidTransformer
{
    public function __construct(
        private UlidTypeConverter $typeConverter  // Specific
    ) { }

    public function toPhpValue(mixed $value): Ulid  // Accurate
    {
        $this->typeConverter->fromBinary($value);
    }
}
```

**Fixed:**

- ✅ Variable "$typeConverter" is specific
- ✅ Parameter "$value" is accurate

## Quick Reference: Is It in the Right Place?

| Class Does                | Belongs In                | Examples             |
| ------------------------- | ------------------------- | -------------------- |
| Converts types            | `Converter/`              | UlidTypeConverter    |
| Transforms data (DB↔PHP) | `Transformer/`            | UlidTransformer      |
| Validates values          | `Validator/`              | UlidValidator        |
| Builds/constructs         | `Builder/`                | ArrayResponseBuilder |
| Fixes/modifies            | `Fixer/`                  | ContentPropertyFixer |
| Cleans/filters            | `Cleaner/`                | ArrayValueCleaner    |
| Creates objects           | `Factory/`                | UlidFactory          |
| Serializes/normalizes     | `Serializer/`             | OpenApiNormalizer    |
| Domain logic              | `Entity/`, `ValueObject/` | Customer, Ulid       |

## Pre-Commit Checklist

Before committing code organization changes:

```bash
# 1. Check code style
make phpcsfixer

# 2. Check static analysis
make psalm

# 3. Run unit tests
make unit-tests

# 4. Verify directory structure
find src/ -name "*.php" -type f | head -20

# 5. Check namespace consistency
grep -r "^namespace" src/ --include="*.php" | head -10
```

## Success Criteria

✅ Directory contains only the type of class it's named for
✅ Class name accurately describes functionality
✅ Namespace matches directory structure
✅ Variable names are specific and clear
✅ Parameter names match their actual types
✅ Comments accurately describe functionality
✅ All tests pass
✅ No Psalm errors
✅ Code style compliant

## Common Mistakes to Avoid

1. **Don't create "Helper" or "Util" classes**

   - These are code smells
   - Extract specific responsibilities into properly named classes

2. **Don't put multiple class types in one directory**

   - Converters don't belong in Transformer/
   - Validators don't belong in Converter/
   - Each directory has ONE purpose

3. **Don't use vague variable names**

   - "$converter" → "$typeConverter" (be specific)
   - "$data" → "$customerData" (be descriptive)
   - "$value" is OK for parameters accepting ANY type

4. **Don't mismatch parameter names with types**

   - If parameter accepts `mixed`, don't name it after one type
   - `fromBinary(mixed $binary)` → `fromBinary(mixed $value)`

5. **Don't write misleading comments**
   - Comments should describe WHAT it does, not assumptions
   - "before transformation" → wrong if it's used BY transformer
   - "Validates ULID values" → correct and clear

## PHP Best Practices

### Constructor Property Promotion

Always use PHP 8.0+ constructor property promotion for cleaner code:

✅ **CORRECT (Constructor Promotion):**

```php
final readonly class CustomerUpdateFactory
{
    public function __construct(
        private CustomerRelationTransformerInterface $relationResolver,
        private CustomerUpdateScalarResolver $scalarResolver,
    ) {
    }
}
```

❌ **WRONG (Old Style):**

```php
final readonly class CustomerUpdateFactory
{
    private CustomerRelationTransformerInterface $relationResolver;
    private CustomerUpdateScalarResolver $scalarResolver;

    public function __construct(
        CustomerRelationTransformerInterface $relationResolver,
        CustomerUpdateScalarResolver $scalarResolver,
    ) {
        $this->relationResolver = $relationResolver;
        $this->scalarResolver = $scalarResolver;
    }
}
```

**Benefits:**

- Less boilerplate code
- Clearer dependency declaration
- Easier to maintain
- Better readability

### Dependency Injection - No Default Instantiation

**NEVER** instantiate dependencies with default values in constructors. Always inject them.

✅ **CORRECT (Pure DI):**

```php
final class IriReferenceContentTransformer
{
    public function __construct(
        private readonly IriReferenceMediaTypeTransformerInterface $mediaTypeTransformer
    ) {
    }
}
```

❌ **WRONG (Default Instantiation):**

```php
final class IriReferenceContentTransformer
{
    private readonly IriReferenceMediaTypeTransformerInterface $mediaTypeTransformer;

    public function __construct(
        ?IriReferenceMediaTypeTransformerInterface $mediaTypeTransformer = null
    ) {
        $this->mediaTypeTransformer = $mediaTypeTransformer
            ?? new IriReferenceMediaTypeTransformer();  // ❌ Direct instantiation!
    }
}
```

**Why is this wrong?**

- Hard to test (can't mock dependencies)
- Tight coupling
- Hidden dependencies
- Violates Dependency Inversion Principle
- Makes circular dependencies possible

### Factory Pattern - No `new` in src/

In `src/`, always use factories instead of the `new` keyword (except for Value Objects, Exceptions, and Framework objects).

✅ **CORRECT (Using Factory):**

```php
// In Factory
final class CreateCustomerCommandFactory
{
    public function create(array $data): CreateCustomerCommand
    {
        return new CreateCustomerCommand(  // ✅ OK in factories
            email: $data['email'],
            type: $data['type'],
            status: $data['status']
        );
    }
}

// In Application Service
final class CustomerCreator
{
    public function __construct(
        private CreateCustomerCommandFactory $commandFactory  // ✅ Inject factory
    ) {
    }

    public function create(array $data): void
    {
        $command = $this->commandFactory->create($data);  // ✅ Use factory
    }
}
```

❌ **WRONG (Direct instantiation):**

```php
final class CustomerCreator
{
    public function create(array $data): void
    {
        $command = new CreateCustomerCommand(  // ❌ Don't use `new` in services!
            email: $data['email'],
            type: $data['type'],
            status: $data['status']
        );
    }
}
```

**When `new` is acceptable in src/:**

1. **Value Objects:**

   ```php
   return new CustomerUpdate($data);  // ✅ OK
   return new Ulid($value);           // ✅ OK
   ```

2. **Exceptions:**

   ```php
   throw new CustomerNotFoundException($id);  // ✅ OK
   throw new ValidationException($message);   // ✅ OK
   ```

3. **Framework Objects:**

   ```php
   return new Response($content);     // ✅ OK
   return new ArrayObject($data);     // ✅ OK
   ```

4. **Library Objects (OpenAPI, etc):**
   ```php
   return new OpenApi($info, $servers, $paths);  // ✅ OK
   return new PathItem();                        // ✅ OK
   ```

### Testability Best Practices

#### 1. Always Inject All Dependencies

✅ **CORRECT (All dependencies injected):**

```php
final class OpenApiFactory
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        iterable $endpointFactories,
        private PathParametersProcessor $pathParametersProcessor,
        private ParameterDescriptionProcessor $parameterDescriptionProcessor,
        private IriReferenceTypeProcessor $iriReferenceTypeProcessor,
        private TagDescriptionProcessor $tagDescriptionProcessor,
        private OpenApiExtensionsApplier $extensionsApplier
    ) {
        $this->endpointFactories = $endpointFactories;
    }
}
```

❌ **WRONG (Hidden dependencies):**

```php
final class OpenApiFactory
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        iterable $endpointFactories,
        private PathParametersProcessor $pathParametersProcessor = new PathParametersProcessor(),  // ❌
        private ParameterDescriptionProcessor $parameterDescriptionProcessor = new ParameterDescriptionProcessor(),  // ❌
    ) {
    }
}
```

**Why?** Hidden dependencies make testing impossible without reflection.

#### 2. Use Interfaces for Dependencies

✅ **CORRECT (Interface injection):**

```php
public function __construct(
    private IriReferenceContentTransformerInterface $contentTransformer,  // ✅ Interface
    private IriReferenceOperationContextResolverInterface $contextResolver  // ✅ Interface
) {
}
```

❌ **LESS FLEXIBLE (Concrete class):**

```php
public function __construct(
    private IriReferenceContentTransformer $contentTransformer,  // ❌ Concrete
    private IriReferenceOperationContextResolver $contextResolver  // ❌ Concrete
) {
}
```

**Why interfaces?**

- Easy to mock in tests
- Supports multiple implementations
- Follows SOLID principles
- Better for test doubles

#### 3. Test Files Can Use `new` Freely

In test files (`tests/`), using `new` is perfectly acceptable:

✅ **CORRECT (Tests can use `new`):**

```php
final class CustomerFactoryTest extends TestCase
{
    public function testCreateCustomer(): void
    {
        $factory = new CustomerFactory(  // ✅ OK in tests
            new CustomerTransformer(),    // ✅ OK in tests
            new CustomerValidator()       // ✅ OK in tests
        );

        $customer = $factory->create(['email' => 'test@example.com']);

        self::assertInstanceOf(Customer::class, $customer);
    }
}
```

**Why?** Tests need to instantiate objects directly for testing purposes.

## Real-World Refactoring Examples

### Example 1: Moving Resolver Classes

**Before (WRONG):**

```php
// ❌ src/Core/Customer/Application/Factory/CustomerUpdateScalarResolver.php
namespace App\Core\Customer\Application\Factory;

final class CustomerUpdateScalarResolver  // It's a RESOLVER, not a FACTORY!
{
    public function resolveScalarValue(...) { }
}
```

**After (CORRECT):**

```php
// ✅ src/Core/Customer/Application/Resolver/CustomerUpdateScalarResolver.php
namespace App\Core\Customer\Application\Resolver;

final class CustomerUpdateScalarResolver  // Now in correct directory!
{
    public function resolveScalarValue(...) { }
}
```

**What changed:**

- ✅ Moved from `Factory/` to `Resolver/`
- ✅ Updated namespace
- ✅ Updated all imports in dependent files
- ✅ Updated test file location and namespace

### Example 2: Removing Default Instantiation

**Before (WRONG):**

```php
final class CustomerUpdateFactory
{
    private CustomerUpdateScalarResolver $scalarResolver;

    public function __construct(
        private CustomerRelationTransformerInterface $relationResolver,
        ?CustomerUpdateScalarResolver $scalarResolver = null,  // ❌ Optional with default
    ) {
        $this->scalarResolver = $scalarResolver
            ?? new CustomerUpdateScalarResolver();  // ❌ Direct instantiation!
    }
}
```

**After (CORRECT):**

```php
final readonly class CustomerUpdateFactory
{
    public function __construct(
        private CustomerRelationTransformerInterface $relationResolver,
        private CustomerUpdateScalarResolver $scalarResolver,  // ✅ Required, no default
    ) {
    }
}
```

**What changed:**

- ✅ Removed default instantiation
- ✅ Made dependency required
- ✅ Used constructor property promotion
- ✅ Added `readonly` modifier
- ✅ Now fully testable with mocks

### Example 3: Fixing OpenApiFactory Dependencies

**Before (WRONG):**

```php
final class OpenApiFactory
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        iterable $endpointFactories,
        private PathParametersProcessor $pathParametersProcessor
            = new PathParametersProcessor(),  // ❌ Direct instantiation
        private ParameterDescriptionProcessor $parameterDescriptionProcessor
            = new ParameterDescriptionProcessor(),  // ❌ Direct instantiation
        // ... 3 more with direct instantiation
    ) {
    }
}
```

**After (CORRECT):**

```php
final class OpenApiFactory
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        iterable $endpointFactories,
        private PathParametersProcessor $pathParametersProcessor,  // ✅ Required
        private ParameterDescriptionProcessor $parameterDescriptionProcessor,  // ✅ Required
        private IriReferenceTypeProcessor $iriReferenceTypeProcessor,  // ✅ Required
        private TagDescriptionProcessor $tagDescriptionProcessor,  // ✅ Required
        private OpenApiExtensionsApplier $extensionsApplier  // ✅ Required
    ) {
        $this->endpointFactories = $endpointFactories;
    }
}
```

**What changed:**

- ✅ Removed all 5 default instantiations
- ✅ All dependencies now required
- ✅ Configured in `services.yaml` instead
- ✅ Fully testable with mocks

**services.yaml configuration:**

```yaml
services:
  App\Shared\Application\OpenApi\OpenApiFactory:
    arguments:
      $decorated: '@api_platform.openapi.factory'
      $endpointFactories: !tagged_iterator 'app.openapi.endpoint_factory'
      $pathParametersProcessor: '@App\Shared\Application\OpenApi\Processor\PathParametersProcessor'
      $parameterDescriptionProcessor: '@App\Shared\Application\OpenApi\Processor\ParameterDescriptionProcessor'
      $iriReferenceTypeProcessor: '@App\Shared\Application\OpenApi\Processor\IriReferenceTypeProcessor'
      $tagDescriptionProcessor: '@App\Shared\Application\OpenApi\Processor\TagDescriptionProcessor'
      $extensionsApplier: '@App\Shared\Application\OpenApi\Applier\OpenApiExtensionsApplier'
```

### Example 4: Test Coverage for Extension Properties

When refactoring to remove static methods or improve testability, ensure all code paths are covered:

**Added test for PathsMapper:**

```php
public function testProcessPreservesExtensionProperties(): void
{
    $openApi = (new OpenApi(
        new Info('title', '1.0', ''),
        [new Server('https://localhost')],
        $paths,
        new Components()
    ))->withExtensionProperty('x-custom', 'value');  // Add extension property

    $processor = new IriReferenceTypeProcessor(
        new IriReferenceContentTransformer(...),
        new IriReferenceOperationContextResolver()
    );
    $result = $processor->process($openApi);

    // Verify extension properties are preserved through PathsMapper
    self::assertSame(['x-custom' => 'value'], $result->getExtensionProperties());
}
```

**Why this matters:**

- ✅ Achieves 100% code coverage
- ✅ Catches mutation testing escapes
- ✅ Verifies important functionality (extension properties preserved)
- ✅ Documents expected behavior

## Static Methods - When to Avoid

### Prefer Instance Methods Over Static

❌ **AVOID (Static methods):**

```php
final class PathsMapper
{
    public static function map(OpenApi $openApi, callable $callback): OpenApi  // ❌ Static
    {
        $newPaths = self::createMappedPaths($openApi->getPaths(), $callback);
        return self::createOpenApiWithNewPaths($openApi, $newPaths);
    }

    private static function createMappedPaths(...): Paths  // ❌ Static
    {
        // ...
    }
}
```

✅ **PREFER (Instance methods):**

```php
final class PathsMapper
{
    public function map(OpenApi $openApi, callable $callback): OpenApi  // ✅ Instance method
    {
        $newPaths = $this->createMappedPaths($openApi->getPaths(), $callback);
        return $this->createOpenApiWithNewPaths($openApi, $newPaths);
    }

    private function createMappedPaths(...): Paths  // ✅ Instance method
    {
        // ...
    }
}
```

**Why avoid static?**

- Hard to mock in tests
- Creates tight coupling
- Cannot be polymorphic
- Cannot use dependency injection
- Makes code less flexible

**When static is acceptable:**

- Named constructors (e.g., `Parameter::required()`)
- Factory methods on value objects
- Framework requirements (e.g., `EventSubscriber::getSubscribedEvents()`)

## Refactoring Checklist

When refactoring for better code organization:

### Phase 1: Analyze

- [ ] Identify misplaced classes (wrong directory)
- [ ] Find classes with direct instantiation (`new` in constructors)
- [ ] Locate optional dependencies with defaults
- [ ] Check for static methods that should be instance methods
- [ ] Review test coverage gaps

### Phase 2: Plan

- [ ] Create target directory structure
- [ ] List all files that need namespace updates
- [ ] Identify test files that need updates
- [ ] Plan dependency injection configuration
- [ ] Estimate impact (how many files affected)

### Phase 3: Refactor

- [ ] Move classes to correct directories
- [ ] Update namespaces
- [ ] Remove default instantiations
- [ ] Convert to constructor property promotion
- [ ] Add required dependencies
- [ ] Update all imports
- [ ] Update test files
- [ ] Configure DI in services.yaml

### Phase 4: Test

- [ ] Run `make phpcsfixer`
- [ ] Run `make psalm` (0 errors required)
- [ ] Run `make unit-tests` (100% coverage required)
- [ ] Run `make mutation-testing` (100% MSI required)
- [ ] Run `make ci` (all checks must pass)

### Phase 5: Verify

- [ ] All classes in correct directories
- [ ] No direct instantiation in src/
- [ ] All dependencies injected
- [ ] Constructor promotion used everywhere
- [ ] Test coverage at 100%
- [ ] Mutation score at 100%
- [ ] CI pipeline green

## Related Skills

- **quality-standards**: Maintains code quality metrics
- **ci-workflow**: Runs comprehensive checks
- **code-review**: Handles PR feedback about organization
- **testing-workflow**: Ensures proper test coverage
