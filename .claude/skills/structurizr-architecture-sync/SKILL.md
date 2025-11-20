---
name: structurizr-architecture-sync
description: Maintain Structurizr C4 architecture diagrams in sync with code changes. Use when adding components, modifying relationships, changing architectural boundaries, or implementing new patterns. Ensures workspace.dsl accurately reflects the current system architecture.
---

# Structurizr Architecture Synchronization

## Context (Input)

Use this skill when:

- Adding new components (controllers, handlers, services, repositories)
- Creating new entities, value objects, or aggregates
- Modifying component relationships or dependencies
- Implementing new architectural patterns (CQRS, events, subscribers)
- Adding infrastructure components (databases, caches, message brokers)
- Refactoring that changes component structure
- After fixing Deptrac violations (may indicate architecture drift)
- Creating new bounded contexts or modules
- Implementing new API endpoints with significant handlers

## Task (Function)

Keep the Structurizr workspace (`workspace.dsl`) synchronized with codebase changes, ensuring C4 model diagrams accurately represent the current system architecture.

**Success Criteria**:

- `workspace.dsl` contains all significant components
- Component relationships match actual code dependencies
- Layer groupings (Application/Domain/Infrastructure) are accurate
- Component descriptions reflect current purpose
- All infrastructure dependencies are documented
- C4 diagrams can be generated without errors

---

## Critical Principles

### 🎯 What to Document

**DO include**:

- ✅ Controllers and API handlers
- ✅ Command handlers and event subscribers
- ✅ Domain entities and value objects
- ✅ Aggregates and factories
- ✅ Infrastructure implementations (repositories, buses, transformers)
- ✅ External dependencies (databases, caches, message brokers)
- ✅ Significant relationships between components

**DON'T include**:

- ❌ DTOs (too granular, data structures not components)
- ❌ Simple interfaces without business logic
- ❌ Framework classes (Symfony kernel, etc.)
- ❌ Test classes
- ❌ Trivial utility functions
- ❌ Every single class (focus on architectural significance)

### 🏗️ Architecture Layers

Components must be grouped by their architectural layer:

| Layer                | Contains                                                        | DSL Group         |
| -------------------- | --------------------------------------------------------------- | ----------------- |
| **Application**      | Controllers, Command Handlers, Event Subscribers, API handlers  | `group "Application"` |
| **Domain**           | Entities, Value Objects, Aggregates, Domain Events, Interfaces  | `group "Domain"`      |
| **Infrastructure**   | Repositories, Buses, Transformers, Doctrine Types, External adapters | `group "Infrastructure"` |

**Alignment with Deptrac**: Layer groupings must match Deptrac configuration to maintain architectural integrity.

---

## Structurizr DSL Syntax

### Component Definition

```dsl
componentName = component "ComponentDisplayName" "Component description" "ComponentType" {
    tags "Tag1" "Tag2"
}
```

**Example**:

```dsl
customerCommandHandler = component "CustomerCommandHandler" "Handles customer commands" "CommandHandler" {
    tags "Item"
}
```

### Relationship Definition

```dsl
sourceComponent -> targetComponent "relationship description"
```

**Example**:

```dsl
customerCommandHandler -> customerRepository "uses"
customerCommandHandler -> customerCreatedEvent "creates"
```

### Common Component Types

| Type                | Purpose                    | Layer            |
| ------------------- | -------------------------- | ---------------- |
| `Controller`        | API endpoint handlers      | Application      |
| `CommandHandler`    | CQRS command handlers      | Application      |
| `EventSubscriber`   | Domain event subscribers   | Infrastructure   |
| `Entity`            | Domain entities            | Domain           |
| `ValueObject`       | Domain value objects       | Domain           |
| `Aggregate`         | Domain aggregates          | Domain           |
| `DomainEvent`       | Domain events              | Domain           |
| `Factory`           | Object factories           | Domain/Infrastructure |
| `Repository`        | Data access                | Infrastructure   |
| `Transformer`       | Data transformers          | Infrastructure   |
| `EventBus`          | Event publishing           | Infrastructure   |
| `MongoDB`           | Database                   | Infrastructure   |
| `Redis`             | Cache                      | Infrastructure   |
| `AWS SQS`           | Message broker             | Infrastructure   |

### Tags

Use tags for visual styling:

- `"Item"` - Standard component (blue background)
- `"Database"` - External database/cache/broker (cylinder shape, blue background)

---

## Update Workflow

### Step 1: Identify Architectural Changes

After implementing code changes, determine if they are architecturally significant:

**Questions to ask**:

- Did you create a new component (handler, controller, repository)?
- Did you add a new entity or value object?
- Did you change how components interact?
- Did you add external dependencies?
- Did you refactor across architectural layers?

**If YES to any** → Update `workspace.dsl`

### Step 2: Determine Component Details

For each new/modified component, identify:

