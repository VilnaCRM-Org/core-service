# Refactoring Examples

This document provides detailed before/after examples from real refactoring work in the codebase.

## Example 1: UlidValidator Misplacement

### Before (WRONG)

```php
// ❌ File: src/Shared/Infrastructure/Transformer/UlidValidator.php
namespace App\Shared\Infrastructure\Transformer;

/**
 * Validates ULID values before transformation.
 */
final class UlidValidator
{
    public function validate(string $value): bool
    {
        // Validation logic
        return true;
    }
}
```

### Issues
- ❌ In Transformer/ directory but it's a VALIDATOR
- ❌ Comment says "before transformation" (misleading)
- ❌ Wrong namespace for its responsibility

### After (CORRECT)

```php
// ✅ File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Validator;

/**
 * Validates ULID values.
 */
final class UlidValidator
{
    public function validate(string $value): bool
    {
        // Validation logic
        return true;
    }
}
```

### Fixed
- ✅ In Validator/ directory (correct)
- ✅ Comment is accurate and clear
- ✅ Namespace matches directory structure

---

## Example 2: UlidTypeConverter Naming and Location

### Before (WRONG)

```php
// ❌ File: src/Shared/Infrastructure/Transformer/UlidConverter.php
namespace App\Shared\Infrastructure\Transformer;

final class UlidConverter
{
    public function fromBinary(mixed $binary): SymfonyUlid
    {
        // Conversion logic
    }

    public function toUlid(mixed $value): SymfonyUlid
    {
        // Conversion logic
    }
}
```

### Issues
- ❌ In Transformer/ directory but it's a CONVERTER
- ❌ Name "UlidConverter" too generic
- ❌ Parameter "$binary" misleading (accepts any type)

### After (CORRECT)

```php
// ✅ File: src/Shared/Infrastructure/Converter/UlidTypeConverter.php
namespace App\Shared\Infrastructure\Converter;

final class UlidTypeConverter
{
    public function fromBinary(mixed $value): SymfonyUlid
    {
        // Conversion logic
    }

    public function toUlid(mixed $value): SymfonyUlid
    {
        // Conversion logic
    }
}
```

### Fixed
- ✅ In Converter/ directory (correct)
- ✅ Name "UlidTypeConverter" is specific
- ✅ Parameter "$value" is accurate (accepts any type)

---

## Example 3: UlidTransformer Variable Naming

### Before (WRONG)

```php
final readonly class UlidTransformer
{
    public function __construct(
        private UlidTypeConverter $converter  // ❌ Vague
    ) { }

    public function toPhpValue(mixed $binary): Ulid  // ❌ Misleading parameter name
    {
        return $this->converter->fromBinary($binary);
    }
}
```

### Issues
- ❌ Variable "$converter" too vague (converter of what?)
- ❌ Parameter "$binary" misleading (accepts any type)

### After (CORRECT)

```php
final readonly class UlidTransformer
{
    public function __construct(
        private UlidTypeConverter $typeConverter  // ✅ Specific
    ) { }

    public function toPhpValue(mixed $value): Ulid  // ✅ Accurate parameter name
    {
        return $this->typeConverter->fromBinary($value);
    }
}
```

### Fixed
- ✅ Variable "$typeConverter" is specific
- ✅ Parameter "$value" is accurate (accepts mixed type)
- ✅ Clear what type of converter it is

---

## Example 4: CustomerUpdateScalarResolver Location

### Before (WRONG)

```php
// ❌ File: src/Core/Customer/Application/Factory/CustomerUpdateScalarResolver.php
namespace App\Core\Customer\Application\Factory;

/**
 * Resolves scalar values for customer updates.
 */
final class CustomerUpdateScalarResolver
{
    public function resolveScalarValue(mixed $value, string $field): mixed
    {
        // Resolution logic
    }
}
```

### Issues
- ❌ In Factory/ directory but it's a RESOLVER
- ❌ Wrong namespace for its responsibility
- ❌ Factory directory should only contain factories

### After (CORRECT)

