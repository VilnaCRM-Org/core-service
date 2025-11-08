# Code Complexity Refactoring Task

## Objective
Systematically refactor the most complex classes in the codebase to reduce cyclomatic complexity and improve code maintainability while maintaining 100% test coverage and mutation testing standards.

## Background
This repository has strict quality standards enforced by PHPInsights:
- **Cyclomatic complexity must be below 5 per method**
- **100% test coverage requirement**
- **100% MSI (Mutation Score Indicator) - 0 escaped mutants**
- All complexity issues must be resolved without decreasing quality thresholds

## Analysis Tool Available

The repository includes a complexity analysis tool based on **PHPMetrics** - a professional static analysis tool that provides accurate cyclomatic complexity calculations and additional metrics.

### Usage Commands
```bash
# Analyze top 20 most complex classes (default)
make analyze-complexity

# Analyze top N classes
make analyze-complexity N=10

# Export to JSON for programmatic analysis
make analyze-complexity-json N=20

# Export to CSV for spreadsheet analysis
make analyze-complexity-csv N=20
```

### Script Output
The script provides PHPMetrics-based metrics for each class:
- **Class name** - Full namespace and class name
- **CCN (Cyclomatic Complexity Number)** - Total decision points in the class
- **WMC (Weighted Method Count)** - Sum of all method complexities
- **Method count** - Number of methods in the class
- **LLOC (Logical Lines of Code)** - Actual executable lines
- **Average complexity** - CCN ÷ Methods (target: < 5)
- **Max method complexity** - Highest complexity of any single method
- **Maintainability Index** - 0-100 score (> 65 is good)

## Refactoring Workflow

### Step 1: Identify High-Complexity Classes
```bash
# Run complexity analysis to get top 20 classes
make analyze-complexity N=20
```

Focus on classes with:
- Average complexity > 5 per method
- Total complexity significantly higher than method count
- Multiple methods with complex logic

### Step 2: Analyze Each Complex Class

For each identified class:

1. **Read the class implementation**
   - Understand the business logic and purpose
   - Identify complex methods (conditional logic, nested loops, multiple boolean operators)
   - Check dependencies and architectural context

2. **Review existing tests**
   - Ensure comprehensive test coverage exists
   - Understand tested scenarios and edge cases
   - Identify gaps in test coverage

3. **Identify refactoring opportunities**
   - Extract complex conditional logic into strategy classes
   - Break down large methods into smaller, focused methods
   - Replace nested conditionals with guard clauses
   - Extract validation logic into separate validator classes
   - Use composition over complex inheritance

### Step 3: Refactor with Quality Assurance

For each refactoring:

1. **Make targeted changes**
   - Apply one refactoring pattern at a time
   - Keep changes minimal and focused
   - Maintain backward compatibility

2. **Update tests**
   - Ensure existing tests still pass
   - Add tests for extracted methods/classes
   - Maintain 100% coverage

3. **Verify quality**
   ```bash
   # Run quality checks after each refactoring
   make phpcsfixer        # Fix code style
   make psalm             # Static analysis
   make unit-tests        # Verify tests pass
   make infection         # Check mutation testing
   ```

4. **Commit changes**
   ```bash
   git add .
   git commit -m "Refactor: reduce complexity in ClassName by extracting strategy pattern"
   ```

### Step 4: Run Comprehensive CI

After refactoring multiple classes:

```bash
# Run full CI suite
make ci
```

**CRITICAL**: The output MUST show "✅ CI checks successfully passed!" at the end. If not, address failures and rerun.

## Common Refactoring Patterns

### Pattern 1: Extract Method
**Before:**
```php
public function validate($value): bool
{
    if (!$value || strlen($value) < 8 || strlen($value) > 64 || 
        !preg_match('/[A-Z]/', $value) || !preg_match('/[0-9]/', $value)) {
        return false;
    }
    return true;
}
```

**After:**
```php
public function validate($value): bool
{
    if (!$this->hasValidLength($value)) {
        return false;
    }
    if (!$this->hasUppercase($value)) {
        return false;
    }
    if (!$this->hasDigit($value)) {
        return false;
    }
    return true;
}

private function hasValidLength(?string $value): bool
{
    return $value && strlen($value) >= 8 && strlen($value) <= 64;
}

private function hasUppercase(string $value): bool
{
    return preg_match('/[A-Z]/', $value) === 1;
}

private function hasDigit(string $value): bool
{
    return preg_match('/[0-9]/', $value) === 1;
}
```