| Detail         | How to Identify                                  |
| -------------- | ------------------------------------------------ |
| **Name**       | Class name (e.g., `CustomerCommandHandler`)      |
| **Layer**      | File location (`Application/Domain/Infrastructure`) |
| **Type**       | Class purpose (Controller, Handler, Entity, etc.)   |
| **Description**| Class docblock or primary responsibility         |
| **Dependencies**| Constructor parameters, method calls            |

### Step 3: Update workspace.dsl

#### Pattern A: Adding a New Component

1. **Locate the appropriate group** (Application/Domain/Infrastructure)
2. **Add component definition**:

```dsl
newComponent = component "NewComponent" "Brief description" "ComponentType" {
    tags "Item"
}
```

3. **Add relationships** from/to this component:

```dsl
newComponent -> existingComponent "uses"
existingComponent -> newComponent "triggers"
```

#### Pattern B: Adding Relationships

If an existing component gains new dependencies:

```dsl
existingComponent -> newDependency "description"
```

#### Pattern C: Renaming or Refactoring

1. **Update component name** and variable:

```dsl
// Old
oldComponentName = component "OldComponent" ...

// New
newComponentName = component "NewComponent" ...
```

2. **Update all relationships** referencing this component

#### Pattern D: Removing Components

1. **Delete component definition**
2. **Remove all relationships** involving this component

### Step 4: Validate Changes

Run validation checks:

```bash
# If Structurizr CLI is available
structurizr-cli validate workspace.dsl

# Manual validation
# - Check for syntax errors
# - Ensure all referenced components are defined
# - Verify relationships make architectural sense
```

### Step 5: Generate and Review Diagrams

```bash
# If using Structurizr Lite (Docker)
docker run -it --rm -p 8080:8080 -v $(pwd):/usr/local/structurizr structurizr/lite

# Access at http://localhost:8080
# Review generated C4 component diagrams
```

**Validation checklist**:

- [ ] All components visible in diagram
- [ ] Relationships flow logically
- [ ] Layer groupings are clear
- [ ] No orphaned components (components with no relationships)
- [ ] External dependencies properly marked

---

## Common Scenarios

### Scenario 1: Adding a New CQRS Command Handler

**Code change**: Created `CreateCustomerCommandHandler`

**workspace.dsl update**:

```dsl
group "Application" {
    createCustomerHandler = component "CreateCustomerCommandHandler" "Handles customer creation commands" "CommandHandler" {
        tags "Item"
    }
}

# Add relationships
createCustomerHandler -> customerEntity "creates"
createCustomerHandler -> customerRepository "uses"
createCustomerHandler -> customerCreatedEvent "publishes"
```

### Scenario 2: Adding a Domain Entity

**Code change**: Created `Customer` entity with value objects

**workspace.dsl update**:

```dsl
group "Domain" {
    customerEntity = component "Customer" "Represents a customer aggregate" "Entity" {
        tags "Item"
    }
    customerIdVO = component "CustomerId" "Customer identifier value object" "ValueObject" {
        tags "Item"
    }
    customerEmailVO = component "CustomerEmail" "Customer email value object" "ValueObject" {
        tags "Item"
    }
}

# Add relationships
customerEntity -> customerIdVO "has"
customerEntity -> customerEmailVO "has"
```

### Scenario 3: Adding an Infrastructure Repository

**Code change**: Implemented `CustomerRepository`

**workspace.dsl update**:

```dsl
group "Infrastructure" {
    customerRepository = component "CustomerRepository" "Persists customers to MongoDB" "Repository" {
        tags "Item"
    }
}

# Add relationships
customerRepository -> database "persists to"
customerRepository -> customerEntity "stores/retrieves"
```

### Scenario 4: Adding Event Subscriber

**Code change**: Created `SendWelcomeEmailSubscriber`

**workspace.dsl update**:

```dsl
group "Infrastructure" {
    sendWelcomeEmailSubscriber = component "SendWelcomeEmailSubscriber" "Sends welcome email on customer creation" "EventSubscriber" {
        tags "Item"
    }
}

# Add relationships
customerCreatedEvent -> sendWelcomeEmailSubscriber "triggers"
sendWelcomeEmailSubscriber -> messageBroker "sends via"
```

### Scenario 5: Adding External Dependency

**Code change**: Integrated Elasticsearch for search

**workspace.dsl update**:

```dsl
group "Infrastructure" {
    searchIndex = component "Search Index" "Indexes searchable data" "Elasticsearch" {
        tags "Database"
    }
}

# Add relationships
customerRepository -> searchIndex "indexes to"
```

---

## Constraints (Parameters)

### NEVER

- Add every single class to the diagram (focus on architectural components)
- Include test classes in production architecture
- Document implementation details (private methods, internal state)
- Break DSL syntax (validate before committing)
- Create orphaned components without relationships
- Mix architectural layers (e.g., Domain component directly in Infrastructure group)

### ALWAYS

- Add components when they represent architectural decisions
- Group components by architectural layer (Application/Domain/Infrastructure)
- Use descriptive relationship labels
- Validate DSL syntax after changes
- Review generated diagrams for clarity
- Keep descriptions concise (1-2 sentences)
- Align with Deptrac layer definitions
- Document external dependencies explicitly

