---
name: testing-workflow
description: Run and manage different types of tests (unit, integration, E2E, mutation, load tests). Use when running tests, debugging test failures, ensuring test coverage, or fixing mutation testing issues. Covers PHPUnit, Behat, Infection, and K6 load tests.
---

# Testing Workflow Skill

This skill provides comprehensive guidance for running and managing tests in the project.

## When to Use This Skill

Activate this skill when:

- User asks to run tests
- Debugging test failures
- Checking test coverage
- Running specific test suites
- After implementing new features
- Validating bug fixes

## Test Types and Commands

### Unit Tests

**Purpose**: Test individual classes/methods in isolation with mocked dependencies

```bash
make unit-tests
```

**Runtime**: 2-3 minutes
**Location**: `tests/Unit/`
**Coverage Required**: 100%

### Integration Tests

**Purpose**: Test interactions between components with real database and services

```bash
make integration-tests
```

**Runtime**: 3-5 minutes
**Location**: `tests/Integration/`
**Coverage Required**: Comprehensive

### End-to-End Tests (Behat)

**Purpose**: BDD scenarios testing complete user journeys

```bash
make behat
# or
make e2e-tests
```

**Runtime**: 5-10 minutes
**Location**: `features/`
**Contexts**: Defined in `behat.yml.dist`

### All Tests

**Purpose**: Run complete test suite

```bash
make all-tests
```

**Runtime**: 8-15 minutes
**CRITICAL**: NEVER CANCEL - Wait for completion

### Mutation Testing (Infection)

**Purpose**: Validate test quality by making code mutations

```bash
make infection
```

**Runtime**: Variable (can be long)
**Target**: 100% MSI (0 escaped mutants)

### Load Testing

**Purpose**: Validate performance under various load conditions

```bash
make smoke-load-tests    # 5-10 min, minimal load
make average-load-tests  # 15-25 min, normal patterns
make stress-load-tests   # 20-30 min, high load
make spike-load-tests    # 25-35 min, extreme spikes
make load-tests          # All load tests
```

**Requirements**: Test database setup, 30-min timeouts

## Test Coverage Workflow

### Check Coverage

```bash
make tests-with-coverage
```

**Runtime**: 10-15 minutes
**Output**: Coverage report

### Generate HTML Coverage Report

```bash
make coverage-html
```

Opens detailed coverage report in browser.

## Debugging Test Failures

### Step 1: Identify Failure Type

**Unit Test Failure**:

- Check test output for assertion failures
- Review mocked dependencies
- Verify test data setup

**Integration Test Failure**:

- Check database state
- Verify service connections
- Review environment variables

**Behat Test Failure**:

- Check feature file scenarios
- Review context implementations
- Verify API responses match expectations

### Step 2: Run Specific Test

**Run single test file**:

```bash
docker compose exec -e APP_ENV=test php vendor/bin/phpunit tests/Unit/Specific/TestFile.php
```

**Run specific test method**:

```bash
docker compose exec -e APP_ENV=test php vendor/bin/phpunit --filter testMethodName
```

**Run specific Behat scenario**:

```bash
docker compose exec -e APP_ENV=test php vendor/bin/behat features/specific.feature:10
```

### Step 3: Fix and Verify

1. Fix the failing test or code
2. Re-run the specific test
3. Run full suite to ensure no regressions
4. Check coverage maintained at 100%

## Mutation Testing Strategy

### Understanding Escaped Mutants

Infection makes small code changes (mutations) and checks if tests catch them.

**Common mutation types**:

- Boundary conditions (`>` → `>=`, `<` → `<=`)
- Return values (`true` → `false`)
- Operators (`+` → `-`, `&&` → `||`)
- Default parameter values

### Fixing Escaped Mutants

1. **Run Infection**:

   ```bash
   make infection
   ```

2. **Review mutation diff** in output

3. **Add targeted tests** for edge cases:

   ```php
   public function testBoundaryCondition(): void
   {
       // Test exact boundary
       $this->assertTrue($validator->validate(8));  // Min length
       $this->assertTrue($validator->validate(64)); // Max length
       $this->assertFalse($validator->validate(7)); // Below min
       $this->assertFalse($validator->validate(65)); // Above max
   }
   ```

4. **If tests can't catch mutants**, refactor for testability:
   - Extract complex conditions into methods
   - Make default parameters injectable
   - Use dependency injection for DateTime
   - Avoid static method calls

### Target: 100% MSI

- All mutants must be killed
- Zero escaped mutants
- Zero uncovered mutants

## Test Database Management

### Setup Test Database

```bash
make setup-test-db
```

**Purpose**: Drop and recreate test MongoDB schema
**When to use**:

- Before running integration/E2E tests
- After schema changes
- When tests fail due to database state

## Best Practices

### Use Faker for Test Data

**MANDATORY**: Never hardcode test values

```php
// ❌ BAD
$email = 'test@example.com';

// ✅ GOOD
$email = $this->faker->unique()->email();
```

### Maintain 100% Coverage

- Write tests for all new code
- Cover all branches and edge cases
- Test error conditions
- Test boundary values

### Keep Tests Fast

- Mock external dependencies in unit tests
- Use test database for integration tests
- Avoid unnecessary setup/teardown
- Run specific tests during development

### Write Clear Test Names

```php
// ✅ GOOD: Descriptive test names
public function testUserRegistrationFailsWhenEmailAlreadyExists(): void
public function testPasswordValidationRequiresMinimumLength(): void
public function testTokenExpiresAfterOneHour(): void
```

## Success Criteria

- All test suites pass
- 100% code coverage maintained
- 100% mutation score (0 escaped mutants)
- No test database errors
- Load tests meet performance thresholds
