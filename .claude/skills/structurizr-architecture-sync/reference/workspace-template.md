# Complete workspace.dsl Template

This template shows the complete structure of a workspace.dsl file following the user-service pattern.

## Full Template

```dsl
workspace {

    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }

        softwareSystem = softwareSystem "VilnaCRM" {
            serviceName = container "Service Name" {

                group "Application" {
                    // Processors (HTTP/GraphQL handlers)
                    createEntityProcessor = component "CreateEntityProcessor" "Processes HTTP requests for entity creation" "RequestProcessor" {
                        tags "Item"
                    }
                    entityPatchProcessor = component "EntityPatchProcessor" "Processes HTTP requests for entity updates" "RequestProcessor" {
                        tags "Item"
                    }
                    entityPutProcessor = component "EntityPutProcessor" "Processes HTTP requests for entity replacement" "RequestProcessor" {
                        tags "Item"
                    }

                    // Command Handlers (CQRS)
                    createEntityCommandHandler = component "CreateEntityCommandHandler" "Handles CreateEntityCommand" "CommandHandler" {
                        tags "Item"
                    }
                    updateEntityCommandHandler = component "UpdateEntityCommandHandler" "Handles UpdateEntityCommand" "CommandHandler" {
                        tags "Item"
                    }

                    // Event Subscribers
                    entityCreatedSubscriber = component "EntityCreatedSubscriber" "Handles EntityCreatedEvent" "EventSubscriber" {
                        tags "Item"
                    }
                    entityUpdatedSubscriber = component "EntityUpdatedSubscriber" "Handles EntityUpdatedEvent" "EventSubscriber" {
                        tags "Item"
                    }

                    // Controllers (for non-CRUD operations)
                    healthCheckController = component "HealthCheckController" "Handles health check requests" "Controller" {
                        tags "Item"
                    }
                }

                group "Domain" {
                    // Entities
                    entity = component "Entity" "Represents the main entity" "Entity" {
                        tags "Item"
                    }
                    relatedEntity = component "RelatedEntity" "Represents a related entity" "Entity" {
                        tags "Item"
                    }

                    // Domain Events
                    entityCreatedEvent = component "EntityCreatedEvent" "Represents entity creation event" "DomainEvent" {
                        tags "Item"
                    }
                    entityUpdatedEvent = component "EntityUpdatedEvent" "Represents entity update event" "DomainEvent" {
                        tags "Item"
                    }
                }

                group "Infrastructure" {
                    // Repositories
                    entityRepository = component "EntityRepository" "Manages access to entities" "Repository" {
                        tags "Item"
                    }
                    relatedEntityRepository = component "RelatedEntityRepository" "Manages access to related entities" "Repository" {
                        tags "Item"
                    }

                    // Infrastructure Services
                    eventBus = component "EventBus" "Handles event publishing" "EventBus" {
                        tags "Item"
                    }
                    mailer = component "Mailer" "Manages sending emails" "MailService" {
                        tags "Item"
                    }
                }

                // External Dependencies (OUTSIDE groups)
                database = component "Database" "Stores application data" "MongoDB" {
                    tags "Database"
                }
                cache = component "Cache" "Caches application data" "Redis" {
                    tags "Database"
                }
                messageBroker = component "Message Broker" "Handles asynchronous messaging" "AWS SQS" {
                    tags "Database"
                }

                // Relationships - Processor → Handler Flow
                createEntityProcessor -> createEntityCommandHandler "dispatches CreateEntityCommand"
                entityPatchProcessor -> updateEntityCommandHandler "dispatches UpdateEntityCommand"
                entityPutProcessor -> updateEntityCommandHandler "dispatches UpdateEntityCommand"

                // Relationships - Handler → Entity → Repository
                createEntityCommandHandler -> entity "creates"
                updateEntityCommandHandler -> entity "updates"
                createEntityCommandHandler -> entityRepository "persists via"
                updateEntityCommandHandler -> entityRepository "uses"

                // Relationships - Repository → Database
                entityRepository -> entity "save and load"
                entityRepository -> database "accesses data"
                relatedEntityRepository -> relatedEntity "save and load"
                relatedEntityRepository -> database "accesses data"

                // Relationships - Event Flow
                createEntityCommandHandler -> entityCreatedEvent "publishes"
                updateEntityCommandHandler -> entityUpdatedEvent "publishes"
                entityCreatedEvent -> entityCreatedSubscriber "triggers"
                entityUpdatedEvent -> entityUpdatedSubscriber "triggers"

                // Relationships - Subscriber → External Services
                entityCreatedSubscriber -> messageBroker "sends to"
                entityUpdatedSubscriber -> mailer "sends email via"

                // Relationships - Health Check
                healthCheckController -> eventBus "publishes via"
                eventBus -> entityCreatedEvent "dispatches"
            }
        }
    }

    views {
        component softwareSystem.serviceName "Components_All" {
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

## Key Structure Points

### 1. Header

```dsl
workspace {
    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }
