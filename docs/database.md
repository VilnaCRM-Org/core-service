# Database

Core Service persists customer-domain data in MongoDB through Doctrine MongoDB ODM. Mapping files live in `config/doctrine/`, and repository implementations live under the owning bounded context in `src/Core/Customer/Infrastructure/Repository/`.

## Document Ownership

| Domain concept  | Mapping file                                 | Owning context  | Purpose                                         |
| --------------- | -------------------------------------------- | --------------- | ----------------------------------------------- |
| Customer        | `config/doctrine/Customer.mongodb.xml`       | `Core/Customer` | Main customer aggregate and contact fields      |
| Customer type   | `config/doctrine/CustomerType.mongodb.xml`   | `Core/Customer` | Classification values referenced by customers   |
| Customer status | `config/doctrine/CustomerStatus.mongodb.xml` | `Core/Customer` | Lifecycle status values referenced by customers |

The `Shared` and `Internal/HealthCheck` contexts do not own MongoDB collections directly. Shared classes provide cross-cutting abstractions, and health checks verify connectivity.

## Schema Source of Truth

Doctrine ODM XML mappings are the source of truth for persisted fields, identifiers, indexes, embedded references, and lifecycle timestamps. Do not duplicate persistence rules in controllers or API Platform resources.

When changing persistence behavior:

1. Update the mapped domain entity and its XML mapping together.
2. When API-visible fields change, update validators and serialization metadata.
3. Adjust OpenAPI/GraphQL expectations to reflect API contract changes.
4. Revise this document for changes to collections, relations, indexes, or ownership rules.
5. Add or adjust tests that exercise repository and API behavior.

## Identifiers

The service uses ULID-based identifiers in the domain model. API resources expose identifiers through API Platform IRIs such as `/api/customers/{id}`.

Keep identifier conversion at the application and infrastructure boundaries. Domain code should depend on typed value objects rather than raw request strings.

## Indexing

Indexes should be declared in Doctrine ODM mappings when query behavior requires stable performance. Existing query surfaces include customer collection reads, filtering, sorting, and lookup by related customer type or status.

Before adding a new filter or sort option:

- confirm the query is part of the API contract
- add or reuse an index that supports the expected access pattern
- add load or API tests if the query is user-facing and performance-sensitive

## Local Schema Commands

For local and test environments, schema setup is wrapped by Make targets:

```bash
make setup-test-db
```

The target starts required services, clears the test cache, drops the test schema, and recreates it with tolerance for already-existing collections.

Do not use test schema reset commands against shared or production databases.

## Data Safety

Customer records can contain personal or business contact data. Treat database dumps, fixtures, logs, and test artifacts as sensitive unless they are explicitly synthetic.

Operational data-handling expectations are documented in [security.md](security.md) and [operational.md](operational.md).
