Feature: GraphQL Customer Query Operations - Positive Test Cases
  In order to retrieve customer data via GraphQL
  As an API consumer
  I want to perform comprehensive query operations with various filters, sorting, and pagination

  Background:
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"

  # ----- Single Customer Query Tests -----

  Scenario: Query a single customer with all fields
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
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
        createdAt
        updatedAt
        type {
          id
          value
        }
        status {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customer.id"
    And the GraphQL response should contain "data.customer.initials"
    And the GraphQL response should contain "data.customer.email"
    And the GraphQL response should contain "data.customer.phone"
    And the GraphQL response should contain "data.customer.leadSource"
    And the GraphQL response should contain "data.customer.confirmed"
    And the GraphQL response should contain "data.customer.createdAt"
    And the GraphQL response should contain "data.customer.updatedAt"
    And the GraphQL response should contain "data.customer.type.id"
    And the GraphQL response should contain "data.customer.type.value"
    And the GraphQL response should contain "data.customer.status.id"
    And the GraphQL response "data.customer.id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the GraphQL response "data.customer.type.value" should not be empty
    And the GraphQL response "data.customer.status.value" should not be empty

  Scenario: Query a single customer with minimal field selection
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL query:
    """
    {
      customer(id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4") {
        id
        email
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customer.id"
    And the GraphQL response should contain "data.customer.email"

  Scenario: Query a single customer verifying email format
    Given create customer with email "test@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 1, email: "test@example.com") {
        edges {
          node {
            id
            email
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  # ----- Collection Query Tests with Pagination -----

  Scenario: Query customers collection with cursor pagination
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    When I send the following GraphQL query:
    """
    {
      customers(first: 2) {
        edges {
          node {
            id
          }
          cursor
        }
        pageInfo {
          hasNextPage
          hasPreviousPage
          startCursor
          endCursor
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"
    And the GraphQL response should contain "data.customers.pageInfo.hasNextPage"
    And the GraphQL response should contain "data.customers.pageInfo.endCursor"
    And the GraphQL response "data.customers.edges" should have 2 items
    And the GraphQL response "data.customers.pageInfo.hasNextPage" should be "true"
    And the GraphQL response should contain "data.customers.edges.0.cursor"
    And the GraphQL response should contain "data.customers.edges.1.cursor"

  Scenario: Query customers collection filtering by email
    Given create customer with email "user1@example.com"
    And create customer with email "user2@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, email: "user1@example.com") {
        edges {
          node {
            id
            email
          }
        }
        pageInfo {
          hasNextPage
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"
    And the GraphQL response "data.customers.edges" should have 1 items
    And the GraphQL response "data.customers.edges.0.node.email" should be equal to "user1@example.com"

  Scenario: Query customers collection filtering by initials
    Given create customer with initials "AB"
    And create customer with initials "CD"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, initials: "AB") {
        edges {
          node {
            id
            initials
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"
    And the GraphQL response "data.customers.edges" should have 1 items
    And the GraphQL response "data.customers.edges.0.node.initials" should be equal to "AB"

  Scenario: Query customers collection filtering by phone
    Given create customer with phone "+1234567890"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, phone: "+1234567890") {
        edges {
          node {
            id
            phone
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"
    And the GraphQL response "data.customers.edges" should have 1 items
    And the GraphQL response "data.customers.edges.0.node.phone" should be equal to "+1234567890"

  Scenario: Query customers collection filtering by leadSource
    Given create customer with leadSource "GoogleAds"
    And create customer with leadSource "Facebook"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, leadSource: "GoogleAds") {
        edges {
          node {
            id
            leadSource
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"
    And the GraphQL response "data.customers.edges" should have 1 items
    And the GraphQL response "data.customers.edges.0.node.leadSource" should be equal to "GoogleAds"

  Scenario: Query customers collection filtering by confirmed status true
    Given create customer with confirmed "true"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, confirmed: true) {
        edges {
          node {
            id
            confirmed
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"
    And the GraphQL response "data.customers.edges" should have at least 1 items
    And the GraphQL response "data.customers.edges.0.node.confirmed" should be "true"

  Scenario: Query customers collection filtering by confirmed status false
    Given create customer with confirmed "false"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, confirmed: false) {
        edges {
          node {
            id
            confirmed
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"
    And the GraphQL response "data.customers.edges" should have at least 1 items
    And the GraphQL response "data.customers.edges.0.node.confirmed" should be "false"

  Scenario: Query customers collection filtering by type value
    Given create customer with type value "Premium" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, type_value: "Premium") {
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

  Scenario: Query customers collection filtering by status value
    Given customer with type value "Regular" and status value "Inactive" exists
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, status_value: "Inactive") {
        edges {
          node {
            id
            status {
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

  # ----- Sorting Tests -----



  Scenario: Query customers collection with sorting by email ascending
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{email: "ASC"}]) {
        edges {
          node {
            id
            email
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers collection with sorting by createdAt descending
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{createdAt: "DESC"}]) {
        edges {
          node {
            id
            createdAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  # ----- Edge Cases -----

  Scenario: Query customers collection when empty
    When I send the following GraphQL query:
    """
    {
      customers(first: 10) {
        edges {
          node {
            id
          }
        }
        pageInfo {
          hasNextPage
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.customers.pageInfo.hasNextPage" should be "false"

  Scenario: Query customer with non-existent ID returns null
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

  Scenario: Query customers with nested type and status fields
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL query:
    """
    {
      customer(id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4") {
        id
        type {
          id
          value
        }
        status {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customer.type.id"
    And the GraphQL response should contain "data.customer.type.value"
    And the GraphQL response should contain "data.customer.status.id"
    And the GraphQL response should contain "data.customer.status.value"
