# Common Patterns Reference

Quick reference for identifying class types and their patterns.

## Pattern Catalog

### Converter Pattern

**Purpose**: Convert between different types

**Naming**: `{What}TypeConverter`, `{What}Converter`

**Location**: `*/Converter/`

**Examples**:
- `UlidTypeConverter` - Converts between Ulid types
- `DateTimeConverter` - Converts datetime formats

**Method signatures**:
```php
public function toX(mixed $value): X
public function fromY(mixed $value): X
public function convert(A $from): B
```

**Key traits**:
- Takes one type, returns another
- Usually stateless
- No side effects
- Pure functions

---

### Transformer Pattern

**Purpose**: Transform data for persistence/serialization

**Naming**: `{What}Transformer`

**Location**: `*/Transformer/`

**Examples**:
- `UlidTransformer` - Doctrine transformer for Ulid
- `CustomerRelationTransformer` - Transforms customer relations

**Method signatures**:
```php
public function toPhpValue(mixed $value): mixed
public function toDatabaseValue(mixed $value): mixed
public function transform(array $data): object
```

**Key traits**:
- Often implements framework interfaces
- Used by ORM/serialization
- Bidirectional transformation

---

### Validator Pattern

**Purpose**: Validate data

**Naming**: `{What}Validator`

**Location**: `*/Validator/`

**Examples**:
- `UlidValidator` - Validates ULID format
- `EmailValidator` - Validates email

**Method signatures**:
```php
public function validate(mixed $value): bool
public function isValid(mixed $value): bool
public function assert(mixed $value): void
```

**Key traits**:
- Single responsibility: validation
- No data modification
- Returns bool or throws exception
- Stateless

---

### Builder Pattern

**Purpose**: Construct complex objects

**Naming**: `{What}Builder`

**Location**: `*/Builder/`

**Examples**:
- `ArrayResponseBuilder` - Builds array responses
- `QueryBuilder` - Builds queries

**Method signatures**:
```php
public function build(): Result
public function withX(...): self
public function addY(...): self
```

**Key traits**:
- Fluent interface
- Step-by-step construction
- Returns `self` for chaining
- Final `build()` method

---

### Fixer Pattern

**Purpose**: Fix or modify existing structures

**Naming**: `{What}Fixer`, `{What}PropertyFixer`

**Location**: `*/Fixer/`

**Examples**:
- `ContentPropertyFixer` - Fixes content properties
- `SchemaFixer` - Fixes schema issues

**Method signatures**:
```php
public function fix(mixed $value): mixed
public function apply(mixed $target): mixed
```

**Key traits**:
- Modifies existing data
- Returns modified version
- Usually immutable (returns new instance)

---

### Cleaner Pattern

**Purpose**: Clean or filter data

**Naming**: `{What}Cleaner`

**Location**: `*/Cleaner/`

**Examples**:
- `ArrayValueCleaner` - Cleans array values
- `StringCleaner` - Cleans strings

**Method signatures**:
```php
public function clean(mixed $value): mixed
public function sanitize(mixed $value): mixed
```

**Key traits**:
- Removes unwanted data
- Normalizes values
- Returns cleaned version

---

### Factory Pattern

**Purpose**: Create objects

**Naming**: `{What}Factory`

**Location**: `*/Factory/`

**Examples**:
- `CustomerFactory` - Creates customers
- `CommandFactory` - Creates commands

**Method signatures**:
```php
public function create(...): object
public function createFromArray(array $data): object
public static function make(...): object
```

**Key traits**:
- Can use `new` keyword
- Encapsulates creation logic
- Often validates before creation

---

### Resolver Pattern

**Purpose**: Resolve values or references

**Naming**: `{What}Resolver`

**Location**: `*/Resolver/`

**Examples**:
- `CustomerUpdateScalarResolver` - Resolves scalar values
- `ConfigResolver` - Resolves configuration

**Method signatures**:
```php
public function resolve(...): mixed
public function resolveX(...): X
```

**Key traits**:
- Complex resolution logic
- May have dependencies
- Returns resolved value

---

### Processor Pattern

**Purpose**: Process data or requests

**Naming**: `{What}Processor`

**Location**: `*/Processor/`

**Examples**:
- `PathParametersProcessor` - Processes parameters
- `RequestProcessor` - Processes requests

**Method signatures**:
```php
public function process(mixed $input): mixed
public function handle(mixed $request): mixed
```

**Key traits**:
- Part of processing pipeline
- May modify input
- Returns processed result

---

### Serializer/Normalizer Pattern

**Purpose**: Serialize or normalize data

**Naming**: `{What}Normalizer`, `{What}Serializer`

