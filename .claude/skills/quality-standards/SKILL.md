---
name: Quality Standards
description: Maintain and improve code quality standards, reduce cyclomatic complexity, fix PHPInsights issues, and ensure architectural compliance. Use when quality checks fail or when refactoring for better code quality.
---

# Quality Standards Skill

This skill guides maintaining and improving code quality standards without ever decreasing current quality levels.

## When to Use This Skill

Activate this skill when:

- PHPInsights reports quality issues
- Cyclomatic complexity is too high
- Deptrac reports architecture violations
- Code quality needs improvement
- Refactoring for better maintainability

## Protected Quality Metrics

**NEVER decrease these thresholds**:

### PHPInsights (Source Code)

- **min-quality**: 100%
- **min-complexity**: 95%
- **min-architecture**: 100%
- **min-style**: 100%

### PHPInsights (Tests)

- **min-quality**: 95%
- **min-complexity**: 95%
- **min-architecture**: 90%
- **min-style**: 95%

### Test Coverage

- **Unit test coverage**: 100%
- **Mutation testing (Infection) MSI**: 100%

## Quality Check Commands

### Run PHPInsights

```bash
make phpinsights
```

### Run PHP Mess Detector (for complexity issues)

```bash
make phpmd
```

### Run Psalm Static Analysis

```bash
make psalm
make psalm-security  # Security taint analysis
```

### Run Deptrac Architecture Validation

```bash
make deptrac
```

### Run PHP CS Fixer

```bash
make phpcsfixer
```

## Resolving PHPInsights Complexity Failures

### When PHPInsights Reports Low Complexity Score

**Problem**: `[ERROR] The complexity score is too low` without specific files

**Solution**:

1. **Run PHP Mess Detector first** to find hotspots:

   ```bash
   make phpmd
   ```

2. **Review PHPMD output** for cyclomatic complexity warnings

3. **Address each high-complexity finding**

4. **Re-run PHPInsights**:
   ```bash
   make phpinsights
   ```

## Reducing Cyclomatic Complexity

**Target**: Keep complexity below 5 per method

### Strategy 1: Extract Methods

**Before** (complexity: 8):

```php
public function validate($value, Constraint $constraint): void
{
    if ($value === null || ($constraint->isOptional() && $value === '')) {
        return;
    }
    if (!(strlen($value) >= 8 && strlen($value) <= 64)) {
        $this->addViolation('password.invalid.length');
    }
    if (!preg_match('/[A-Z]/', $value)) {
        $this->addViolation('password.missing.uppercase');
    }
    if (!preg_match('/[0-9]/', $value)) {
        $this->addViolation('password.missing.digit');
    }
}
```

**After** (complexity: 2):

```php
public function validate($value, Constraint $constraint): void
{
    if ($this->shouldSkipValidation($value, $constraint)) {
        return;
    }

    $this->performValidations($value);
}

private function shouldSkipValidation($value, Constraint $constraint): bool
{
    return $value === null || ($constraint->isOptional() && $value === '');
}

private function performValidations($value): void
{
    $this->validateLength($value);
    $this->validateUppercase($value);
    $this->validateDigit($value);
}
```

### Strategy 2: Use Strategy Pattern

**Before** (complexity: 10):

```php
public function process($data, $type): array
{
    if ($type === 'json') {
        // JSON processing logic (3 conditions)
    } elseif ($type === 'xml') {
        // XML processing logic (3 conditions)
    } elseif ($type === 'csv') {
        // CSV processing logic (3 conditions)
    }
    return $result;
}
```

**After** (complexity: 2):

```php
public function process($data, $type): array
{
    $strategy = $this->strategyFactory->create($type);
    return $strategy->process($data);
}
```

### Strategy 3: Early Returns

**Before** (complexity: 6):

```php
public function calculate($value): int
{
    if ($value !== null) {
        if ($value > 0) {
            if ($value < 100) {
                return $value * 2;
            }
        }
    }
    return 0;
}
```

**After** (complexity: 3):

