# 🤖 AI AGENT: REFACTOR COMPLEXITY NOW

## YOUR MISSION

Reduce cyclomatic complexity in the top 20 most complex classes to < 5 per method while maintaining 100% test coverage and 100% mutation score.

## START HERE

```bash
make analyze-complexity N=20
```

## WORKFLOW (Repeat for Each Class)

### 1️⃣ ANALYZE

- Read class implementation
- Review existing tests
- Identify complex methods (high if/else, loops, boolean operators)

### 2️⃣ REFACTOR (Pick One Pattern)

**Extract Method** - Break complex methods into focused ones
**Strategy Pattern** - Replace conditionals with strategy classes
**Guard Clauses** - Replace nested ifs with early returns
**Extract Validator** - Move validation to separate classes

### 3️⃣ TEST

```bash
make phpcsfixer && make psalm && make unit-tests && make infection
```

ALL must pass. Fix issues immediately.

### 4️⃣ COMMIT

```bash
git add . && git commit -m "Refactor: reduce complexity in ClassName using [pattern]"
```

### 5️⃣ VERIFY (Every 5 Classes)

```bash
make ci
```

MUST output: **"✅ CI checks successfully passed!"**

## QUICK PATTERNS

### Extract Method

```php
// BEFORE (complexity: 8)
if (!$value || strlen($value) < 8 || strlen($value) > 64 ||
    !preg_match('/[A-Z]/', $value)) return false;

// AFTER (complexity: 1 each)
if (!$this->hasValidLength($value)) return false;
if (!$this->hasUppercase($value)) return false;

private function hasValidLength(?string $value): bool {
    return $value && strlen($value) >= 8 && strlen($value) <= 64;
}
```

### Strategy Pattern

```php
// BEFORE (complexity: 12)
if ($type === 'email') { /* 15 lines */ }
elseif ($type === 'phone') { /* 15 lines */ }

// AFTER (complexity: 2)
return $this->strategyFactory->create($type)->process($data);
```

### Guard Clauses

```php
// BEFORE (nested, complexity: 4)
if ($value !== null) {
    if ($value > 0) {
        if ($value < 100) return $value * 2;
    }
}
return 0;

// AFTER (flat, complexity: 3)
if ($value === null) return 0;
if ($value <= 0) return 0;
if ($value >= 100) return 0;
return $value * 2;
```

## RULES

✅ Refactor ONE class at a time
✅ Keep changes minimal and surgical
✅ Write self-documenting code (NO comments)
✅ Maintain 100% test coverage
✅ Ensure 100% mutation score (0 escaped mutants)

❌ NO decreasing quality thresholds
❌ NO skipping failing tests
❌ NO inline comments
❌ NO breaking architectural boundaries

## TARGETS

- **Avg complexity per method: < 5**
- **PHPInsights complexity: ≥ 95%**
- **Unit test coverage: 100%**
- **Mutation testing MSI: 100%**

## SUCCESS PER CLASS

- ✅ Complexity < 5 per method
- ✅ All tests pass (100% coverage)
- ✅ Mutation testing: 100% MSI
- ✅ `make ci` shows success message
- ✅ Code is self-explanatory
- ✅ Clear commit message

## GO!

```bash
make analyze-complexity N=20
```

Start with class #1. Refactor systematically. Verify constantly. Commit frequently.

**Full Guide**: `COMPLEXITY_REFACTORING_GUIDE.md`