**Location**: `*/Serializer/`

**Examples**:
- `OpenApiNormalizer` - Normalizes OpenAPI
- `JsonSerializer` - Serializes to JSON

**Method signatures**:
```php
public function normalize(mixed $object): array
public function denormalize(array $data): object
public function serialize(mixed $data): string
```

**Key traits**:
- Implements Symfony interfaces
- Converts objects to arrays/strings
- Bidirectional

---

## Identification Flowchart

```
Does it create objects?
  └─ YES → Factory/
  └─ NO ↓

Does it validate data?
  └─ YES → Validator/
  └─ NO ↓

Does it convert between types?
  └─ YES → Converter/
  └─ NO ↓

Does it transform for DB/serialization?
  └─ YES → Transformer/
  └─ NO ↓

Does it build complex objects step-by-step?
  └─ YES → Builder/
  └─ NO ↓

Does it fix/modify existing structures?
  └─ YES → Fixer/
  └─ NO ↓

Does it clean/filter data?
  └─ YES → Cleaner/
  └─ NO ↓

Does it resolve values/references?
  └─ YES → Resolver/
  └─ NO ↓

Does it process requests/data?
  └─ YES → Processor/
  └─ NO ↓

Does it serialize/normalize?
  └─ YES → Serializer/
  └─ NO ↓

Is it a domain entity?
  └─ YES → Domain/Entity/
  └─ NO ↓

Is it a value object?
  └─ YES → Domain/ValueObject/
  └─ NO ↓

Review the class purpose again!
```

---

## Common Method Name Patterns

### Creation Methods
```php
create()
createFrom*()
make()
build()
__construct()  // In factories
```

### Conversion Methods
```php
toX()
fromY()
convert()
parse()
```

### Transformation Methods
```php
transform()
toPhpValue()
toDatabaseValue()
normalize()
denormalize()
```

### Validation Methods
```php
validate()
isValid()
assert()
check()
```

### Modification Methods
```php
fix()
clean()
sanitize()
apply()
modify()
```

### Resolution Methods
```php
resolve()
get*()
find*()
determine()
```

### Processing Methods
```php
process()
handle()
execute()
apply()
```

---

## Naming Guidelines

### DO ✅

- **Be specific**: `UlidTypeConverter` not `UlidConverter`
- **Match directory**: In `Validator/`? Name ends with `Validator`
- **Describe action**: `CustomerUpdateScalarResolver` describes what it resolves
- **Use common suffixes**: Converter, Transformer, Validator, Builder, etc.

### DON'T ❌

- **Be vague**: `Helper`, `Utils`, `Manager`
- **Mix concerns**: `ConverterAndValidator`
- **Use generic names**: `DataProcessor` (processor of what data?)
- **Contradict directory**: `*Converter` in `Transformer/` directory

---

## Quick Examples

### ✅ Good Organization

```php
// src/Shared/Infrastructure/Converter/UlidTypeConverter.php
namespace App\Shared\Infrastructure\Converter;

final class UlidTypeConverter
{
    public function toUlid(mixed $value): Ulid { }
    public function fromBinary(mixed $value): Ulid { }
}
```

### ❌ Bad Organization

```php
// src/Shared/Infrastructure/Helper/UlidHelper.php  // ❌ "Helper" is vague
namespace App\Shared\Infrastructure\Helper;

final class UlidHelper  // ❌ What does it help with?
{
    public function convert(...) { }  // ❌ Should be UlidTypeConverter in Converter/
    public function validate(...) { }  // ❌ Should be UlidValidator in Validator/
    public function transform(...) { } // ❌ Should be UlidTransformer in Transformer/
}
```

---

## Class Responsibility Matrix

| If the class... | It's a... | Goes in... |
|----------------|-----------|------------|
| Creates objects | Factory | `*/Factory/` |
| Validates data | Validator | `*/Validator/` |
| Converts types | Converter | `*/Converter/` |
| Transforms for DB | Transformer | `*/Transformer/` |
| Builds step-by-step | Builder | `*/Builder/` |
| Fixes structures | Fixer | `*/Fixer/` |
| Cleans data | Cleaner | `*/Cleaner/` |
| Resolves values | Resolver | `*/Resolver/` |
| Processes requests | Processor | `*/Processor/` |
| Serializes data | Serializer/Normalizer | `*/Serializer/` |
| Is a domain entity | Entity | `Domain/Entity/` |
| Is immutable data | ValueObject | `Domain/ValueObject/` |
| Defines persistence | Repository Interface | `Domain/Repository/` |
| Handles commands | CommandHandler | `Application/CommandHandler/` |
| Represents command | Command | `Application/Command/` |
