# Glossary

This glossary page aims to explain the naming conventions used for classes within the Domain layer of the `core-service` project. Understanding these conventions will help contributors and developers navigate and contribute to the project more effectively.

## General Naming Conventions

- **Aggregate**: Suffix is used to denote classes aggregated in the domain-driven design context. Aggregates are clusters of domain objects that can be treated as a single unit. Example: `AggregateRoot`.

- **Entity**: Indicates classes that have a distinct identity that runs through time and different states. Entities within our domain model are named to reflect their role in the business domain. Example: `Customer`, `CustomerType`, `CustomerStatus`.

- **Event**: Used for classes that represent something that happened in the domain. These classes are named after the domain event they represent. Example: `HealthCheckEvent`.

- **Exception**: Prefix or suffix indicating classes that define specific domain exceptions. These exceptions are named based on the domain rule violation they represent. Example: `CustomerNotFoundException`, `CustomerTypeNotFoundException`.

- **Factory**: Denotes classes responsible for creating instances of entities or aggregates. Factories abstract the instantiation logic. Example: `CustomerFactory`, `TypeFactory`, `StatusFactory`.

- **Interface**: Prefix or suffix used to name interfaces, indicating a contract that classes must adhere to. Interfaces are named based on the role they play in the domain. Example: `CustomerInterface`, `CustomerTypeInterface`, `CustomerStatusInterface`.

- **Repository**: Suffix indicating classes that provide a collection-like interface for accessing domain objects. Repositories abstract the underlying storage mechanism. Example: `MongoStatusRepository`, `MongoTypeRepository`.

- **ValueObject**: Indicates classes that represent descriptive aspects of the domain with no conceptual identity. Value Objects are named based on what they describe. Example: `CustomerUpdate`, `CustomerTypeUpdate`, `CustomerStatusUpdate`.

- **Processor**: Suffix for classes that process requests, typically by taking a DTO as input, performing operations, and returning a response. Example: `CreateCustomerProcessor`, `CustomerPutProcessor`.

- **Resolver**: Suffix for classes that resolve GraphQL mutations or queries. They are part of the API layer that directly interacts with the GraphQL framework.

- **Transformer**: Suffix for classes that transform one type of object into another, often used for converting domain objects into DTOs or vice versa. Example: `CreateCustomerTransformer`, `CreateTypeTransformer`.

- **Command**: Suffix used to denote classes that represent an action or operation to be performed. Commands are simple DTOs that carry the data necessary for the action. Example: `CreateCustomerCommand`, `UpdateCustomerCommand`.

- **DTO**: Suffix for classes that transfer data between processes or layers of the application. They are often used as input for commands. Example: `CustomerPut`, `TypePut`, `StatusPut`.

- **MutationInput**: Suffix for classes that represent GraphQL mutation inputs. Example: `CreateCustomerMutationInput`, `UpdateTypeMutationInput`.

## Ubiquitous Language

In software development, a shared vocabulary known as the "ubiquitous language" ensures clear communication between technical and non-technical stakeholders. It streamlines collaboration, minimizes misunderstandings, and aligns software solutions with business needs.

Here is a breakdown of the meaning of our classes from the Domain layer:

### Entity

- **Customer**: Represents a customer in the CRM system, containing information like initials, email, phone, lead source, type, status, and timestamps.

- **CustomerType**: Represents a classification category for customers (e.g., Individual, Business, Enterprise).

- **CustomerStatus**: Represents the lifecycle stage of a customer (e.g., Lead, Active, Inactive).

### Event

- **HealthCheckEvent**: Published during system health verification to check database, cache, and broker connectivity.

### Exception

- **CustomerNotFoundException**: Thrown when a Customer was not found.
- **CustomerTypeNotFoundException**: Thrown when a CustomerType was not found.

### ValueObject

- **CustomerUpdate**: Used to transfer customer update data among different layers.
- **CustomerTypeUpdate**: Used to transfer customer type update data among different layers.
- **CustomerStatusUpdate**: Used to transfer customer status update data among different layers.
- **Ulid**: Universally Unique Lexicographically Sortable Identifier used for entity IDs.
- **HealthCheck**: Represents the result of a health check operation.

### Factory

- **CustomerFactory**: Creates new Customer instances.
- **TypeFactory**: Creates new CustomerType instances.
- **StatusFactory**: Creates new CustomerStatus instances.
- **UlidFactory**: Creates new ULID instances for entity identification.

### Command

- **CreateCustomerCommand**: Dispatched to create a new customer.
- **UpdateCustomerCommand**: Dispatched to update an existing customer.
- **CreateTypeCommand**: Dispatched to create a new customer type.
- **UpdateCustomerTypeCommand**: Dispatched to update an existing customer type.
- **CreateStatusCommand**: Dispatched to create a new customer status.
- **UpdateCustomerStatusCommand**: Dispatched to update an existing customer status.

This glossary will be updated as the project evolves. If you encounter a term or class naming convention you believe should be included here, please [open an issue](https://github.com/VilnaCRM-Org/core-service/issues/new) to suggest it.

Learn more about [Versioning and Change Management](versioning.md).
