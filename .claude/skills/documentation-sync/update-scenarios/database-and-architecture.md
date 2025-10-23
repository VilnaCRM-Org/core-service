# Database and Architecture Documentation

## Database Schema Changes

**When adding/modifying entities**:

**Update**: `docs/design-and-architecture.md`

1. Entity relationships
2. New fields and their purpose
3. Migration notes

**Example**:
```markdown
#### Customer Entity

- `id`: ULID (primary key)
- `email`: Unique, indexed
- `type`: Reference to CustomerType (IRI)
- `status`: Reference to CustomerStatus (IRI)
```

**Update**: `docs/developer-guide.md` with repository usage patterns

## Domain Model Changes

**When implementing new domain features**:

**Update**: `docs/design-and-architecture.md`

1. **Aggregates**: New domain aggregates
2. **Commands**: Command handlers
3. **Events**: Domain events
4. **Bounded Contexts**: Context interactions

**Update**: `docs/glossary.md` with new domain terms

**Example**:
```markdown
## Customer Management Context

### Aggregates
- **Customer**: Root aggregate for customer data

### Commands
- `CreateCustomerCommand`: Create new customer
- `UpdateCustomerCommand`: Update customer details

### Events
- `CustomerCreatedEvent`: Emitted when customer created
- `CustomerUpdatedEvent`: Emitted when customer updated
```

## Architecture Pattern Changes

**When changing patterns or structure**:

**Update**: `docs/design-and-architecture.md`

1. Pattern description
2. Implementation examples
3. Benefits and trade-offs
4. Migration path from old pattern
