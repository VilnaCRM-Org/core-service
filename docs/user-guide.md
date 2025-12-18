Welcome to the User Guide for Core Service. This guide aims to provide you with all the necessary information to use our service and its features.

## Localization

We support multiple languages. The default language is English, but you can easily change it by passing the `Accept-Language` header with your preferred language code. This will adjust the language of the messages and errors you receive from the Core Service.

## API Overview

The Core Service provides two primary API interfaces for managing customers:

1. **REST API**: Traditional RESTful endpoints following JSON-LD/Hydra standards
2. **GraphQL API**: Flexible query language for precise data fetching

Both interfaces provide full CRUD (Create, Read, Update, Delete) operations for all resources.

## Working with Customers

### Creating a Customer

#### REST API

```bash
curl -X POST \
  https://localhost/api/customers \
  -H 'Content-Type: application/json' \
  -d '{
    "initials": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "leadSource": "Website",
    "customerType": "/api/customer_types/01HGXK...",
    "customerStatus": "/api/customer_statuses/01HGXK...",
    "confirmed": true
  }'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

#### GraphQL

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

### Retrieving Customers

#### Get Single Customer (REST)

```bash
curl -X GET \
  https://localhost/api/customers/{id}
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

#### Get Customer Collection (REST)

```bash
curl -X GET \
  'https://localhost/api/customers?page=1&itemsPerPage=10'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

#### Get Customer (GraphQL)

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

### Updating a Customer

#### REST API (PATCH)

```bash
curl -X PATCH \
  https://localhost/api/customers/{id} \
  -H 'Content-Type: application/merge-patch+json' \
  -d '{
    "phone": "+1987654321"
  }'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

#### REST API (PUT)

```bash
curl -X PUT \
  https://localhost/api/customers/{id} \
  -H 'Content-Type: application/json' \
  -d '{
    "initials": "John Doe",
    "email": "john@example.com",
    "phone": "+1987654321",
    "leadSource": "Referral",
    "customerType": "/api/customer_types/01HGXK...",
    "customerStatus": "/api/customer_statuses/01HGXK...",
    "confirmed": true
  }'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

#### GraphQL

```graphql
mutation {
  updateCustomer(input: { id: "/api/customers/01HGXK...", phone: "+1987654321" }) {
    customer {
      id
      phone
    }
  }
}
```

### Deleting a Customer

#### REST API

```bash
curl -X DELETE \
  https://localhost/api/customers/{id}
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

#### GraphQL

```graphql
mutation {
  deleteCustomer(input: { id: "/api/customers/01HGXK..." }) {
    customer {
      id
    }
  }
}
```

## Working with Customer Types

Customer types categorize your customers (e.g., "Individual", "Business", "Enterprise").

### Creating a Customer Type

```bash
curl -X POST \
  https://localhost/api/customer_types \
  -H 'Content-Type: application/json' \
  -d '{
    "value": "Enterprise"
  }'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

### Listing Customer Types

```bash
curl -X GET \
  https://localhost/api/customer_types
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

## Working with Customer Statuses

Customer statuses track the lifecycle stage (e.g., "Lead", "Active", "Inactive").

### Creating a Customer Status

```bash
curl -X POST \
  https://localhost/api/customer_statuses \
  -H 'Content-Type: application/json' \
  -d '{
    "value": "Active"
  }'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

### Listing Customer Statuses

```bash
curl -X GET \
  https://localhost/api/customer_statuses
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

## Health Check

Verify the service is running:

```bash
curl -X GET \
  https://localhost/api/health
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

## Interactive Documentation

Access interactive API documentation when running locally:

- **REST API (OpenAPI)**: `https://localhost/api/docs`
- **GraphQL Playground**: `https://localhost/api/graphql`

Learn more about OAuth and other endpoints in [API Endpoints](api-endpoints.md).
