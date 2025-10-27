Feature: GraphQL Customer CRUD Operations
  In order to manage customers via GraphQL
  As an API consumer
  I want to perform Create, Read, Update, and Delete operations using GraphQL mutations and queries

  Background:
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"

  Scenario: Query a single customer by ID
    Given create customer with id 01JKX8XGHVDZ46MWYMZT94YER4
    When I send the following GraphQL query:
    """
    {
      customer(id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4") {
        id
        initials
        email
        phone
        leadSource
        confirmed
        type {
          id
          value
        }
        status {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customer"
    And the GraphQL response "data.customer.email" should contain "@"
    And the GraphQL response "data.customer.confirmed" should be "true"

  Scenario: Query customers collection
    Given create customer with email "user1@example.com"
    And create customer with email "user2@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10) {
        edges {
          node {
            id
            email
            initials
          }
        }
        pageInfo {
          hasNextPage
          endCursor
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"
    Then delete customer with email "user1@example.com"
    And delete customer with email "user2@example.com"

  Scenario: Create a new customer via GraphQL mutation
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "JD"
        email: "graphql@example.com"
        phone: "1234567890"
        leadSource: "GraphQL Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          initials
          email
          phone
          leadSource
          confirmed
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.email" should be "graphql@example.com"
    And the GraphQL response "data.createCustomer.customer.initials" should be "JD"
    And the GraphQL response "data.createCustomer.customer.phone" should be "1234567890"
    And the GraphQL response "data.createCustomer.customer.leadSource" should be "GraphQL Test"
    And the GraphQL response "data.createCustomer.customer.confirmed" should be "true"
    Then delete customer with email "graphql@example.com"

  Scenario: Update a customer via GraphQL mutation
    Given create customer with id 01JKX8XGHVDZ46MWYMZT94YER4
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4"
        email: "updated@example.com"
        phone: "9876543210"
      }) {
        customer {
          id
          email
          phone
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.updateCustomer.customer.email" should be "updated@example.com"
    And the GraphQL response "data.updateCustomer.customer.phone" should be "9876543210"

  Scenario: Delete a customer via GraphQL mutation
    Given create customer with id 01JKX8XGHVDZ46MWYMZT94YER4
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.deleteCustomer.customer"

  Scenario: Query customers with filtering
    Given create customer with type value "Premium" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL query:
    """
    {
      customers(first: 5, type_value: "Premium") {
        edges {
          node {
            id
            type {
              value
            }
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Attempt to create a customer with missing required fields
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "JD"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Query non-existent customer
    When I send the following GraphQL query:
    """
    {
      customer(id: "/api/customers/01ZZZZZZZZZZZZZZZZZZZZZZZZ") {
        id
        email
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response "data.customer" should be "null"
