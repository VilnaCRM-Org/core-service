# Example: Adding CQRS Pattern

Complete example of documenting a CQRS command flow in Structurizr.

## Scenario

Implementing a new feature: **Create Customer** using CQRS pattern.

### Components Implemented

**Application Layer**:

- `CreateCustomerCommand` (command)
- `CreateCustomerCommandHandler` (handler)

**Domain Layer**:

- `Customer` (entity/aggregate)
- `CustomerId` (value object)
- `CustomerEmail` (value object)
- `CustomerName` (value object)
- `CustomerCreatedEvent` (domain event)
- `CustomerRepositoryInterface` (port)

**Infrastructure Layer**:

- `CustomerRepository` (adapter)
- `SendWelcomeEmailSubscriber` (event subscriber)
- `InMemorySymfonyEventBus` (event bus)

## Step 1: Add Application Layer Components

```dsl
group "Application" {
    createCustomerCommandHandler = component "CreateCustomerCommandHandler" "Handles customer creation commands" "CommandHandler" {
        tags "Item"
    }
}
```

**Note**: We don't add `CreateCustomerCommand` itself as it's just a data structure (DTO pattern).

## Step 2: Add Domain Layer Components

```dsl
group "Domain" {
    customer = component "Customer" "Customer aggregate root" "Aggregate" {
        tags "Item"
    }

    customerId = component "CustomerId" "Customer unique identifier" "ValueObject" {
        tags "Item"
    }

    customerEmail = component "CustomerEmail" "Customer email with validation" "ValueObject" {
        tags "Item"
    }

    customerName = component "CustomerName" "Customer full name" "ValueObject" {
        tags "Item"
    }

    customerCreatedEvent = component "CustomerCreatedEvent" "Event published when customer is created" "DomainEvent" {
        tags "Item"
    }

    customerRepositoryInterface = component "CustomerRepositoryInterface" "Contract for customer persistence" "Interface" {
        tags "Item"
    }
}
```

## Step 3: Add Infrastructure Layer Components

```dsl
group "Infrastructure" {
    customerRepository = component "CustomerRepository" "MongoDB implementation of customer persistence" "Repository" {
        tags "Item"
    }

    sendWelcomeEmailSubscriber = component "SendWelcomeEmailSubscriber" "Sends welcome email on customer creation" "EventSubscriber" {
        tags "Item"
    }

    inMemorySymfonyEventBus = component "InMemorySymfonyEventBus" "Handles event publishing" "EventBus" {
        tags "Item"
    }
}
```

## Step 4: Add External Dependencies (if not already present)

```dsl
database = component "Database" "MongoDB instance" "MongoDB" {
    tags "Database"
}

messageBroker = component "Message Broker" "AWS SQS for async messaging" "AWS SQS" {
    tags "Database"
}
```

## Step 5: Add Relationships

### Command Flow

```dsl
# Handler creates aggregate
createCustomerCommandHandler -> customer "creates"

# Handler depends on repository interface (hexagonal port)
createCustomerCommandHandler -> customerRepositoryInterface "depends on"

# Handler publishes event
createCustomerCommandHandler -> customerCreatedEvent "publishes"
```

### Domain Model Relationships

```dsl
# Customer aggregate has value objects
customer -> customerId "has"
customer -> customerEmail "has"
customer -> customerName "has"

# Customer aggregate creates event
customer -> customerCreatedEvent "creates"
```

### Infrastructure Relationships

```dsl
# Repository implements interface
customerRepository -> customerRepositoryInterface "implements"

# Repository stores customer
customerRepository -> customer "stores / retrieves"

# Repository persists to database
customerRepository -> database "persists to"
```

### Event Flow Relationships

```dsl
# Event triggers subscriber
customerCreatedEvent -> sendWelcomeEmailSubscriber "triggers"

# Subscriber uses event bus
sendWelcomeEmailSubscriber -> inMemorySymfonyEventBus "uses"

# Subscriber sends message via broker
sendWelcomeEmailSubscriber -> messageBroker "sends via"
```

## Complete workspace.dsl Section