### Pattern 2: Strategy Pattern for Complex Conditionals
**Before:**
```php
public function process($type, $data)
{
    if ($type === 'email') {
        // 15 lines of email logic
    } elseif ($type === 'phone') {
        // 15 lines of phone logic
    } elseif ($type === 'address') {
        // 15 lines of address logic
    }
}
```

**After:**
```php
public function process($type, $data)
{
    $strategy = $this->strategyFactory->create($type);
    return $strategy->process($data);
}

// Separate strategy classes
class EmailProcessingStrategy { /* ... */ }
class PhoneProcessingStrategy { /* ... */ }
class AddressProcessingStrategy { /* ... */ }
```

### Pattern 3: Guard Clauses
**Before:**
```php
public function calculate($value)
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

**After:**
```php
public function calculate($value)
{
    if ($value === null) {
        return 0;
    }
    if ($value <= 0) {
        return 0;
    }
    if ($value >= 100) {
        return 0;
    }
    return $value * 2;
}
```

### Pattern 4: Extract Validation Classes
**Before:**
```php
class CustomerService
{
    public function createCustomer($data)
    {
        // 20 lines of validation logic
        // 10 lines of business logic
    }
}
```

**After:**
```php
class CustomerService
{
    public function createCustomer($data)
    {
        $this->validator->validate($data);
        // 10 lines of business logic
    }
}

class CustomerValidator
{
    // 20 lines of validation logic in focused methods
}
```

## Quality Standards to Maintain

### PHPInsights Requirements
- **min-quality**: 100%
- **min-complexity**: 95%
- **min-architecture**: 100%
- **min-style**: 100%

### Test Coverage Requirements
- **Unit test coverage**: 100%
- **Mutation testing**: 100% MSI (0 escaped mutants)

### Architecture Boundaries
- Respect hexagonal architecture layers
- Follow DDD bounded contexts
- Maintain CQRS separation
- Don't introduce cross-context dependencies

## Prohibited Actions

❌ **DO NOT**:
- Decrease quality thresholds in configuration files
- Skip or suppress failing tests
- Remove test coverage
- Ignore mutation testing failures
- Add inline comments to explain complex code (refactor instead)
- Violate architectural boundaries
- Break backward compatibility without justification

✅ **DO**:
- Extract methods to reduce complexity
- Create strategy classes for complex logic
- Use composition over inheritance
- Write self-documenting code with clear naming
- Maintain comprehensive test coverage
- Keep commits focused and incremental
- Document architectural decisions when needed

## Success Criteria

A refactoring is complete when:

1. ✅ Average complexity per method is below 5
2. ✅ All unit tests pass with 100% coverage
3. ✅ Mutation testing shows 100% MSI (0 escaped mutants)
4. ✅ `make ci` outputs "✅ CI checks successfully passed!"
5. ✅ Code is self-explanatory without inline comments
6. ✅ Architectural boundaries are respected
7. ✅ Changes are committed with clear commit messages

## Iteration Strategy

1. Start with the **highest complexity classes** from analysis
2. Refactor **one class at a time**
3. **Verify quality** after each class refactoring
4. **Commit incrementally** with descriptive messages
5. Move to next class only after current one passes all checks
6. After refactoring 5-10 classes, run full `make ci` to ensure no regressions

## Example Workflow

```bash
# 1. Identify targets
make analyze-complexity N=20

# 2. For each complex class:
#    - Read implementation and tests
#    - Plan refactoring approach
#    - Apply refactoring pattern
#    - Update/add tests

# 3. Verify after each refactoring
make phpcsfixer
make psalm  
make unit-tests
make infection

# 4. Commit
git add .
git commit -m "Refactor: reduce complexity in CustomerValidator using extract method pattern"

# 5. After multiple refactorings
make ci

# 6. Re-analyze to track progress
make analyze-complexity N=20
```

## Notes

- The complexity analysis script uses cyclomatic complexity calculation
- Focus on methods with multiple decision points (if/else, loops, boolean operators)
- Prioritize business logic classes over infrastructure/framework code
- Maintain test-first approach: ensure tests exist before refactoring
- Use the existing test infrastructure (Faker, base test classes) when adding tests

## Getting Help

- Review `AGENTS.md` for repository guidelines and quality standards
- Check `.claude/skills/quality-standards.md` for detailed quality patterns
- Consult existing refactored classes as examples
- Run `make phpmd` to identify specific complexity hotspots before refactoring