```php
public function calculate($value): int
{
    if ($value === null) {
        return 0;
    }

    if ($value <= 0 || $value >= 100) {
        return 0;
    }

    return $value * 2;
}
```

## Fixing Architecture Violations

### Understanding Deptrac Layers

**Dependency Rules**:

- **Domain** layer: NO dependencies on other layers
- **Application** layer: Can depend on Domain and Infrastructure
- **Infrastructure** layer: Can depend on Domain and Application

### Common Violations

**Problem**: Domain depends on Infrastructure

```php
// ❌ BAD: Domain using Doctrine annotation
namespace App\User\Domain\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class User { }
```

**Solution**: Move annotations to XML mapping

```xml
<!-- config/doctrine/User.orm.xml -->
<doctrine-mongo-mapping>
    <document name="App\User\Domain\Entity\User">
        <!-- mappings here -->
    </document>
</doctrine-mongo-mapping>
```

**Problem**: Using wrong layer's interfaces
**Solution**: Move interface to correct layer or create proper abstraction

## Improving Code Quality Score

### Remove Code Duplication

Use PHP CS Fixer to identify and extract common patterns:

```php
// ❌ BAD: Duplicated logic
public function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidEmailException();
    }
}

public function checkEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidEmailException();
    }
}

// ✅ GOOD: Extracted common logic
private function isValidEmail($email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
```

### Improve Method Naming

```php
// ❌ BAD: Unclear method name
public function process($data): void { }

// ✅ GOOD: Self-explanatory method name
public function hashPasswordUsingBcrypt(string $plainPassword): string { }
```

### Remove Inline Comments

**MANDATORY**: Write self-explanatory code instead of comments

```php
// ❌ BAD: Inline comment explaining code
if ($value === '') {
    return false; // empty is not "only spaces"
}

// ✅ GOOD: Self-explanatory method name
if ($this->isEmptyButNotOnlySpaces($value)) {
    return false;
}
```

## Quality Improvement Workflow

### 1. Identify Issues

Run all quality checks:

```bash
make phpinsights
make phpmd
make psalm
make deptrac
```

### 2. Prioritize Fixes

**Priority Order**:

1. Architecture violations (Deptrac)
2. Security issues (Psalm security)
3. High complexity methods (PHPMD)
4. Type safety issues (Psalm)
5. Code style (PHP CS Fixer)

### 3. Apply Fixes

For each issue:

1. Understand the root cause
2. Apply appropriate refactoring strategy
3. Run specific quality check
4. Verify improvement

### 4. Verify Overall Quality

Run comprehensive checks:

```bash
make ci
```

Must output: "✅ CI checks successfully passed!"

## Best Practices

### Single Responsibility Principle

Each class/method should have one clear purpose:

```php
// ❌ BAD: Multiple responsibilities
class UserService {
    public function createUser() { }
    public function sendEmail() { }
    public function logActivity() { }
}

// ✅ GOOD: Single responsibility
class UserRegistrationService {
    public function register(User $user): void { }
}

class EmailService {
    public function send(Email $email): void { }
}
```

### Dependency Inversion

Depend on abstractions, not concretions:

```php
// ❌ BAD: Depends on concrete class
class UserService {
    private MySQLUserRepository $repository;
}

// ✅ GOOD: Depends on interface
class UserService {
    private UserRepositoryInterface $repository;
}
```

### Keep Methods Small

Target: Under 20 lines per method

### Use Type Hints

```php
// ✅ GOOD: Explicit types
public function processUser(User $user): ProcessedUser
{
    // ...
}
```

## Success Criteria

- PHPInsights: All scores meet or exceed thresholds
- PHPMD: No high complexity warnings
- Psalm: Zero errors
- Deptrac: Zero architecture violations
- PHP CS Fixer: Clean code style
- CI: "✅ CI checks successfully passed!"

## Remember

**NEVER** modify quality thresholds downward in:

- `phpinsights.php`
- `phpinsights-tests.php`
- `infection.json5`
- `phpunit.xml.dist`

**ALWAYS** fix the code, never lower the standards.
