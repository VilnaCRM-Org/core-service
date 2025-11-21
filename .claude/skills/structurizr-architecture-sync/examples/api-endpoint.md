# Example: Adding API Endpoint

Complete example of documenting a REST API endpoint with API Platform in Structurizr.

## Scenario

Implementing a new REST API endpoint: **GET /api/customers/{id}** using API Platform.

### Components Implemented

**Application Layer**:

- `CustomerController` (API entry point)
- `CustomerItemProvider` (API Platform item provider)
- `UuidTransformer` (transforms UUID strings to value objects)

**Domain Layer**:

- `Customer` (entity)
- `Uuid` (value object)
- `UuidFactoryInterface` (factory port)

**Infrastructure Layer**:

- `CustomerRepository` (data access)
- `UuidFactory` (factory implementation)

## Step 1: Add Application Layer Components

```dsl
group "Application" {
    customerController = component "CustomerController" "Handles customer API requests" "Controller" {
        tags "Item"
    }

    customerItemProvider = component "CustomerItemProvider" "Provides customer items for API Platform" "ItemProvider" {
        tags "Item"
    }

    uuidTransformer = component "UuidTransformer" "Transforms UUID strings to value objects" "Transformer" {
        tags "Item"
    }
}
```

## Step 2: Add Domain Layer Components (if not already present)

```dsl
group "Domain" {
    customer = component "Customer" "Customer entity" "Entity" {
        tags "Item"
    }

    uuid = component "Uuid" "UUID value object" "ValueObject" {
        tags "Item"
    }

    uuidFactoryInterface = component "UuidFactoryInterface" "Contract for UUID creation" "Interface" {
        tags "Item"
    }
}
```

## Step 3: Add Infrastructure Layer Components (if not already present)

```dsl
group "Infrastructure" {
    customerRepository = component "CustomerRepository" "Retrieves customers from MongoDB" "Repository" {
        tags "Item"
    }

    uuidFactory = component "UuidFactory" "Creates UUID value objects" "Factory" {
        tags "Item"
    }
}
```

## Step 4: Add External Dependencies (if not already present)

```dsl
database = component "Database" "MongoDB instance" "MongoDB" {
    tags "Database"
}
```

## Step 5: Add Relationships

### API Request Flow

```dsl
# Controller uses item provider
customerController -> customerItemProvider "delegates to"

# Item provider uses repository
customerItemProvider -> customerRepository "uses to retrieve customer"

# Controller uses UUID transformer
customerController -> uuidTransformer "uses to transform ID parameter"
```

### UUID Transformation Flow

```dsl
# Transformer depends on factory interface
uuidTransformer -> uuidFactoryInterface "uses"

# Factory implements interface
uuidFactory -> uuidFactoryInterface "implements"

# Factory creates UUID value object
uuidFactory -> uuid "creates"
```

### Data Access Flow

```dsl
# Repository retrieves customer entity
customerRepository -> customer "retrieves"

# Repository reads from database
customerRepository -> database "retrieves from"
```

## Complete workspace.dsl Addition

```dsl
# Application Layer
group "Application" {
    customerController = component "CustomerController" "Handles customer API requests" "Controller" {
        tags "Item"
    }

    customerItemProvider = component "CustomerItemProvider" "Provides customer items for API Platform" "ItemProvider" {
        tags "Item"
    }

    uuidTransformer = component "UuidTransformer" "Transforms UUID strings to value objects" "Transformer" {
        tags "Item"
    }
}

# Domain Layer
group "Domain" {
    customer = component "Customer" "Customer entity" "Entity" {
        tags "Item"
    }

    uuid = component "Uuid" "UUID value object" "ValueObject" {
        tags "Item"
    }

    uuidFactoryInterface = component "UuidFactoryInterface" "Contract for UUID creation" "Interface" {
        tags "Item"
    }
}

# Infrastructure Layer
group "Infrastructure" {
    customerRepository = component "CustomerRepository" "Retrieves customers from MongoDB" "Repository" {
        tags "Item"
    }

    uuidFactory = component "UuidFactory" "Creates UUID value objects" "Factory" {
        tags "Item"
    }
}

# External Dependencies
database = component "Database" "MongoDB instance" "MongoDB" {
    tags "Database"
}

# API request flow
customerController -> customerItemProvider "delegates to"
customerController -> uuidTransformer "uses to transform ID parameter"
customerItemProvider -> customerRepository "uses to retrieve customer"

# UUID transformation
uuidTransformer -> uuidFactoryInterface "uses"
uuidFactory -> uuidFactoryInterface "implements"
uuidFactory -> uuid "creates"

# Data access
customerRepository -> customer "retrieves"
customerRepository -> database "retrieves from"
```

