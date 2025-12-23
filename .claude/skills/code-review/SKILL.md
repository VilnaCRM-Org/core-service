---
name: code-review
description: Systematically retrieve and address PR code review comments using make pr-comments. Enforces DDD architecture, code organization principles, and quality standards. Use when handling code review feedback, refactoring based on reviewer suggestions, or addressing PR comments.
---

# Code Review Workflow Skill

## Context (Input)

- PR has unresolved code review comments
- Need systematic approach to address feedback
- Ready to implement reviewer suggestions
- Need to verify DDD architecture compliance
- Need to ensure code organization best practices
- Need to maintain quality standards

## Task (Function)

Retrieve PR comments, categorize by type, verify architecture compliance, enforce code organization principles, and implement all changes systematically while maintaining 100% quality standards.

## Execution Steps

### Step 1: Get PR Comments

```bash
make pr-comments              # Auto-detect from current branch
make pr-comments PR=62       # Specify PR number
make pr-comments FORMAT=json  # JSON output
```

**Output**: All unresolved comments with file/line, author, timestamp, URL

### Step 2: Categorize Comments

| Type                   | Identifier                  | Priority | Action                               |
| ---------------------- | --------------------------- | -------- | ------------------------------------ |
| Committable Suggestion | Code block, "```suggestion" | Highest  | Apply immediately, commit separately |
| LLM Prompt             | "🤖 Prompt for AI Agents"   | High     | Execute prompt, implement changes    |
| Architecture Concern   | Class naming, file location | High     | Verify DDD compliance (see Step 2.1) |
| Question               | Ends with "?"               | Medium   | Answer inline or via code change     |
| General Feedback       | Discussion, recommendation  | Low      | Consider and improve                 |

#### Step 2.1: Architecture & Code Organization Verification

For any code changes (suggestions, prompts, or new files), **MANDATORY** verification:

**A. Code Organization Principle** (see `code-organization` skill):

> **Directory X contains ONLY class type X**

Verify class is in the correct directory for its type:

- `Converter/` → ONLY converters (type conversion)
- `Transformer/` → ONLY transformers (data transformation for DB/serialization)
- `Validator/` → ONLY validators (validation logic)
- `Builder/` → ONLY builders (object construction)
- `Fixer/` → ONLY fixers (modify/correct data)
- `Cleaner/` → ONLY cleaners (filter/clean data)
- `Factory/` → ONLY factories (create complex objects)
- `Resolver/` → ONLY resolvers (resolve/determine values)
- `Serializer/` → ONLY serializers/normalizers

**B. Class Naming Compliance** (see `implementing-ddd-architecture` skill):

| Layer              | Class Type         | Naming Pattern                       | Example                           |
| ------------------ | ------------------ | ------------------------------------ | --------------------------------- |
| **Domain**         | Entity             | `{EntityName}.php`                   | `Customer.php`                    |
|                    | Value Object       | `{ConceptName}.php`                  | `Email.php`, `Money.php`          |
|                    | Domain Event       | `{Entity}{PastTenseAction}.php`      | `CustomerCreated.php`             |
|                    | Repository Iface   | `{Entity}RepositoryInterface.php`    | `CustomerRepositoryInterface.php` |
|                    | Exception          | `{SpecificError}Exception.php`       | `InvalidEmailException.php`       |
| **Application**    | Command            | `{Action}{Entity}Command.php`        | `CreateCustomerCommand.php`       |
|                    | Command Handler    | `{Action}{Entity}Handler.php`        | `CreateCustomerHandler.php`       |
|                    | Event Subscriber   | `{Action}On{Event}.php`              | `SendEmailOnCustomerCreated.php`  |
|                    | DTO                | `{Entity}{Type}.php`                 | `CustomerInput.php`               |
|                    | Processor          | `{Action}{Entity}Processor.php`      | `CreateCustomerProcessor.php`     |
|                    | Transformer        | `{From}To{To}Transformer.php`        | `CustomerToArrayTransformer.php`  |
| **Infrastructure** | Repository         | `{Technology}{Entity}Repository.php` | `MongoDBCustomerRepository.php`   |
|                    | Doctrine Type      | `{ConceptName}Type.php`              | `UlidType.php`                    |
|                    | Bus Implementation | `{Framework}{Type}Bus.php`           | `SymfonyCommandBus.php`           |