```dsl
workspace {
    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }

        softwareSystem = softwareSystem "VilnaCRM" {
            coreService = container "Core Service" {

                group "Application" {
                    createCustomerCommandHandler = component "CreateCustomerCommandHandler" "Handles customer creation commands" "CommandHandler" {
                        tags "Item"
                    }
                }

                group "Domain" {
                    customer = component "Customer" "Customer aggregate root" "Aggregate" {
                        tags "Item"
                    }
                    customerId = component "CustomerId" "Customer unique identifier" "ValueObject" {
                        tags "Item"
                    }
                    customerEmail = component "CustomerEmail" "Customer email with validation" "ValueObject" {
                        tags "Item"
                    }
                    customerName = component "CustomerName" "Customer full name" "ValueObject" {
                        tags "Item"
                    }
                    customerCreatedEvent = component "CustomerCreatedEvent" "Event published when customer is created" "DomainEvent" {
                        tags "Item"
                    }
                    customerRepositoryInterface = component "CustomerRepositoryInterface" "Contract for customer persistence" "Interface" {
                        tags "Item"
                    }
                }

                group "Infrastructure" {
                    customerRepository = component "CustomerRepository" "MongoDB implementation of customer persistence" "Repository" {
                        tags "Item"
                    }
                    sendWelcomeEmailSubscriber = component "SendWelcomeEmailSubscriber" "Sends welcome email on customer creation" "EventSubscriber" {
                        tags "Item"
                    }
                    inMemorySymfonyEventBus = component "InMemorySymfonyEventBus" "Handles event publishing" "EventBus" {
                        tags "Item"
                    }
                }

                database = component "Database" "MongoDB instance" "MongoDB" {
                    tags "Database"
                }
                messageBroker = component "Message Broker" "AWS SQS for async messaging" "AWS SQS" {
                    tags "Database"
                }

                # Command flow
                createCustomerCommandHandler -> customer "creates"
                createCustomerCommandHandler -> customerRepositoryInterface "depends on"
                createCustomerCommandHandler -> customerCreatedEvent "publishes"

                # Domain model
                customer -> customerId "has"
                customer -> customerEmail "has"
                customer -> customerName "has"
                customer -> customerCreatedEvent "creates"

                # Infrastructure implementation
                customerRepository -> customerRepositoryInterface "implements"
                customerRepository -> customer "stores / retrieves"
                customerRepository -> database "persists to"

                # Event flow
                customerCreatedEvent -> sendWelcomeEmailSubscriber "triggers"
                sendWelcomeEmailSubscriber -> inMemorySymfonyEventBus "uses"
                sendWelcomeEmailSubscriber -> messageBroker "sends via"
            }
        }
    }

    views {
        component softwareSystem.coreService "Components_All" {
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

## Visual Result

The generated diagram will show:

1. **Application Layer** (top):

   - CreateCustomerCommandHandler

2. **Domain Layer** (middle):

   - Customer aggregate with value objects
   - CustomerCreatedEvent
   - CustomerRepositoryInterface (port)

3. **Infrastructure Layer** (bottom):

   - CustomerRepository implementing the interface
   - SendWelcomeEmailSubscriber
   - InMemorySymfonyEventBus

4. **External Systems**:

   - Database (MongoDB)
   - Message Broker (AWS SQS)

5. **Flow**:
   - Handler → Creates Customer → Uses Repository Interface
   - Handler → Publishes CustomerCreatedEvent
   - Event → Triggers Subscriber
   - Subscriber → Sends via Message Broker
   - Repository → Implements Interface
   - Repository → Persists to Database

## Verification Checklist

- [x] All command handler components documented
- [x] Domain model (entity + value objects) documented
- [x] Domain event documented
- [x] Repository interface (port) documented
- [x] Repository implementation (adapter) documented
- [x] Event subscriber documented
- [x] External dependencies (database, broker) documented
- [x] Command flow relationships clear
- [x] Domain model relationships clear
- [x] Infrastructure implementation relationships clear
- [x] Event flow relationships clear
- [x] Hexagonal architecture visible (port/adapter pattern)
- [x] Layer groupings correct
- [x] No DTOs included (CreateCustomerCommand omitted)

## Common Questions

### Q: Should I include CreateCustomerCommand?

**A**: No. Commands are data structures (DTOs) without behavior. They are not architecturally significant components.

### Q: Should I include all value objects?

**A**: Include value objects with significant business logic (validation, formatting). For simple wrapper value objects (like `FirstName`, `LastName`), consider omitting to reduce clutter.

### Q: How do I show that the handler uses the repository?

**A**: Show dependency on the repository **interface** (port), not the implementation. This highlights hexagonal architecture:

```dsl
createCustomerCommandHandler -> customerRepositoryInterface "depends on"
customerRepository -> customerRepositoryInterface "implements"
```

### Q: Should I show the event bus explicitly?

**A**: Yes, if it's a significant infrastructure component. It shows how events are distributed.

### Q: What if I have multiple subscribers for the same event?

**A**: Show all subscriber relationships:

```dsl
customerCreatedEvent -> sendWelcomeEmailSubscriber "triggers"
customerCreatedEvent -> updateAnalyticsSubscriber "triggers"
customerCreatedEvent -> logAuditSubscriber "triggers"
```

## Next Steps

After documenting the CQRS pattern:

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

3. **Review visually**: Check http://localhost:8080

4. **Update documentation**: Use [documentation-sync](../../documentation-sync/SKILL.md) skill

5. **Run CI checks**: Use [ci-workflow](../../ci-workflow/SKILL.md) skill
