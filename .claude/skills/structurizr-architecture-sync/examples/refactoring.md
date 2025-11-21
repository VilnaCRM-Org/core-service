# Example: Refactoring Components

Complete example of updating Structurizr documentation during refactoring.

## Scenario

Refactoring: **Split CustomerCommandHandler into separate handlers** following Single Responsibility Principle.

### Before Refactoring

```php
// Single handler for all customer commands
class CustomerCommandHandler implements CommandHandlerInterface
{
    public function handleCreate(CreateCustomerCommand $command): void { }
    public function handleUpdate(UpdateCustomerCommand $command): void { }
    public function handleDelete(DeleteCustomerCommand $command): void { }
}
```

### After Refactoring

```php
// Separate handlers
class CreateCustomerCommandHandler implements CommandHandlerInterface { }
class UpdateCustomerCommandHandler implements CommandHandlerInterface { }
class DeleteCustomerCommandHandler implements CommandHandlerInterface { }
```

## Step 1: Review Current workspace.dsl

**Before**:

```dsl
group "Application" {
    customerCommandHandler = component "CustomerCommandHandler" "Handles all customer commands" "CommandHandler" {
        tags "Item"
    }
}

# Relationships
customerCommandHandler -> customer "creates / updates / deletes"
customerCommandHandler -> customerRepository "uses"
customerCommandHandler -> customerCreatedEvent "publishes"
customerCommandHandler -> customerUpdatedEvent "publishes"
customerCommandHandler -> customerDeletedEvent "publishes"
```

## Step 2: Identify Changes

**Components to add**:

- `CreateCustomerCommandHandler`
- `UpdateCustomerCommandHandler`
- `DeleteCustomerCommandHandler`

**Components to remove**:

- `CustomerCommandHandler` (replaced by specific handlers)

**Relationships to update**:

- Each handler has specific responsibilities
- Each handler publishes specific events

## Step 3: Update workspace.dsl

### Remove Old Component

```dsl
# DELETE this section
customerCommandHandler = component "CustomerCommandHandler" "Handles all customer commands" "CommandHandler" {
    tags "Item"
}
```

### Add New Components

```dsl
group "Application" {
    createCustomerHandler = component "CreateCustomerCommandHandler" "Handles customer creation" "CommandHandler" {
        tags "Item"
    }

    updateCustomerHandler = component "UpdateCustomerCommandHandler" "Handles customer updates" "CommandHandler" {
        tags "Item"
    }

    deleteCustomerHandler = component "DeleteCustomerCommandHandler" "Handles customer deletion" "CommandHandler" {
        tags "Item"
    }
}
```

### Update Relationships

**Remove old relationships**:

```dsl
# DELETE these relationships
customerCommandHandler -> customer "creates / updates / deletes"
customerCommandHandler -> customerRepository "uses"
customerCommandHandler -> customerCreatedEvent "publishes"
customerCommandHandler -> customerUpdatedEvent "publishes"
customerCommandHandler -> customerDeletedEvent "publishes"
```

**Add new specific relationships**:

```dsl
# CreateCustomerCommandHandler relationships
createCustomerHandler -> customer "creates"
createCustomerHandler -> customerRepository "uses for persistence"
createCustomerHandler -> customerCreatedEvent "publishes"

# UpdateCustomerCommandHandler relationships
updateCustomerHandler -> customer "updates"
updateCustomerHandler -> customerRepository "uses for persistence"
updateCustomerHandler -> customerUpdatedEvent "publishes"

# DeleteCustomerCommandHandler relationships
deleteCustomerHandler -> customer "deletes"
deleteCustomerHandler -> customerRepository "uses for persistence"
deleteCustomerHandler -> customerDeletedEvent "publishes"
```

## Complete Updated Section