**Directory Location Compliance**:

```
src/{Context}/
├── Application/
│   ├── Command/          ← Commands
│   ├── CommandHandler/   ← Command Handlers
│   ├── EventSubscriber/  ← Event Subscribers
│   ├── DTO/              ← Data Transfer Objects
│   ├── Processor/        ← API Platform Processors
│   ├── Transformer/      ← Data Transformers
│   └── MutationInput/    ← GraphQL Mutation Inputs
├── Domain/
│   ├── Entity/           ← Entities & Aggregates
│   ├── ValueObject/      ← Value Objects
│   ├── Event/            ← Domain Events
│   ├── Repository/       ← Repository Interfaces
│   └── Exception/        ← Domain Exceptions
└── Infrastructure/
    ├── Repository/       ← Repository Implementations
    ├── DoctrineType/     ← Custom Doctrine Types
    └── Bus/              ← Message Bus Implementations
```

**Verification Questions**:

1. ✅ Is the class following **"Directory X contains ONLY class type X"** principle?
   - Example: `UlidValidator` must be in `Validator/`, NOT in `Transformer/` or `Converter/`
2. ✅ Is the class name following the DDD naming pattern for its type?
3. ✅ Is the class in the correct directory according to its responsibility?
4. ✅ Does the class name reflect what it actually does?
5. ✅ Is the class in the correct layer (Domain/Application/Infrastructure)?
6. ✅ Does Domain layer have NO framework imports (Symfony/Doctrine/API Platform)?
7. ✅ Are variable names specific (not vague)?
   - ✅ `$typeConverter`, `$scalarResolver` (specific)
   - ❌ `$converter`, `$resolver` (too vague)
8. ✅ Are parameter names accurate (match actual types)?
   - ✅ `mixed $value` when accepts any type
   - ❌ `string $binary` when accepts mixed

**C. Namespace Consistency**:

Namespace **MUST** match directory structure exactly:

```php
✅ CORRECT:
// File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Validator;

❌ WRONG:
// File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Transformer;  // Mismatch!
```

**D. PHP Best Practices**:

- ✅ Use constructor property promotion
- ✅ Inject ALL dependencies (no default instantiation)
- ✅ Use `readonly` when appropriate
- ✅ Use `final` for classes that shouldn't be extended
- ❌ NO "Helper" or "Util" classes (code smell - extract specific responsibilities)

**E. Classes Over Arrays (Type Safety)**:

> **Prefer typed classes and collections over arrays for structured data**

Arrays lack type safety and self-documentation. Use concrete classes instead:

| Pattern | Bad (Array) | Good (Class) |
|---------|-------------|--------------|
| Configuration | `['endpoint' => 'X', 'operation' => 'Y']` | `new EndpointOperationDimensions('X', 'Y')` |
| Return data | `return ['name' => $n, 'value' => $v]` | `return new MetricData($n, $v)` |
| Method params | `function emit(array $metrics)` | `function emit(MetricCollection $metrics)` |
| Events data | `['type' => 'created', 'id' => $id]` | `new CustomerCreatedEvent($id)` |

**Benefits of typed classes**:

- ✅ IDE autocompletion and refactoring support
- ✅ Static analysis catches type errors
- ✅ Self-documenting code (class name describes purpose)
- ✅ Encapsulation (validation in constructor)
- ✅ Single Responsibility (each class has clear purpose)
- ✅ Open/Closed (extend via new classes, not array key changes)

**Collection Pattern**:

