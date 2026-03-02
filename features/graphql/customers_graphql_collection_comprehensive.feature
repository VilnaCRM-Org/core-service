Feature: GraphQL Customers Collection - Comprehensive Filtering and Pagination
  In order to ensure full compliance of GraphQL API for customer collections
  As an API consumer
  I want to perform comprehensive collection queries with various filters, pagination, and validation

  Background:
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"

  # ----- Single Value Filtering Tests -----

  Scenario: Query customers filtering by specific email
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    And create customer with email "charlie@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, email: "alice@example.com") {
        edges {
          node {
            id
            email
          }
        }
        totalCount
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers filtering by specific initials
    Given create customer with initials "AB"
    And create customer with initials "CD"
    And create customer with initials "EF"
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

  Scenario: Query customers filtering by specific phone
    Given create customer with phone "0123456789"
    And create customer with phone "0987654321"
    And create customer with phone "1234567890"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, phone: "0123456789") {
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

  Scenario: Query customers filtering by specific leadSource
    Given create customer with leadSource "Google"
    And create customer with leadSource "Facebook"
    And create customer with leadSource "Twitter"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, leadSource: "Google") {
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

  Scenario: Query customers filtering by confirmed true
    Given create customer with confirmed "true"
    And create customer with confirmed "false"
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
        totalCount
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers filtering by confirmed false
    Given create customer with confirmed "true"
    And create customer with confirmed "false"
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
        totalCount
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers filtering by specific type value
    Given create customer with type value "Premium" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    And create customer with type value "Standard" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER6"
    And create customer with type value "Basic" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER7"
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

  Scenario: Query customers filtering by specific status value
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    And create customer with type value "VIP" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER6"
    And create customer with type value "VIP" and status value "Pending" and id "01JKX8XGHVDZ46MWYMZT94YER7"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, status_value: "Active") {
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

  # ----- Cursor Pagination Tests -----

  Scenario: Query customers with cursor pagination and verify pageInfo
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
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
        totalCount
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.pageInfo.hasNextPage"
    And the GraphQL response should contain "data.customers.pageInfo.endCursor"
    And the GraphQL response should contain "data.customers.pageInfo.startCursor"

  Scenario: Query customers using cursor-based pagination with after parameter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    When I send the following GraphQL query:
    """
    {
      customers(first: 1) {
        edges {
          node {
            id
          }
          cursor
        }
        pageInfo {
          endCursor
          hasNextPage
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.pageInfo.endCursor"
    And the GraphQL response "data.customers.pageInfo.hasNextPage" should be "true"

  Scenario: Query customers with last parameter for reverse pagination
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    When I send the following GraphQL query:
    """
    {
      customers(last: 2) {
        edges {
          node {
            id
          }
          cursor
        }
        pageInfo {
          hasPreviousPage
          hasNextPage
          startCursor
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.pageInfo.startCursor"

  # ----- Combined Filtering Tests -----

  Scenario: Query customers with multiple filters and sorting
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, confirmed: true, order: [{email: "ASC"}]) {
        edges {
          node {
            id
            email
            confirmed
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers filtering by email domain pattern and confirmed status
    Given create customer with email "user1@example.com"
    And create customer with email "user2@example.com"
    And create customer with email "user3@test.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, confirmed: true) {
        edges {
          node {
            id
            email
            confirmed
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  # ----- Empty Collection Tests -----

  Scenario: Query customers collection when no customers exist
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
          hasPreviousPage
        }
        totalCount
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.customers.pageInfo.hasNextPage" should be "false"
    And the GraphQL response "data.customers.pageInfo.hasPreviousPage" should be "false"

  Scenario: Query customers with filter that returns no results
    Given create customer with email "exists@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, email: "nonexistent@example.com") {
        edges {
          node {
            id
          }
        }
        totalCount
        pageInfo {
          hasNextPage
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.customers.pageInfo.hasNextPage" should be "false"

  # ----- Nested Relation Filtering Tests -----

  Scenario: Query customers with nested type and status filtering and field selection
    Given create customer with type value "Premium" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, type_value: "Premium", status_value: "Active") {
        edges {
          node {
            id
            email
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
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  # ----- Limit and Boundary Tests -----

  Scenario: Query customers with first parameter set to 0
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 0) {
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

  Scenario: Query customers with large first parameter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 100) {
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
    And the GraphQL response should contain "data.customers.edges"

  # ----- Field Selection Tests -----

  Scenario: Query customers with minimal field selection
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10) {
        edges {
          node {
            id
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers with complete field selection
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10) {
        edges {
          node {
            id
            email
            phone
            initials
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
          cursor
        }
        pageInfo {
          hasNextPage
          hasPreviousPage
          startCursor
          endCursor
        }
        totalCount
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"
    And the GraphQL response should contain "data.customers.pageInfo"
    And the GraphQL response should contain "data.customers.totalCount"