---

## Format (Output)

### Expected workspace.dsl Structure

```dsl
workspace {
    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }

        softwareSystem = softwareSystem "VilnaCRM" {
            webApplication = container "Core Service" {

                group "Application" {
                    # Controllers, Handlers
                }

                group "Domain" {
                    # Entities, Value Objects, Events, Interfaces
                }

                group "Infrastructure" {
                    # Repositories, Buses, Subscribers, Transformers
                }

                # External dependencies
                database = component "Database" ...
                cache = component "Cache" ...
                messageBroker = component "Message Broker" ...

                # Relationships
                component1 -> component2 "description"
            }
        }
    }

    views {
        component softwareSystem.webApplication "Components_All" {
            include *
        }

        styles {
            element "Item" {
                color white
                background #34abeb
            }
            element "Database" {
                color white
                shape cylinder
                background #34abeb
            }
        }
    }
}
```

---

## Best Practices

### 1. Start with High-Level Components

Focus on components that matter architecturally:

- Entry points (controllers, handlers)
- Core domain logic (entities, aggregates)
- Infrastructure adapters (repositories, external services)

### 2. Use Consistent Naming

Match DSL variable names to class names:

```dsl
# Good
customerCommandHandler = component "CustomerCommandHandler" ...

# Bad
handler1 = component "CustomerCommandHandler" ...
```

### 3. Document "Why" in Descriptions

```dsl
# Good
"Validates customer email format and domain restrictions"

# Less helpful
"Handles email"
```

### 4. Group Related Components

Use architectural layers to create visual groupings:

```dsl
group "Domain" {
    # All domain components together
}
```

### 5. Keep Relationships Directional

Show dependency flow clearly:

```dsl
# Good: Shows clear dependency direction
handler -> repository "uses"
repository -> database "persists to"

# Confusing: Bidirectional without context
handler -> repository
repository -> handler
```

### 6. Update Incrementally

Don't wait for major refactors - update `workspace.dsl` with each PR that changes architecture.

### 7. Review Generated Diagrams

Always visualize your changes:

- Use Structurizr Lite or Cloud
- Check for clarity and logical flow
- Ensure layer separation is visible

---

## Verification Checklist

After updating `workspace.dsl`:

- [ ] All new components documented with correct layer grouping
- [ ] Component types accurately reflect their purpose
- [ ] Descriptions are concise and meaningful
- [ ] All significant relationships added
- [ ] External dependencies explicitly defined
- [ ] DSL syntax is valid (no errors when parsing)
- [ ] Variable names match class names
- [ ] Tags applied consistently
- [ ] Layer groupings align with Deptrac configuration
- [ ] Generated diagram is clear and understandable
- [ ] No orphaned components
- [ ] Relationships use descriptive labels

---

## Integration with Other Skills

Use this skill **after** implementing changes with:

- [implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md) - After creating domain model
- [api-platform-crud](../api-platform-crud/SKILL.md) - After adding API endpoints
- [deptrac-fixer](../deptrac-fixer/SKILL.md) - After fixing layer violations (may reveal architecture drift)
- [database-migrations](../database-migrations/SKILL.md) - After adding entities

Use this skill **before**:

- [documentation-sync](../documentation-sync/SKILL.md) - Update architectural docs
- [ci-workflow](../ci-workflow/SKILL.md) - Validate all changes

---

## Quick Commands

```bash
# Validate DSL syntax (if Structurizr CLI installed)
structurizr-cli validate workspace.dsl

# Run Structurizr Lite locally
docker run -it --rm -p 8080:8080 \
  -v $(pwd):/usr/local/structurizr \
  structurizr/lite

# Access diagrams at http://localhost:8080

# View current architecture
cat workspace.dsl
```

---

## Reference Documentation

For detailed patterns and examples:

- **[C4 Model Fundamentals](reference/c4-model-guide.md)** - Understanding C4 modeling concepts
- **[DSL Syntax Reference](reference/dsl-syntax.md)** - Complete Structurizr DSL syntax guide
- **[Component Identification Guide](reference/component-identification.md)** - Determining what to document
- **[Relationship Patterns](reference/relationship-patterns.md)** - Common relationship types and descriptions

---

## Examples

Practical examples for common scenarios:

- **[Adding CQRS Pattern](examples/cqrs-pattern.md)** - Command handlers, events, subscribers
- **[Adding API Endpoint](examples/api-endpoint.md)** - Controllers, processors, transformers
- **[Adding Domain Entity](examples/domain-entity.md)** - Entities, value objects, factories
- **[Refactoring Components](examples/refactoring.md)** - Updating relationships during refactoring

---

## External Resources

- **Structurizr DSL Documentation**: https://docs.structurizr.com/dsl
- **C4 Model**: https://c4model.com/
- **Structurizr Lite**: https://structurizr.com/help/lite
- **Project Architecture**: See CLAUDE.md for hexagonal/DDD/CQRS patterns
