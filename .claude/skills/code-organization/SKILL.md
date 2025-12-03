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

## Related Skills

- **quality-standards**: Maintains code quality metrics
- **ci-workflow**: Runs comprehensive checks
- **code-review**: Handles PR feedback about organization