```dsl
# Application Layer
group "Application" {
    createCustomerHandler = component "CreateCustomerCommandHandler" "Handles customer creation" "CommandHandler" {
        tags "Item"
    }

    updateCustomerHandler = component "UpdateCustomerCommandHandler" "Handles customer updates" "CommandHandler" {
        tags "Item"
    }

    deleteCustomerHandler = component "DeleteCustomerCommandHandler" "Handles customer deletion" "CommandHandler" {
        tags "Item"
    }
}

# Domain Layer (unchanged)
group "Domain" {
    customer = component "Customer" "Customer aggregate" "Entity" {
        tags "Item"
    }

    customerCreatedEvent = component "CustomerCreatedEvent" ...
    customerUpdatedEvent = component "CustomerUpdatedEvent" ...
    customerDeletedEvent = component "CustomerDeletedEvent" ...

    customerRepositoryInterface = component "CustomerRepositoryInterface" ...
}

# Infrastructure Layer (unchanged)
group "Infrastructure" {
    customerRepository = component "CustomerRepository" ...
}

# CreateCustomerCommandHandler flow
createCustomerHandler -> customer "creates"
createCustomerHandler -> customerRepositoryInterface "depends on"
createCustomerHandler -> customerCreatedEvent "publishes"

# UpdateCustomerCommandHandler flow
updateCustomerHandler -> customer "updates"
updateCustomerHandler -> customerRepositoryInterface "depends on"
updateCustomerHandler -> customerUpdatedEvent "publishes"

# DeleteCustomerCommandHandler flow
deleteCustomerHandler -> customer "deletes"
deleteCustomerHandler -> customerRepositoryInterface "depends on"
deleteCustomerHandler -> customerDeletedEvent "publishes"

# Infrastructure (unchanged)
customerRepository -> customerRepositoryInterface "implements"
customerRepository -> customer "stores / retrieves"
customerRepository -> database "persists to"
```

## Visual Result

**Before**: Single CustomerCommandHandler with multiple responsibilities

**After**: Three handlers, each with clear single responsibility:

1. CreateCustomerCommandHandler → Creates → Publishes CustomerCreatedEvent
2. UpdateCustomerCommandHandler → Updates → Publishes CustomerUpdatedEvent
3. DeleteCustomerCommandHandler → Deletes → Publishes CustomerDeletedEvent

## Example 2: Extracting Service

**Before**: Handler contains complex business logic

```dsl
createCustomerHandler = component "CreateCustomerCommandHandler" "Handles customer creation with validation and pricing" "CommandHandler" {
    tags "Item"
}
```

**After**: Extracted domain service

```dsl
# Application layer
createCustomerHandler = component "CreateCustomerCommandHandler" "Handles customer creation" "CommandHandler" {
    tags "Item"
}

# Domain layer
customerPricingService = component "CustomerPricingService" "Calculates customer pricing tiers" "DomainService" {
    tags "Item"
}

# Relationship
createCustomerHandler -> customerPricingService "uses for pricing calculation"
customerPricingService -> customer "applies pricing to"
```

## Example 3: Moving Component Between Layers

**Before**: Validator in Infrastructure layer (Deptrac violation)

```dsl
group "Infrastructure" {
    emailValidator = component "EmailValidator" "Validates email format" "Validator" {
        tags "Item"
    }
}
```

**After**: Moved to Domain layer (correct layer)

```dsl
group "Domain" {
    emailValidator = component "EmailValidator" "Validates email format" "Validator" {
        tags "Item"
    }
}
```

**Update relationships** (if any reference this component):

```dsl
# No changes to relationships if variable name stays same
customerEmail -> emailValidator "validates via"
```

## Example 4: Introducing Interface (Hexagonal Architecture)

**Before**: Direct dependency on implementation

```dsl
# Handler depends directly on repository
createCustomerHandler -> customerRepository "uses"
```

**After**: Dependency on interface (port)