```php
// ❌ BAD: Array of arrays
$metrics = [
    ['name' => 'OrdersPlaced', 'value' => 1],
    ['name' => 'OrderValue', 'value' => 99.99],
];

// ✅ GOOD: Typed collection of objects
$metrics = new MetricCollection(
    new OrdersPlacedMetric(value: 1),
    new OrderValueMetric(value: 99.99)
);
```

**When arrays ARE acceptable**:

- Simple key-value maps for serialization output (`toArray()` methods)
- Framework integration points requiring arrays
- Temporary internal data within a single method

**Action on Violations**:

1. **Class in Wrong Directory**:

   ```bash
   # Move file to correct directory
   mv src/Path/WrongDir/ClassName.php src/Path/CorrectDir/ClassName.php

   # Update namespace in file
   # Update all imports across codebase
   grep -r "use.*WrongDir\\ClassName" src/ tests/
   ```

2. **Wrong Class Name**:

   - Rename class to follow naming conventions
   - Update all references to renamed class
   - Ensure name reflects actual functionality

3. **Vague Variable/Parameter Names**:

   ```php
   ❌ BEFORE: private UlidTypeConverter $converter;
   ✅ AFTER:  private UlidTypeConverter $typeConverter;

   ❌ BEFORE: private CustomerUpdateScalarResolver $resolver;
   ✅ AFTER:  private CustomerUpdateScalarResolver $scalarResolver;
   ```

4. **Quality Verification**:
   ```bash
   make phpcsfixer    # Fix code style
   make psalm         # Static analysis
   make deptrac       # Verify no layer violations
   make unit-tests    # Run tests
   ```

### Step 3: Apply Changes Systematically

#### For Committable Suggestions

1. Apply code change exactly as suggested
2. Commit with reference:

   ```bash
   git commit -m "Apply review suggestion: [brief description]

   Ref: [comment URL]"
   ```

#### For LLM Prompts

1. Copy prompt from comment
2. Execute as instructed
3. Verify output meets requirements
4. Commit with reference

#### For Questions

1. Determine if code change or reply needed
2. If code: implement + commit
3. If reply: respond on GitHub

#### For Feedback

1. Evaluate suggestion merit
2. Implement if beneficial
3. Document reasoning if declined

### Step 4: Verify All Addressed

```bash
make pr-comments  # Should show zero unresolved comments
```

### Step 5: Run Quality Checks

**MANDATORY**: Run comprehensive CI checks after implementing all changes:

```bash
make ci  # Must output "✅ CI checks successfully passed!"
```

**If CI fails**, address issues systematically:

1. **Code Style Issues**: `make phpcsfixer`
2. **Static Analysis Errors**: `make psalm`
3. **Architecture Violations**: `make deptrac`
4. **Test Failures**: `make unit-tests` / `make integration-tests`
5. **Mutation Testing**: `make infection` (must maintain 100% MSI)
6. **Complexity Issues**:
   - Run `make phpmd` first to identify specific hotspots
   - Refactor complex methods (keep complexity < 5 per method)
   - Re-run `make phpinsights`

**Quality Standards Protection** (see `quality-standards` skill):

- **PHPInsights**: 100% quality, 93% src / 95% tests complexity, 100% architecture, 100% style
- **Test Coverage**: 100% (no decrease allowed)
- **Mutation Testing**: 100% MSI, 0 escaped mutants
- **Cyclomatic Complexity**: < 5 per class/method

**DO NOT** finish the task until `make ci` shows: `✅ CI checks successfully passed!`

## Comment Resolution Workflow

```mermaid
PR Comments → Categorize → Apply by Priority → Verify → Run CI → Done
```

## Constraints (Parameters)

**NEVER**:

