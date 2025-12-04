# Directory Structure Reference

This document provides a comprehensive reference for the project's directory structure and where each type of class belongs.

## Infrastructure Layer

```
src/Shared/Infrastructure/
├── Converter/       → Type conversion (Type A → Type B)
│   └── UlidTypeConverter.php
├── Transformer/     → Data transformation (DB ↔ PHP, format changes)
│   └── UlidTransformer.php
├── Validator/       → Validation logic
│   └── UlidValidator.php
├── Factory/         → Object creation
│   └── EntityFactory.php
├── Filter/          → Data filtering
│   └── DataFilter.php
├── DoctrineType/    → Doctrine custom types
│   └── UlidType.php
└── Bus/             → Message bus implementations
    └── CommandBus.php
```

### Converter/ Directory

**Purpose**: Convert between different types

**Examples**:
- `UlidTypeConverter` - Converts between binary, string, and Ulid types
- `DateTimeConverter` - Converts between different datetime formats

**Key characteristics**:
- Focuses on type conversion
- Takes one type, returns another
- Usually stateless
- Methods like `toX()`, `fromY()`

### Transformer/ Directory

**Purpose**: Transform data between layers (DB ↔ PHP)

**Examples**:
- `UlidTransformer` - Transforms Ulid for Doctrine persistence
- `CustomerTransformer` - Transforms customer data between DB and domain

**Key characteristics**:
- Used by Doctrine for database transformations
- Implements transformer interfaces
- Methods like `toPhpValue()`, `toDatabaseValue()`

### Validator/ Directory

**Purpose**: Validate data

**Examples**:
- `UlidValidator` - Validates ULID format
- `EmailValidator` - Validates email format

**Key characteristics**:
- Single responsibility: validation
- Returns boolean or throws exception
- Methods like `validate()`, `isValid()`

---

## Application Layer

```
src/Shared/Application/OpenApi/
├── Builder/         → Building/constructing components
│   └── ArrayResponseBuilder.php
├── Fixer/           → Fixing/modifying properties
│   └── ContentPropertyFixer.php
├── Cleaner/         → Cleaning/filtering data
│   └── ArrayValueCleaner.php
├── Serializer/      → Serialization/normalization
│   └── OpenApiNormalizer.php
├── Factory/         → Creating instances
│   └── OpenApiFactory.php
├── Augmenter/       → Augmenting/enhancing data
│   └── DataAugmenter.php
├── Sanitizer/       → Sanitizing input
│   └── InputSanitizer.php
├── ValueObject/     → Value objects (data holders)
│   └── OpenApiSpec.php
├── Processor/       → Processing data/requests
│   └── PathParametersProcessor.php
├── Resolver/        → Resolving values/references
│   └── ScalarResolver.php
└── Extension/       → Extensions/plugins
    └── CustomExtension.php
```

### Builder/ Directory

**Purpose**: Build or construct complex objects

**Examples**:
- `ArrayResponseBuilder` - Builds array responses from data
- `RequestBuilder` - Builds HTTP requests

**Key characteristics**:
- Constructs complex objects step by step
- Often has fluent interface
- Methods like `build()`, `with*()`, `add*()`

### Fixer/ Directory

**Purpose**: Fix or modify existing data/objects

**Examples**:
- `ContentPropertyFixer` - Fixes content properties in schemas
- `SchemaFixer` - Fixes schema inconsistencies

**Key characteristics**:
- Modifies existing structures
- Takes input, returns modified version
- Methods like `fix()`, `apply()`

### Cleaner/ Directory

**Purpose**: Clean or filter data

**Examples**:
- `ArrayValueCleaner` - Cleans array values
- `StringCleaner` - Removes unwanted characters

**Key characteristics**:
- Removes unwanted data
- Normalizes values
- Methods like `clean()`, `sanitize()`

### Serializer/ Directory

**Purpose**: Serialize or normalize data

**Examples**:
- `OpenApiNormalizer` - Normalizes OpenAPI structures
- `JsonSerializer` - Serializes to JSON