```dsl
# Add interface to Domain layer
group "Domain" {
    customerRepositoryInterface = component "CustomerRepositoryInterface" "Repository port" "Interface" {
        tags "Item"
    }
}

# Handler depends on interface
createCustomerHandler -> customerRepositoryInterface "depends on"

# Repository implements interface
customerRepository -> customerRepositoryInterface "implements"
```

## Example 5: Renaming Component

**Before**:

```dsl
customerHandler = component "CustomerHandler" ...

# Relationships
controller -> customerHandler "uses"
```

**After**:

```dsl
customerCommandHandler = component "CustomerCommandHandler" ...

# Update relationships
controller -> customerCommandHandler "uses"
```

**Important**: Update both component definition AND all relationships.

## Verification Checklist

After refactoring:

- [ ] Old components removed
- [ ] New components added with correct layer grouping
- [ ] Component types accurate
- [ ] Descriptions updated
- [ ] All old relationships removed
- [ ] All new relationships added
- [ ] Variable names updated in relationships
- [ ] No orphaned components (components without relationships)
- [ ] No broken relationships (referencing deleted components)
- [ ] DSL syntax valid
- [ ] Diagram generated successfully
- [ ] Layer boundaries respected

## Common Refactoring Patterns

### Pattern 1: Split Handler

**When**: Handler has multiple responsibilities

**Action**: Create separate handlers for each responsibility

**Update**: Replace one component with multiple, update relationships

### Pattern 2: Extract Service

**When**: Handler contains complex business logic

**Action**: Extract domain service

**Update**: Add domain service component, add handler → service relationship

### Pattern 3: Extract Value Object

**When**: Primitive obsession in entity

**Action**: Create value object

**Update**: Add value object to domain, add entity → value object relationship

### Pattern 4: Move Component to Correct Layer

**When**: Deptrac violation

**Action**: Move component to correct layer group

**Update**: Change group in workspace.dsl, verify relationships still valid

### Pattern 5: Introduce Interface

**When**: Violating dependency inversion principle

**Action**: Create interface in domain, implement in infrastructure

**Update**: Add interface component, change dependency relationships

## Automation Tips

### Use Version Control

**Before refactoring**:

```bash
git checkout -b refactor/split-customer-handler
```

**Update workspace.dsl in same commit**:

```bash
git add src/Customer/Application/CommandHandler/CreateCustomerCommandHandler.php
git add src/Customer/Application/CommandHandler/UpdateCustomerCommandHandler.php
git add src/Customer/Application/CommandHandler/DeleteCustomerCommandHandler.php
git add workspace.dsl
git commit -m "refactor: split CustomerCommandHandler into separate handlers"
```

### Review Changes

**Diff workspace.dsl**:

```bash
git diff workspace.dsl
```

**Verify**:

- Components removed
- Components added
- Relationships updated

## Common Mistakes

### Mistake 1: Forgetting to Remove Old Component

**Problem**: Old component still in workspace.dsl after refactoring

**Solution**: Explicitly remove old component definition and relationships

### Mistake 2: Forgetting to Update Relationships

**Problem**: Relationships still reference old component variable name

**Solution**: Search workspace.dsl for old variable name, update all occurrences

### Mistake 3: Creating Orphaned Components

**Problem**: New component added but no relationships

**Solution**: Always add relationships when adding components

### Mistake 4: Breaking Relationships

**Problem**: Deleted component but relationships still reference it

**Solution**: Search for component variable name before deleting

### Mistake 5: Inconsistent Variable Names

**Problem**: Component variable name doesn't match new class name

**Solution**: Use consistent camelCase matching class name

## Next Steps

After refactoring components:

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

3. **Review refactoring visually**: Ensure new structure is clearer

4. **Run Deptrac**: Verify layer boundaries respected

   ```bash
   make deptrac
   ```

5. **Update documentation**: Use [documentation-sync](../../documentation-sync/SKILL.md) skill

6. **Run CI checks**: Use [ci-workflow](../../ci-workflow/SKILL.md) skill

7. **Commit changes**: Include workspace.dsl in same commit as code changes