- Skip committable suggestions
- Batch unrelated changes in one commit
- Ignore LLM prompts from reviewers
- Commit without running `make ci`
- Leave questions unanswered
- Accept class names that don't follow DDD naming patterns
- Place files in wrong directories (violates layer architecture)
- Allow Domain layer to import framework code (Symfony/Doctrine/API Platform)
- Put class in wrong type directory (e.g., Validator in Transformer/)
- Use vague variable names like `$converter`, `$resolver` (be specific!)
- Create "Helper" or "Util" classes (extract specific responsibilities)
- Allow namespace to mismatch directory structure
- Decrease quality thresholds (PHPInsights, test coverage, mutation score)
- Allow cyclomatic complexity > 5 per method
- Finish task before `make ci` shows success message
- Use arrays for structured data when typed classes would be more appropriate
- Inject cross-cutting concerns (metrics, logging) directly into command handlers - use event subscribers instead

**ALWAYS**:

- Apply suggestions exactly as provided
- Commit each suggestion separately with URL reference
- Verify **"Directory X contains ONLY class type X"** principle
- Verify architecture compliance for any new/modified classes
- Check class naming follows DDD patterns (see Step 2.1)
- Verify files are in correct directories according to layer AND type
- Ensure namespace matches directory structure exactly
- Use specific variable names (`$typeConverter`, not `$converter`)
- Use accurate parameter names (match actual types)
- Run `make deptrac` to ensure no layer violations
- Run `make ci` after implementing changes
- Address ALL quality standard violations before finishing
- Maintain 100% test coverage and 100% MSI (0 escaped mutants)
- Keep cyclomatic complexity < 5 per method
- Mark conversations resolved after addressing
- Prefer typed classes over arrays for structured data (configuration, DTOs, events)
- Use collections (`MetricCollection`, `EntityCollection`) instead of arrays of objects
- Add new features via new classes following Open/Closed principle (don't modify existing classes)

## Format (Output)

**Commit Message Template**:

```
Apply review suggestion: [concise description]

[Optional: explanation if non-obvious]

Ref: https://github.com/owner/repo/pull/XX#discussion_rYYYYYYY
```

**Final Verification**:

```bash
✅ make pr-comments shows 0 unresolved
✅ make ci shows "CI checks successfully passed!"
```

## Verification Checklist

- [ ] All PR comments retrieved via `make pr-comments`
- [ ] Comments categorized by type (suggestion/prompt/question/feedback)
- [ ] **Code Organization verified for all changes**:
  - [ ] **"Directory X contains ONLY class type X"** principle enforced
  - [ ] Converters in `Converter/`, Transformers in `Transformer/`, etc.
  - [ ] Class type matches directory (no mismatches)
- [ ] **Architecture & DDD compliance verified**:
  - [ ] Class names follow DDD naming patterns
  - [ ] Files in correct directories according to layer
  - [ ] Class names reflect what they actually do
  - [ ] Domain layer has NO framework imports
  - [ ] `make deptrac` passes (0 violations)
- [ ] **Naming conventions enforced**:
  - [ ] Variable names are specific (`$typeConverter`, not `$converter`)
  - [ ] Parameter names match actual types
  - [ ] Namespace matches directory structure
  - [ ] No "Helper" or "Util" classes
- [ ] **PHP best practices applied**:
  - [ ] Constructor property promotion used
  - [ ] All dependencies injected (no default instantiation)
  - [ ] `readonly` and `final` used appropriately
- [ ] **Type safety & SOLID principles enforced**:
  - [ ] Typed classes used instead of arrays for structured data
  - [ ] Collections used instead of arrays of objects
  - [ ] New features added via new classes (Open/Closed principle)
  - [ ] Cross-cutting concerns (metrics) in event subscribers, not handlers
- [ ] Committable suggestions applied and committed separately
- [ ] LLM prompts executed and implemented
- [ ] Questions answered (code or reply)
- [ ] General feedback evaluated and addressed
- [ ] **Quality standards maintained**:
  - [ ] Test coverage remains 100%
  - [ ] Mutation testing: 100% MSI (0 escaped mutants)
  - [ ] PHPInsights: 100% quality, 93% src / 95% tests complexity, 100% architecture, 100% style
  - [ ] Cyclomatic complexity < 5 per method
  - [ ] `make ci` shows "✅ CI checks successfully passed!"
- [ ] `make pr-comments` shows zero unresolved
- [ ] All conversations marked resolved on GitHub

## Common Code Organization Issues in Reviews

### Issue 1: Class in Wrong Type Directory

**Scenario**: `UlidValidator` placed in `Transformer/` directory

```bash
❌ WRONG:
src/Shared/Infrastructure/Transformer/UlidValidator.php

✅ CORRECT:
src/Shared/Infrastructure/Validator/UlidValidator.php
```

**Fix**:

```bash
mv src/Shared/Infrastructure/Transformer/UlidValidator.php \
   src/Shared/Infrastructure/Validator/UlidValidator.php
# Update namespace and all imports
```

### Issue 2: Vague Variable Names

**Scenario**: Generic variable names in constructor

```php
❌ WRONG:
public function __construct(
    private UlidTypeConverter $converter,  // Converter of what?
) {}

✅ CORRECT:
public function __construct(
    private UlidTypeConverter $typeConverter,  // Specific!
) {}
```

### Issue 3: Misleading Parameter Names

**Scenario**: Parameter name doesn't match actual type

```php
❌ WRONG:
public function fromBinary(mixed $binary): Ulid  // Accepts mixed, not just binary

✅ CORRECT:
public function fromBinary(mixed $value): Ulid  // Accurate!
```

### Issue 4: Helper/Util Classes

**Scenario**: Code review flags `CustomerHelper` class

```php
❌ WRONG:
class CustomerHelper {
    public function validateEmail() {}
    public function formatName() {}
    public function convertData() {}
}

✅ CORRECT: Extract specific responsibilities
- CustomerEmailValidator (Validator/)
- CustomerNameFormatter (Formatter/)
- CustomerDataConverter (Converter/)
```

### Issue 5: Arrays Instead of Typed Classes

**Scenario**: Method returns/accepts arrays for structured data

```php
❌ WRONG: Array for configuration/data
public function emit(array $metrics): void
{
    foreach ($metrics as $metric) {
        $name = $metric['name'];     // No type safety
        $value = $metric['value'];   // No IDE support
    }
}

✅ CORRECT: Typed collection of objects
public function emit(MetricCollection $metrics): void
{
    foreach ($metrics as $metric) {
        $name = $metric->name();     // Type-safe
        $value = $metric->value();   // IDE autocomplete
    }
}
```

### Issue 6: Cross-Cutting Concerns in Handlers

**Scenario**: Metrics/logging injected directly into command handlers

```php
❌ WRONG: Metrics in command handler
final class CreateCustomerHandler
{
    public function __construct(
        private CustomerRepository $repository,
        private BusinessMetricsEmitterInterface $metrics  // Wrong place!
    ) {}

    public function __invoke(CreateCustomerCommand $cmd): void
    {
        $customer = Customer::create(...);
        $this->repository->save($customer);
        $this->metrics->emit(new CustomersCreatedMetric());  // Violates SRP
    }
}

✅ CORRECT: Metrics in dedicated event subscriber
final class CreateCustomerHandler
{
    public function __construct(
        private CustomerRepository $repository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreateCustomerCommand $cmd): void
    {
        $customer = Customer::create(...);
        $this->repository->save($customer);
        $this->eventBus->publish(...$customer->pullDomainEvents());
        // Metrics subscriber handles emission
    }
}

final class CustomerCreatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(CustomerCreatedEvent $event): void
    {
        $this->metricsEmitter->emit(new CustomersCreatedMetric());
    }
}
```

## Related Skills

- **quality-standards**: Maintains 100% code quality metrics
- **code-organization**: Enforces "Directory X contains ONLY class type X"
- **implementing-ddd-architecture**: DDD patterns and structure
- **ci-workflow**: Comprehensive quality checks
- **testing-workflow**: Test coverage and mutation testing