```php
// ✅ File: src/Core/Customer/Application/Resolver/CustomerUpdateScalarResolver.php
namespace App\Core\Customer\Application\Resolver;

/**
 * Resolves scalar values for customer updates.
 */
final class CustomerUpdateScalarResolver
{
    public function resolveScalarValue(mixed $value, string $field): mixed
    {
        // Resolution logic
    }
}
```

### Fixed
- ✅ In Resolver/ directory (correct)
- ✅ Namespace matches directory structure
- ✅ Comment remains accurate

### Migration Steps

1. **Move the file**:
   ```bash
   mv src/Core/Customer/Application/Factory/CustomerUpdateScalarResolver.php \
      src/Core/Customer/Application/Resolver/CustomerUpdateScalarResolver.php
   ```

2. **Update namespace in file**:
   ```php
   // Old
   namespace App\Core\Customer\Application\Factory;

   // New
   namespace App\Core\Customer\Application\Resolver;
   ```

3. **Find and update all imports**:
   ```bash
   grep -r "use.*Factory\\CustomerUpdateScalarResolver" src/ tests/
   # Update each file to use:
   # use App\Core\Customer\Application\Resolver\CustomerUpdateScalarResolver;
   ```

4. **Update test file location**:
   ```bash
   mv tests/Unit/Core/Customer/Application/Factory/CustomerUpdateScalarResolverTest.php \
      tests/Unit/Core/Customer/Application/Resolver/CustomerUpdateScalarResolverTest.php
   ```

5. **Run quality checks**:
   ```bash
   make phpcsfixer
   make psalm
   make unit-tests
   ```

---

## Example 5: CustomerUpdateFactory Dependencies

### Before (WRONG)

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

    public function create(array $data): CustomerUpdate
    {
        $scalarValues = $this->scalarResolver->resolveScalarValue($data);
        // Factory logic
    }
}
```

### Issues
- ❌ Optional dependency with default instantiation
- ❌ Not using constructor property promotion
- ❌ Hard to test (can't mock the resolver)
- ❌ Hidden dependency

### After (CORRECT)

```php
final readonly class CustomerUpdateFactory
{
    public function __construct(
        private CustomerRelationTransformerInterface $relationResolver,
        private CustomerUpdateScalarResolver $scalarResolver,  // ✅ Required, no default
    ) {
    }

    public function create(array $data): CustomerUpdate
    {
        $scalarValues = $this->scalarResolver->resolveScalarValue($data);
        // Factory logic
    }
}
```

### Fixed
- ✅ Removed default instantiation
- ✅ Made dependency required
- ✅ Used constructor property promotion
- ✅ Added `readonly` modifier
- ✅ Now fully testable with mocks
- ✅ Explicit dependencies

### Testing Before (HARD)

```php
// ❌ Hard to test - can't easily mock the resolver
final class CustomerUpdateFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        // Can't inject mock easily because of default instantiation
        $factory = new CustomerUpdateFactory($relationResolverMock);
        // The real CustomerUpdateScalarResolver is created internally!
    }
}
```

### Testing After (EASY)

```php
// ✅ Easy to test - can inject mocks
final class CustomerUpdateFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $relationResolverMock = $this->createMock(CustomerRelationTransformerInterface::class);
        $scalarResolverMock = $this->createMock(CustomerUpdateScalarResolver::class);

        $factory = new CustomerUpdateFactory($relationResolverMock, $scalarResolverMock);

        // Test with controlled mocks
    }
}
```

---

## Example 6: PathsMapper Static to Instance Methods

### Before (WRONG)

```php
final class PathsMapper
{
    public static function map(OpenApi $openApi, callable $callback): OpenApi  // ❌ Static
    {
        $newPaths = self::createMappedPaths($openApi->getPaths(), $callback);
        return self::createOpenApiWithNewPaths($openApi, $newPaths);
    }

    private static function createMappedPaths(Paths $paths, callable $callback): Paths  // ❌ Static
    {
        // Mapping logic
    }

    private static function createOpenApiWithNewPaths(OpenApi $openApi, Paths $newPaths): OpenApi  // ❌ Static
    {
        // Creation logic
    }
}
```

### Issues
- ❌ Static methods are hard to mock in tests
- ❌ Creates tight coupling
- ❌ Cannot use dependency injection
- ❌ Cannot be polymorphic

### After (CORRECT)

```php
final class PathsMapper
{
    public function map(OpenApi $openApi, callable $callback): OpenApi  // ✅ Instance method
    {
        $newPaths = $this->createMappedPaths($openApi->getPaths(), $callback);
        return $this->createOpenApiWithNewPaths($openApi, $newPaths);
    }