```

- `!identifiers hierarchical` - Use hierarchical identifiers
- `groupSeparator "/"` - Use forward slash for group separation

### 2. Software System and Container

```dsl
softwareSystem = softwareSystem "VilnaCRM" {
    serviceName = container "Service Name" {
        // Components go here
    }
}
```

- Software system name: "VilnaCRM"
- Container name: Your service name (e.g., "Core Service", "User Service")

### 3. Layer Groups

**Three groups in this order**:

```dsl
group "Application" { ... }
group "Domain" { ... }
group "Infrastructure" { ... }
```

**Component placement**:

- **Application**: Processors, Handlers, Subscribers, Controllers
- **Domain**: Entities, Domain Events
- **Infrastructure**: Repositories, Event Bus, Infrastructure services

### 4. External Dependencies

Place **OUTSIDE any group**, after all groups:

```dsl
database = component "Database" "..." "MongoDB" {
    tags "Database"
}
cache = component "Cache" "..." "Redis" {
    tags "Database"
}
messageBroker = component "Message Broker" "..." "AWS SQS" {
    tags "Database"
}
```

### 5. Relationships Section

Place **AFTER** all component definitions:

```dsl
// Processor → Handler
processor -> handler "dispatches XCommand"

// Handler → Entity → Repository
handler -> entity "creates/updates"
handler -> repository "uses"

// Repository → Database
repository -> entity "save and load"
repository -> database "accesses data"

// Event Flow
handler -> event "publishes"
event -> subscriber "triggers"
subscriber -> messageBroker "sends to"
```

### 6. Views and Styles

```dsl
views {
    component softwareSystem.serviceName "Components_All" {
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
```

**DO NOT add**:

- Multiple views (use single `Components_All`)
- `autolayout` directive (position manually in UI)

## Adapting This Template

### For Your Service

1. **Replace service name**:

   ```dsl
   serviceName = container "Core Service" {  // Your service name
   ```

2. **Add your components** in appropriate groups:

   - List all processors
   - List all command handlers
   - List all event subscribers
   - List your entities
   - List your repositories

3. **Define relationships**:

   - Start with processor → handler flows
   - Add handler → entity → repository chains
   - Add event flows if using events

4. **Keep it focused**:
   - Target 15-25 components
   - Focus on architectural significance
   - Omit factories, transformers, value objects

## Real Examples

- **Core Service**: See `/workspace.dsl` in project root
- **User Service** (VilnaCRM organization reference): <https://github.com/VilnaCRM-Org/user-service/blob/main/workspace.dsl>

## Component Counts by Service

| Service      | Components | Notes               |
| ------------ | ---------- | ------------------- |
| User Service | 23         | Good balance        |
| Core Service | 21         | Clean and focused   |
| Target Range | 15-25      | Optimal for clarity |

## Next Steps

1. Copy this template
2. Replace placeholder names with your components
3. Verify syntax: Check <http://localhost:8080> for errors
4. Position components in UI and save
5. Commit both workspace.dsl and workspace.json
