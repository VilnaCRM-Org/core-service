# PHP Best Practices for Code Organization

This guide covers PHP-specific best practices for code organization, dependency injection, and testability.

## Constructor Property Promotion

Always use PHP 8.0+ constructor property promotion for cleaner code:

✅ **CORRECT (Constructor Promotion)**:

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

❌ **WRONG (Old Style)**:

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

**Benefits**:

- Less boilerplate code
- Clearer dependency declaration
- Easier to maintain
- Better readability

## Dependency Injection - No Default Instantiation

**NEVER** instantiate dependencies with default values in constructors. Always inject them.

✅ **CORRECT (Pure DI)**:

```php
final class IriReferenceContentTransformer
{
    public function __construct(
        private readonly IriReferenceMediaTypeTransformerInterface $mediaTypeTransformer
    ) {
    }
}
```

❌ **WRONG (Default Instantiation)**:

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

## Factory Pattern - No `new` in src/

In `src/`, always use factories instead of the `new` keyword (except for Value Objects, Exceptions, and Framework objects).

✅ **CORRECT (Using Factory)**:

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

❌ **WRONG (Direct instantiation)**:

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

### When `new` is acceptable in src/

1. **Value Objects**:

   ```php
   return new CustomerUpdate($data);  // ✅ OK
   return new Ulid($value);           // ✅ OK
   ```

2. **Exceptions**:

   ```php
   throw new CustomerNotFoundException($id);  // ✅ OK
   throw new ValidationException($message);   // ✅ OK
   ```

3. **Framework Objects**:

   ```php
   return new Response($content);     // ✅ OK
   return new ArrayObject($data);     // ✅ OK
   ```

4. **Library Objects (OpenAPI, etc)**:
   ```php
   return new OpenApi($info, $servers, $paths);  // ✅ OK
   return new PathItem();                        // ✅ OK
   ```

## Testability Best Practices

### 1. Always Inject All Dependencies

✅ **CORRECT (All dependencies injected)**:

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

❌ **WRONG (Hidden dependencies)**:

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

### 2. Use Interfaces for Dependencies

✅ **CORRECT (Interface injection)**:

```php
public function __construct(
    private IriReferenceContentTransformerInterface $contentTransformer,  // ✅ Interface
    private IriReferenceOperationContextResolverInterface $contextResolver  // ✅ Interface
) {
}
```

❌ **LESS FLEXIBLE (Concrete class)**:

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

### 3. Test Files Can Use `new` Freely

In test files (`tests/`), using `new` is perfectly acceptable:

✅ **CORRECT (Tests can use `new`)**:

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

## Static Methods - When to Avoid

### Prefer Instance Methods Over Static

❌ **AVOID (Static methods)**:

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

✅ **PREFER (Instance methods)**:

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

## Real-World Refactoring Examples

### Example 1: Moving Resolver Classes

**Before (WRONG)**:

```php
// ❌ src/Core/Customer/Application/Factory/CustomerUpdateScalarResolver.php
namespace App\Core\Customer\Application\Factory;

final class CustomerUpdateScalarResolver  // It's a RESOLVER, not a FACTORY!
{
    public function resolveScalarValue(...) { }
}
```

**After (CORRECT)**:

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

**Before (WRONG)**:

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

**After (CORRECT)**:

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

**Before (WRONG)**:

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

**After (CORRECT)**:

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
