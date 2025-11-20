# Structurizr DSL Syntax Reference

Complete guide to the Structurizr DSL syntax used in this project.

## Basic Structure

```dsl
workspace {
    !identifiers hierarchical

    model {
        # Define architecture model
    }

    views {
        # Define views and diagrams
    }
}
```

## Identifiers

```dsl
!identifiers hierarchical
```

**Meaning**: Use hierarchical identifiers for components (e.g., `softwareSystem.webApplication.componentName`)

**Benefit**: Clearer references, better organization

## Model Section

### Software System

```dsl
softwareSystem = softwareSystem "SystemName" {
    # Containers and components
}
```

**Example**:

```dsl
softwareSystem = softwareSystem "VilnaCRM" {
    # ...
}
```

### Container

```dsl
container = container "ContainerName" {
    # Components
}
```

**Example**:

```dsl
webApplication = container "Core Service" {
    # Components go here
}
```

### Component

```dsl
variableName = component "DisplayName" "Description" "Technology/Type" {
    tags "Tag1" "Tag2"
}
```

**Parameters**:

- `variableName`: Identifier used in relationships (camelCase)
- `DisplayName`: Name shown in diagrams (PascalCase, usually class name)
- `Description`: Brief explanation of purpose
- `Technology/Type`: Component type (e.g., "CommandHandler", "Entity", "MongoDB")
- `tags`: Visual styling tags

**Example**:

```dsl
customerHandler = component "CustomerCommandHandler" "Handles customer commands" "CommandHandler" {
    tags "Item"
}
```

### Groups

```dsl
group "GroupName" {
    # Components in this group
}
```

**Purpose**: Visually group related components (e.g., by architectural layer)

**Example**:

```dsl
group "Application" {
    controller = component "HealthCheckController" ...
    handler = component "CustomerCommandHandler" ...
}

group "Domain" {
    entity = component "Customer" ...
}

group "Infrastructure" {
    repository = component "CustomerRepository" ...
}
```

### Relationships

```dsl
source -> destination "description"
```

**Parameters**:

- `source`: Source component variable name
- `destination`: Destination component variable name
- `description`: Relationship description (verb phrase)

**Examples**:

```dsl
# Simple relationship
handler -> repository "uses"

# Detailed relationship
handler -> repository "uses for customer persistence"

# Chained relationships
controller -> event "creates"
event -> subscriber "triggers"
subscriber -> database "checks"
```

### Properties

```dsl
properties {
    "propertyName" "value"
}
```

**Example**:

```dsl
properties {
    "structurizr.groupSeparator" "/"
}
```

**Common properties**:

- `structurizr.groupSeparator`: Character used to separate nested group names (default: `/`)

## Views Section

### Component View

```dsl
component <container> "ViewKey" {
    include <elements>
    exclude <elements>
    autoLayout <direction>
    description "View description"
}
```

**Parameters**:

- `<container>`: Container to show components from
- `ViewKey`: Unique identifier for this view
- `include`: Elements to include (`*` for all)
- `exclude`: Elements to exclude
- `autoLayout`: Automatic layout direction (tb, bt, lr, rl)
- `description`: Human-readable description

**Example**:

```dsl
component softwareSystem.webApplication "Components_All" {
    include *
    description "All components in the Core Service"
}
```

### Styles

```dsl
styles {
    element "Tag" {
        <style-properties>
    }
}
```

**Style Properties**:

- `color`: Text color
- `background`: Background color
- `shape`: Component shape
- `fontSize`: Font size in pixels
- `border`: Border style

**Available Shapes**:

- `Box` (default)
- `RoundedBox`
- `Circle`
- `Ellipse`
- `Hexagon`
- `Cylinder` (for databases)
- `Component` (UML component shape)
- `Person` (stick figure)
- `Robot`
- `Folder`
- `WebBrowser`
- `MobileDevicePortrait`
- `MobileDeviceLandscape`

**Example**:

```dsl
styles {
    element "Item" {
        color white
        background #34abeb
        shape Box
    }

    element "Database" {
        color white
        background #34abeb
        shape Cylinder
    }

    element "Important" {
        background #ff0000
        border solid
        fontSize 24
    }
}
```

## Tags

Tags apply styles to components:

```dsl
component "Name" "Description" "Type" {
    tags "Tag1" "Tag2"
}
```

**Common tags**:

- `"Item"`: Standard component styling
- `"Database"`: Database/external service styling
- Custom tags for special styling

**Example**:

```dsl
database = component "Database" "MongoDB instance" "MongoDB" {
    tags "Database"
}

criticalHandler = component "CriticalHandler" "Critical business logic" "Handler" {
    tags "Item" "Critical"
}
```

## Comments

