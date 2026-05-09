# API Endpoints

This document outlines the API endpoints in the Core Service, as defined in the OpenAPI specification and implemented in the codebase.

## OpenAPI specification

You can go [here](https://github.com/VilnaCRM-Org/core-service/blob/main/.github/openapi-spec/spec.yaml), to get our OpenAPI specification as a `.yaml` file, and then use [Swagger Editor](https://editor.swagger.io/) to browse it.

If you've set up Core Service **locally**, you can check [this URL](https://localhost/api/docs) to see the same OpenAPI specification with an interactive interface.

### How to read the documentation

You will see a list of endpoints, and by clicking on them, you can see detailed information about each of them, such as description, available input, and possible responses.

You can also send a request, by clicking the `Try it out` button in the upper right corner and providing required input.

## GraphQL specification

If you've set up Core Service **locally**, you can check [this URL](https://localhost/api/graphql) to see the GraphQL specification with an interactive GraphQL playground interface.

### How to read the documentation

At the right side of the screen, you'll see a tab named `Docs`. There you'll find all the information about available Mutations and Queries.

On the left side of the screen, you'll see a place for writing queries, from it you can send the requests.

Learn more about [GraphQL Queries and Mutations](https://graphql.org/learn/queries/).

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

### Onboarding Step Endpoints

| Method | Endpoint                     | Description                          |
| ------ | ---------------------------- | ------------------------------------ |
| GET    | `/api/onboarding_steps`      | Get onboarding setup steps           |
| POST   | `/api/onboarding_steps`      | Create a new onboarding setup step   |
| GET    | `/api/onboarding_steps/{id}` | Get a specific onboarding setup step |
| PUT    | `/api/onboarding_steps/{id}` | Replace an onboarding setup step     |
| PATCH  | `/api/onboarding_steps/{id}` | Update an onboarding setup step      |
| DELETE | `/api/onboarding_steps/{id}` | Delete an onboarding setup step      |

### Tariff Plan Endpoints

| Method | Endpoint                 | Description                 |
| ------ | ------------------------ | --------------------------- |
| GET    | `/api/tariff_plans`      | Get onboarding tariff plans |
| POST   | `/api/tariff_plans`      | Create a new tariff plan    |
| GET    | `/api/tariff_plans/{id}` | Get a specific tariff plan  |
| PUT    | `/api/tariff_plans/{id}` | Replace a tariff plan       |
| PATCH  | `/api/tariff_plans/{id}` | Update a tariff plan        |
| DELETE | `/api/tariff_plans/{id}` | Delete a tariff plan        |

Default onboarding data can be seeded with:

```bash
bin/console app:onboarding:seed-defaults
```

## Available GraphQL Operations

### Queries

- `customer(id: ID!)` - Get a specific customer by ID
- `customers(...)` - Get collection of customers with filtering and pagination
- `customerType(id: ID!)` - Get a specific customer type
- `customerTypes(...)` - Get collection of customer types
- `customerStatus(id: ID!)` - Get a specific customer status
- `customerStatuses(...)` - Get collection of customer statuses
- `onboardingStep(id: ID!)` - Get a specific onboarding setup step
- `onboardingSteps(...)` - Get collection of onboarding setup steps
- `tariffPlan(id: ID!)` - Get a specific tariff plan
- `tariffPlans(...)` - Get collection of tariff plans

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
- `createOnboardingStep(input: createOnboardingStepInput!)` - Create an onboarding setup step
- `updateOnboardingStep(input: updateOnboardingStepInput!)` - Update an onboarding setup step
- `deleteOnboardingStep(input: deleteOnboardingStepInput!)` - Delete an onboarding setup step
- `createTariffPlan(input: createTariffPlanInput!)` - Create a tariff plan
- `updateTariffPlan(input: updateTariffPlanInput!)` - Update a tariff plan
- `deleteTariffPlan(input: deleteTariffPlanInput!)` - Delete a tariff plan

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
GET /api/onboarding_steps?order[position]=asc
GET /api/tariff_plans?order[priceCents]=asc
```

Onboarding steps can be filtered by `code`, `label`, and `enabled`.

Tariff plans can be filtered by `code`, `name`, `description`, `priceCurrency`, `pricePeriod`, `enabled`, `functionalLimitations`, `priceCents`, `userLimit`, and `position`.

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