**Key characteristics**:
- Converts objects to arrays/strings
- Implements Symfony normalizer interfaces
- Methods like `normalize()`, `denormalize()`, `serialize()`

### Factory/ Directory

**Purpose**: Create objects

**Examples**:
- `OpenApiFactory` - Creates OpenAPI specifications
- `CustomerFactory` - Creates customer entities

**Key characteristics**:
- Primary responsibility is object creation
- Uses `new` keyword (allowed in factories)
- Methods like `create()`, `createFrom*()`

### Processor/ Directory

**Purpose**: Process data or requests

**Examples**:
- `PathParametersProcessor` - Processes path parameters
- `RequestProcessor` - Processes HTTP requests

**Key characteristics**:
- Processes and transforms data
- Often part of a pipeline
- Methods like `process()`, `handle()`

### Resolver/ Directory

**Purpose**: Resolve values, references, or configurations

**Examples**:
- `CustomerUpdateScalarResolver` - Resolves scalar values
- `ConfigResolver` - Resolves configuration values

**Key characteristics**:
- Determines or resolves values
- Often handles complex logic
- Methods like `resolve()`, `get*()`

---

## Domain Layer

```
src/Core/Customer/Domain/
├── Entity/          → Domain entities
│   ├── Customer.php
│   ├── CustomerType.php
│   └── CustomerStatus.php
├── ValueObject/     → Value objects
│   ├── CustomerUpdate.php
│   └── CustomerStatusUpdate.php
├── Repository/      → Repository interfaces
│   └── CustomerRepositoryInterface.php
├── Factory/         → Domain factories
│   └── CustomerFactory.php
├── Event/           → Domain events
│   ├── CustomerCreated.php
│   └── CustomerUpdated.php
└── Exception/       → Domain exceptions
    ├── CustomerNotFoundException.php
    └── CustomerTypeNotFoundException.php
```

### Entity/ Directory

**Purpose**: Domain entities with identity

**Examples**:
- `Customer` - Customer entity
- `CustomerType` - Customer type entity

**Key characteristics**:
- Have unique identity
- Mutable state
- Business logic
- Persistence through repositories

### ValueObject/ Directory

**Purpose**: Immutable value objects

**Examples**:
- `CustomerUpdate` - Represents customer update data
- `Ulid` - Represents ULID value

**Key characteristics**:
- Immutable
- Defined by their values
- No identity
- Can use `new` for creation

### Repository/ Directory

**Purpose**: Repository interfaces (not implementations)

**Examples**:
- `CustomerRepositoryInterface` - Customer repository contract
- `CustomerTypeRepositoryInterface` - Customer type repository contract

**Key characteristics**:
- Interfaces only (implementations in Infrastructure)
- Define persistence contracts
- Methods like `find()`, `save()`, `findBy*()`

### Factory/ Directory

**Purpose**: Create domain entities

**Examples**:
- `CustomerFactory` - Creates customer entities
- `CustomerTypeFactory` - Creates customer type entities

**Key characteristics**:
- Domain logic for entity creation
- Validates business rules
- Methods like `create()`, `createFrom*()`

### Event/ Directory

**Purpose**: Domain events

**Examples**:
- `CustomerCreated` - Event when customer is created
- `CustomerUpdated` - Event when customer is updated

**Key characteristics**:
- Immutable
- Past tense naming
- Carry event data
- Extend `DomainEvent`

---

## Application Command Layer

```
src/Core/Customer/Application/
├── Command/             → Command objects
│   ├── CreateCustomerCommand.php
│   └── UpdateCustomerCommand.php
├── CommandHandler/      → Command handlers
│   ├── CreateCustomerCommandHandler.php
│   └── UpdateCustomerCommandHandler.php
├── Transformer/         → Application transformers
│   └── CustomerRelationTransformer.php
├── Resolver/            → Application resolvers
│   └── CustomerUpdateScalarResolver.php
├── Factory/             → Application factories
│   ├── CustomerUpdateFactory.php
│   └── CreateCustomerCommandFactory.php
└── Processor/           → Request processors
    ├── CreateCustomerProcessor.php
    └── UpdateCustomerProcessor.php
```