```dsl
# This is a single-line comment

/*
This is a
multi-line comment
*/
```

## Variable Naming Conventions

**For components**:

- Use camelCase: `customerCommandHandler`, `customerRepository`
- Match class name: If class is `CustomerCommandHandler`, use `customerCommandHandler`
- Be descriptive: Avoid generic names like `handler1`, `component1`

**For relationships**:

- Use descriptive verbs: `uses`, `creates`, `triggers`, `implements`, `persists to`
- Add context when helpful: `uses for validation`, `stores/retrieves`

## Complete Example

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
                    customerController = component "CustomerController" "Handles customer API requests" "Controller" {
                        tags "Item"
                    }
                    customerCommandHandler = component "CustomerCommandHandler" "Handles customer commands" "CommandHandler" {
                        tags "Item"
                    }
                }

                group "Domain" {
                    customer = component "Customer" "Customer aggregate" "Entity" {
                        tags "Item"
                    }
                    customerCreatedEvent = component "CustomerCreatedEvent" "Event published on customer creation" "DomainEvent" {
                        tags "Item"
                    }
                }

                group "Infrastructure" {
                    customerRepository = component "CustomerRepository" "Persists customers to MongoDB" "Repository" {
                        tags "Item"
                    }
                    emailSubscriber = component "SendWelcomeEmailSubscriber" "Sends welcome email" "EventSubscriber" {
                        tags "Item"
                    }
                }

                database = component "Database" "MongoDB instance" "MongoDB" {
                    tags "Database"
                }
                messageBroker = component "Message Broker" "AWS SQS" "AWS SQS" {
                    tags "Database"
                }

                # Relationships
                customerController -> customerCommandHandler "dispatches commands to"
                customerCommandHandler -> customer "creates"
                customerCommandHandler -> customerRepository "uses"
                customerCommandHandler -> customerCreatedEvent "publishes"
                customerRepository -> database "persists to"
                customerCreatedEvent -> emailSubscriber "triggers"
                emailSubscriber -> messageBroker "sends via"
            }
        }
    }

    views {
        component softwareSystem.webApplication "Components_All" {
            include *
            description "All components within the Core Service"
        }

        styles {
            element "Item" {
                color white
                background #34abeb
            }
            element "Database" {
                color white
                shape Cylinder
                background #34abeb
            }
        }
    }
}
```

## Common Patterns

### Pattern 1: Hierarchical Component Structure

```dsl
group "Layer" {
    group "SubLayer" {
        component ...
    }
}
```

**Use when**: You have multiple sub-layers within a layer

### Pattern 2: Shared Dependencies

```dsl
# Multiple components using same dependency
handler1 -> repository "uses"
handler2 -> repository "uses"
handler3 -> repository "uses"
```

### Pattern 3: Event Flow

```dsl
# Event sourcing pattern
aggregate -> event "records"
event -> subscriber1 "triggers"
event -> subscriber2 "triggers"
event -> subscriber3 "triggers"
```

### Pattern 4: Interface Implementation

```dsl
group "Domain" {
    repositoryInterface = component "CustomerRepositoryInterface" "Repository contract" "Interface" {
        tags "Item"
    }
}

group "Infrastructure" {
    repository = component "CustomerRepository" "MongoDB implementation" "Repository" {
        tags "Item"
    }
}

repository -> repositoryInterface "implements"
handler -> repositoryInterface "depends on"
```

## Validation

### Syntax Validation

```bash
# Using Structurizr CLI
structurizr-cli validate workspace.dsl
```

### Common Syntax Errors

**Error**: `Component 'xyz' not found`

**Cause**: Relationship references undefined component variable

**Fix**: Define component before referencing in relationships

---

**Error**: `Duplicate identifier`

**Cause**: Two components with same variable name

**Fix**: Use unique variable names

---

**Error**: `Unexpected token`

**Cause**: Missing closing brace, incorrect syntax

**Fix**: Check for balanced braces, correct keyword spelling

## Best Practices

1. **Indent consistently**: Use 4 spaces per level
2. **Group related components**: Use architectural layers
3. **Comment complex relationships**: Explain non-obvious dependencies
4. **Order components logically**: Entry points first, then domain, then infrastructure
5. **Use hierarchical identifiers**: Easier to reference and understand
6. **Validate frequently**: Check syntax after each change
7. **Keep descriptions concise**: 1-2 sentences maximum
8. **Use consistent naming**: Match class names from codebase

## External Resources

- **Structurizr DSL Documentation**: https://docs.structurizr.com/dsl
- **Language Reference**: https://github.com/structurizr/dsl/blob/master/docs/language-reference.md
- **Cookbook**: https://github.com/structurizr/dsl/blob/master/docs/cookbook/README.md
