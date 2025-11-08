# AGENT PROMPT: Code Complexity Refactoring

## Your Task
Systematically refactor the top 20 most complex classes in this codebase to reduce cyclomatic complexity below 5 per method while maintaining 100% test coverage and 100% mutation testing score.

## Step-by-Step Instructions

### 1. Analyze Current Complexity
```bash
make analyze-complexity N=20
```

This will show you the 20 most complex classes with metrics for each.

### 2. For Each Complex Class (Starting with #1)

#### A. Understand the Code
- Read the class implementation
- Review existing tests
- Identify methods with high complexity (multiple if/else, loops, boolean operators)

#### B. Plan Refactoring
Choose appropriate pattern(s):
- **Extract Method**: Break complex methods into smaller, focused methods
- **Strategy Pattern**: Replace complex conditionals with strategy classes  
- **Guard Clauses**: Replace nested if statements with early returns
- **Extract Validator**: Move validation logic to separate validator classes

#### C. Refactor
- Apply ONE pattern at a time
- Keep changes minimal and surgical
- Maintain backward compatibility
- Write self-documenting code (NO inline comments)

#### D. Update Tests
- Ensure all existing tests pass
- Add tests for new extracted methods/classes
- Maintain 100% coverage

#### E. Verify Quality
```bash
make phpcsfixer    # Fix code style
make psalm         # Static analysis  
make unit-tests    # Verify tests
make infection     # Mutation testing
```

All must pass before proceeding.

#### F. Commit
```bash
git add .
git commit -m "Refactor: reduce complexity in ClassName using [pattern name]"
```

### 3. After Every 5 Classes

Run full CI to ensure no regressions:
```bash
make ci
```

**CRITICAL**: Output MUST show "✅ CI checks successfully passed!"

### 4. Track Progress
```bash
make analyze-complexity N=20
```

Verify average complexity is decreasing.

## Refactoring Examples

### Example 1: Extract Method
```php
// BEFORE (complexity: 8)
public function validate($value): bool
{
    if (!$value || strlen($value) < 8 || strlen($value) > 64 || 
        !preg_match('/[A-Z]/', $value) || !preg_match('/[0-9]/', $value)) {
        return false;
    }
    return true;
}

// AFTER (complexity: 1 per method)
public function validate($value): bool
{
    if (!$this->hasValidLength($value)) return false;
    if (!$this->hasUppercase($value)) return false;
    if (!$this->hasDigit($value)) return false;
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

### Example 2: Strategy Pattern
```php
// BEFORE (complexity: 12)
public function process($type, $data)
{
    if ($type === 'email') {
        // 15 lines of complex email logic
    } elseif ($type === 'phone') {
        // 15 lines of complex phone logic
    }
}

// AFTER (complexity: 2)
public function process($type, $data)
{
    $strategy = $this->strategyFactory->create($type);
    return $strategy->process($data);
}

// Separate strategy classes with focused logic
```

## Quality Requirements (MUST MAINTAIN)

### PHPInsights
- min-quality: 100%
- min-complexity: 95%
- min-architecture: 100%
- min-style: 100%

### Testing
- Unit test coverage: 100%
- Mutation testing (Infection): 100% MSI (0 escaped mutants)

### Target
- **Cyclomatic complexity: < 5 per method**

## What NOT to Do

❌ DO NOT decrease quality thresholds
❌ DO NOT skip failing tests
❌ DO NOT add inline comments (refactor instead)
❌ DO NOT break architectural boundaries
❌ DO NOT refactor multiple classes before verifying each one

## Success Criteria Per Class

- ✅ Average complexity < 5 per method
- ✅ All tests pass with 100% coverage
- ✅ Mutation testing: 100% MSI
- ✅ Code is self-explanatory
- ✅ Changes committed with clear message

## Start Now

Run this command and begin with class #1:

```bash
make analyze-complexity N=20
```

Then systematically refactor each class following the workflow above.

## Additional Resources

- Full guide: `COMPLEXITY_REFACTORING_GUIDE.md`
- Repository guidelines: `AGENTS.md`
- Quality standards: `.claude/skills/quality-standards.md`