### Command/ Directory

**Purpose**: Command objects (CQRS write operations)

**Examples**:
- `CreateCustomerCommand` - Command to create customer
- `UpdateCustomerCommand` - Command to update customer

**Key characteristics**:
- Immutable
- Implement `CommandInterface`
- Represent intent
- No business logic

### CommandHandler/ Directory

**Purpose**: Handle commands and execute business logic

**Examples**:
- `CreateCustomerCommandHandler` - Handles customer creation
- `UpdateCustomerCommandHandler` - Handles customer updates

**Key characteristics**:
- Implement `CommandHandlerInterface`
- Execute business logic
- Coordinate domain objects
- Methods like `__invoke()`, `handle()`

---

## Quick Decision Tree

### "Where does my new class belong?"

1. **What does it DO?**
   - Creates objects → `Factory/`
   - Validates data → `Validator/`
   - Converts types → `Converter/`
   - Transforms for DB → `Transformer/`
   - Builds complex objects → `Builder/`
   - Fixes/modifies data → `Fixer/`
   - Cleans/filters data → `Cleaner/`
   - Serializes data → `Serializer/`
   - Processes requests → `Processor/`
   - Resolves values → `Resolver/`

2. **Which LAYER?**
   - Domain logic → `Domain/`
   - Application logic → `Application/`
   - Infrastructure (DB, external) → `Infrastructure/`
   - Shared across contexts → `Shared/`

3. **Which CONTEXT?**
   - Customer-related → `Core/Customer/`
   - Order-related → `Core/Order/`
   - Used everywhere → `Shared/`

### Examples

**"I need to create a class that validates email format"**
- DOES: Validates → `Validator/`
- LAYER: Can be shared → `Shared/Infrastructure/`
- RESULT: `src/Shared/Infrastructure/Validator/EmailValidator.php`

**"I need to create a class that builds OpenAPI schemas"**
- DOES: Builds → `Builder/`
- LAYER: Application (OpenAPI is app-level) → `Application/`
- CONTEXT: OpenAPI is shared → `Shared/Application/OpenApi/`
- RESULT: `src/Shared/Application/OpenApi/Builder/SchemaBuilder.php`

**"I need to create a class that resolves scalar values for customer updates"**
- DOES: Resolves → `Resolver/`
- LAYER: Application logic → `Application/`
- CONTEXT: Customer-specific → `Core/Customer/`
- RESULT: `src/Core/Customer/Application/Resolver/CustomerUpdateScalarResolver.php`

**"I need to create a customer entity"**
- DOES: Domain entity → `Entity/`
- LAYER: Domain → `Domain/`
- CONTEXT: Customer → `Core/Customer/`
- RESULT: `src/Core/Customer/Domain/Entity/Customer.php`

---

## Common Mistakes

### ❌ Mistake 1: Putting everything in one directory

```
src/Shared/Infrastructure/
└── Helper/
    ├── UlidValidator.php      // Should be in Validator/
    ├── UlidConverter.php      // Should be in Converter/
    ├── UlidTransformer.php    // Should be in Transformer/
    └── UlidFactory.php        // Should be in Factory/
```

### ✅ Solution: Separate by responsibility

```
src/Shared/Infrastructure/
├── Validator/
│   └── UlidValidator.php
├── Converter/
│   └── UlidTypeConverter.php
├── Transformer/
│   └── UlidTransformer.php
└── Factory/
    └── UlidFactory.php
```

---

### ❌ Mistake 2: Wrong layer

```
src/Core/Customer/Domain/
└── Builder/
    └── CustomerResponseBuilder.php  // Response building is Application layer!
```

### ✅ Solution: Move to correct layer

```
src/Core/Customer/Application/
└── Builder/
    └── CustomerResponseBuilder.php
```

---

### ❌ Mistake 3: Wrong context

```
src/Shared/Infrastructure/
└── Factory/
    └── CustomerFactory.php  // Customer-specific, not shared!
```

### ✅ Solution: Move to correct context

```
src/Core/Customer/Domain/
└── Factory/
    └── CustomerFactory.php
```
