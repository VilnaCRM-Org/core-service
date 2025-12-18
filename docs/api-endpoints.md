This document outlines the API endpoints in the Core Service, as defined in the OpenAPI specification and implemented in the codebase.

## OpenApi specification

You can go [here](https://github.com/VilnaCRM-Org/core-service/blob/main/.github/openapi-spec/spec.yaml), to get our OpenApi specification as a `.yaml` file, and then use [Swagger Editor](https://editor.swagger.io/) to browse it.

If you've set up Core Service **locally**, you can check [this URL](https://localhost/api/docs) to see the same OpenApi specification with an interactive interface.

### How to read the documentation

You will see a list of endpoints, and by clicking on them, you can see detailed information about each of them, such as description, available input, and possible responses.

You can also send a request, by clicking the `Try it out` button in the upper right corner and providing required input.

## GraphQL specification

If you've set up Core Service **locally**, you can check [this URL](https://localhost/api/graphql) to see the GraphQL specification with an interactive GraphQL playground interface.

### How to read the documentation

At the right side of the screen, you'll see a tab named `Docs`. There you'll find all the information about available Mutations and Queries.

On the left side of the screen, you'll see a place for writing queries, from it you can send the requests.

Learn more about [GraphQl Queries and Mutations](https://graphql.org/learn/queries/).

Learn more about [Developer Guide](developer-guide.md).

## Available REST API Endpoints

### Customer Endpoints

| Method | Endpoint              | Description                 |
| ------ | --------------------- | --------------------------- |
| GET    | `/api/customers`      | Get collection of customers |
| POST   | `/api/customers`      | Create a new customer       |
| GET    | `/api/customers/{id}` | Get a specific customer     |
| PUT    | `/api/customers/{id}` | Replace a customer          |
| PATCH  | `/api/customers/{id}` | Update a customer           |
| DELETE | `/api/customers/{id}` | Delete a customer           |

### Customer Type Endpoints

| Method | Endpoint                   | Description                      |
| ------ | -------------------------- | -------------------------------- |
| GET    | `/api/customer_types`      | Get collection of customer types |
| POST   | `/api/customer_types`      | Create a new customer type       |
| GET    | `/api/customer_types/{id}` | Get a specific customer type     |
| PUT    | `/api/customer_types/{id}` | Replace a customer type          |
| PATCH  | `/api/customer_types/{id}` | Update a customer type           |
| DELETE | `/api/customer_types/{id}` | Delete a customer type           |

### Customer Status Endpoints

| Method | Endpoint                      | Description                         |
| ------ | ----------------------------- | ----------------------------------- |
| GET    | `/api/customer_statuses`      | Get collection of customer statuses |
| POST   | `/api/customer_statuses`      | Create a new customer status        |
| GET    | `/api/customer_statuses/{id}` | Get a specific customer status      |
| PUT    | `/api/customer_statuses/{id}` | Replace a customer status           |
| PATCH  | `/api/customer_statuses/{id}` | Update a customer status            |
| DELETE | `/api/customer_statuses/{id}` | Delete a customer status            |

### Health Check

| Method | Endpoint      | Description                 |
| ------ | ------------- | --------------------------- |
| GET    | `/api/health` | Check service health status |

## Available GraphQL Operations

### Queries

- `customer(id: ID!)` - Get a specific customer by ID
- `customers(...)` - Get collection of customers with filtering and pagination
- `customerType(id: ID!)` - Get a specific customer type
- `customerTypes(...)` - Get collection of customer types
- `customerStatus(id: ID!)` - Get a specific customer status
- `customerStatuses(...)` - Get collection of customer statuses

### Mutations

- `createCustomer(input: CreateCustomerInput!)` - Create a new customer
- `updateCustomer(input: UpdateCustomerInput!)` - Update an existing customer
- `deleteCustomer(input: DeleteCustomerInput!)` - Delete a customer
- `createCustomerType(input: CreateCustomerTypeInput!)` - Create a new customer type
- `updateCustomerType(input: UpdateCustomerTypeInput!)` - Update an existing customer type
- `deleteCustomerType(input: DeleteCustomerTypeInput!)` - Delete a customer type
- `createCustomerStatus(input: CreateCustomerStatusInput!)` - Create a new customer status
- `updateCustomerStatus(input: UpdateCustomerStatusInput!)` - Update an existing customer status
- `deleteCustomerStatus(input: DeleteCustomerStatusInput!)` - Delete a customer status

## Filtering and Sorting

### Date Filtering

The Core Service supports filtering customers by date fields with various operators:

- `createdAt[before]` - Filter customers created before a date
- `createdAt[after]` - Filter customers created after a date
- `createdAt[strictly_before]` - Filter customers created strictly before a date
- `createdAt[strictly_after]` - Filter customers created strictly after a date
- `updatedAt[before]` - Filter customers updated before a date
- `updatedAt[after]` - Filter customers updated after a date

### Sorting

Collections can be sorted using the `order` parameter:

```
GET /api/customers?order[createdAt]=desc
GET /api/customers?order[initials]=asc
```

## Example GraphQL Queries

### Get Customer by ID

```graphql
query {
  customer(id: "/api/customers/01HGXK...") {
    id
    initials
    email
    phone
    leadSource
    customerType {
      id
      value
    }
    customerStatus {
      id
      value
    }
  }
}
```

### Create Customer Mutation

```graphql
mutation {
  createCustomer(
    input: {
      initials: "John Doe"
      email: "john@example.com"
      phone: "+1234567890"
      leadSource: "Website"
      customerType: "/api/customer_types/01HGXK..."
      customerStatus: "/api/customer_statuses/01HGXK..."
      confirmed: true
    }
  ) {
    customer {
      id
      initials
      email
    }
  }
}
```

See [testing.md](testing.md) for detailed test scenarios.