## Visual Result

The generated diagram will show:

1. **Request Entry**:

   - CustomerController (entry point)

2. **Request Processing**:

   - Controller → UUID Transformer (transform ID)
   - Controller → Item Provider (retrieve data)
   - Item Provider → Repository (data access)

3. **UUID Transformation**:

   - Transformer → Factory Interface
   - Factory → Implements Interface
   - Factory → Creates UUID

4. **Data Access**:
   - Repository → Retrieves Customer
   - Repository → Reads from Database

## Alternative: State Processor Pattern

For **POST /api/customers** (write operations), use state processor:

### Additional Components

```dsl
group "Application" {
    customerStateProcessor = component "CustomerStateProcessor" "Processes customer state changes" "StateProcessor" {
        tags "Item"
    }

    createCustomerCommandHandler = component "CreateCustomerCommandHandler" "Handles customer creation" "CommandHandler" {
        tags "Item"
    }
}
```

### Additional Relationships

```dsl
# Processor uses command handler
customerStateProcessor -> createCustomerCommandHandler "delegates to"

# Handler creates customer
createCustomerCommandHandler -> customer "creates"

# Handler uses repository
createCustomerCommandHandler -> customerRepository "uses for persistence"
```

## GraphQL Resolver Pattern

For **GraphQL customer queries**, use resolver:

### Additional Components

```dsl
group "Application" {
    customerResolver = component "CustomerResolver" "Resolves GraphQL customer queries" "Resolver" {
        tags "Item"
    }
}
```

### Additional Relationships

```dsl
# Resolver uses repository
customerResolver -> customerRepository "uses to retrieve customer"

# Resolver transforms results
customerResolver -> uuidTransformer "uses to transform IDs"
```

## Verification Checklist

- [x] Controller documented
- [x] API Platform provider/processor documented
- [x] UUID transformer documented
- [x] Repository documented
- [x] Factory and interface documented
- [x] Domain entity documented
- [x] External dependencies documented
- [x] Request flow relationships clear
- [x] UUID transformation flow clear
- [x] Data access flow clear
- [x] Hexagonal architecture visible
- [x] Layer groupings correct
- [x] No DTOs included

## Common Questions

### Q: Should I document every REST endpoint?

**A**: Document controllers and their key dependencies. If multiple endpoints share the same controller and dependencies, one documentation is sufficient.

### Q: Should I include API Platform DTOs?

**A**: No. DTOs (input/output classes) are data structures. Document providers, processors, and resolvers instead.

### Q: How do I show API Platform's automatic wiring?

**A**: Show the main components (controller, provider/processor, repository). API Platform's internal wiring is framework detail, not architecture.

### Q: Should I document validators?

**A**: Only if they contain significant business logic. Simple constraint validators can be omitted.

### Q: How do I differentiate between read and write operations?

**A**: Use different relationship descriptions:

```dsl
# Read operation
itemProvider -> repository "retrieves from"

# Write operation
stateProcessor -> repository "persists via"
```

## Integration with CQRS

If using CQRS with API endpoints:

```dsl
# Write endpoint (command)
customerController -> customerStateProcessor "uses"
customerStateProcessor -> createCustomerCommandHandler "dispatches to"

# Read endpoint (query)
customerController -> customerItemProvider "uses"
customerItemProvider -> customerQueryHandler "queries via"
```

## Pagination and Filtering

For collection endpoints with filters:

```dsl
# Collection provider with filters
customerCollectionProvider = component "CustomerCollectionProvider" "Provides paginated customer collections" "CollectionProvider" {
    tags "Item"
}

# Provider uses repository with filters
customerCollectionProvider -> customerRepository "retrieves filtered results from"

# Repository applies filters
customerRepository -> database "queries with filters"
```

## Next Steps

After documenting the API endpoint:

1. **Validate DSL syntax**:

   ```bash
   structurizr-cli validate workspace.dsl
   ```

2. **Generate diagram**:

   ```bash
   docker run -it --rm -p 8080:8080 \
     -v $(pwd):/usr/local/structurizr \
     structurizr/lite
   ```

3. **Review API request flow**: Ensure clear path from controller to database

4. **Update API documentation**: Use [developing-openapi-specs](../../developing-openapi-specs/SKILL.md) skill

5. **Update documentation**: Use [documentation-sync](../../documentation-sync/SKILL.md) skill

6. **Run CI checks**: Use [ci-workflow](../../ci-workflow/SKILL.md) skill