    private function createMappedPaths(Paths $paths, callable $callback): Paths  // ✅ Instance method
    {
        // Mapping logic
    }

    private function createOpenApiWithNewPaths(OpenApi $openApi, Paths $newPaths): OpenApi  // ✅ Instance method
    {
        // Creation logic
    }
}
```

### Fixed
- ✅ Instance methods are easy to mock
- ✅ Loose coupling
- ✅ Can use dependency injection if needed
- ✅ Can be extended or replaced

### Usage Before

```php
// ❌ Static call - hard to test
$result = PathsMapper::map($openApi, $callback);
```

### Usage After

```php
// ✅ Instance usage - easy to test
$mapper = new PathsMapper();
$result = $mapper->map($openApi, $callback);
```

### Testing Before (HARD)

```php
// ❌ Can't mock static methods easily
final class ProcessorTest extends TestCase
{
    public function testProcess(): void
    {
        // Can't mock PathsMapper::map() without complex workarounds
        $processor = new IriReferenceTypeProcessor(...);
        $result = $processor->process($openApi);
    }
}
```

### Testing After (EASY)

```php
// ✅ Can inject mocked mapper
final class ProcessorTest extends TestCase
{
    public function testProcess(): void
    {
        $mapperMock = $this->createMock(PathsMapper::class);
        $mapperMock->method('map')->willReturn($expectedResult);

        $processor = new IriReferenceTypeProcessor(..., $mapperMock);
        $result = $processor->process($openApi);
    }
}
```

---

## Common Refactoring Patterns

### Pattern 1: Moving Classes

```bash
# 1. Identify the correct directory
# Is it a Converter, Transformer, Validator, etc.?

# 2. Move the file
mv src/Old/Location/ClassName.php src/New/Location/ClassName.php

# 3. Update namespace in the file
# Old: namespace App\Old\Location;
# New: namespace App\New\Location;

# 4. Find all usages
grep -r "use.*Old\\Location\\ClassName" src/ tests/

# 5. Update all imports
# Use IDE refactoring or sed

# 6. Move test file too
mv tests/Unit/Old/Location/ClassNameTest.php tests/Unit/New/Location/ClassNameTest.php

# 7. Update test namespace

# 8. Run checks
make phpcsfixer
make psalm
make unit-tests
```

### Pattern 2: Removing Default Instantiation

```php
// Step 1: Identify the problem
// ❌ Before
public function __construct(
    ?SomeDependency $dependency = null
) {
    $this->dependency = $dependency ?? new SomeDependency();
}

// Step 2: Remove default, make required
// ✅ After
public function __construct(
    private SomeDependency $dependency
) {
}

// Step 3: Configure in services.yaml if needed
services:
  App\Some\Class:
    arguments:
      $dependency: '@App\Some\SomeDependency'

// Step 4: Update all instantiations in tests
// Add the required parameter
```

### Pattern 3: Improving Variable Names

```php
// Step 1: Identify vague names
// ❌ Before
private SomeConverter $converter;
private SomeResolver $resolver;
private mixed $data;

// Step 2: Make specific
// ✅ After
private SomeConverter $typeConverter;
private SomeResolver $scalarResolver;
private mixed $customerData;

// Step 3: Update all usages
$this->converter->convert()     // Old
$this->typeConverter->convert()  // New
```

---

## Summary Checklist

When refactoring code organization, ensure:

- [ ] Class is in correct directory for its type
- [ ] Namespace matches directory structure
- [ ] Class name accurately describes functionality
- [ ] Variable names are specific
- [ ] Parameter names match their actual types
- [ ] No default instantiation in constructors
- [ ] Constructor property promotion used
- [ ] All dependencies injected
- [ ] Prefer instance methods over static
- [ ] Tests updated with class
- [ ] All imports updated
- [ ] Quality checks pass (phpcsfixer, psalm, tests)
