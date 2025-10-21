Feature: GraphQL Customers Date Filtering - Comprehensive Tests
  In order to ensure full date filtering compliance in GraphQL
  As an API consumer
  I want to perform comprehensive date filtering queries with various operators

  Background:
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"

  # ----- CreatedAt Date Filtering Tests -----

  Scenario: Query customers with createdAt before filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, createdAt: {before: "2030-12-31T23:59:59Z"}) {
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

  Scenario: Query customers with createdAt after filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, createdAt: {after: "2020-01-01T00:00:00Z"}) {
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

  Scenario: Query customers with createdAt strictly_before filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, createdAt: {strictly_before: "2030-12-31T23:59:59Z"}) {
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

  Scenario: Query customers with createdAt strictly_after filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, createdAt: {strictly_after: "2020-01-01T00:00:00Z"}) {
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

  Scenario: Query customers with createdAt range filter (after and before)
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, createdAt: {after: "2020-01-01T00:00:00Z", before: "2030-12-31T23:59:59Z"}) {
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

  Scenario: Query customers with createdAt strict range filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, createdAt: {strictly_after: "2020-01-01T00:00:00Z", strictly_before: "2030-12-31T23:59:59Z"}) {
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

  # ----- UpdatedAt Date Filtering Tests -----

  Scenario: Query customers with updatedAt before filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, updatedAt: {before: "2030-12-31T23:59:59Z"}) {
        edges {
          node {
            id
            updatedAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers with updatedAt after filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, updatedAt: {after: "2020-01-01T00:00:00Z"}) {
        edges {
          node {
            id
            updatedAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers with updatedAt strictly_before filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, updatedAt: {strictly_before: "2030-12-31T23:59:59Z"}) {
        edges {
          node {
            id
            updatedAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers with updatedAt strictly_after filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, updatedAt: {strictly_after: "2020-01-01T00:00:00Z"}) {
        edges {
          node {
            id
            updatedAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers with updatedAt range filter (after and before)
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, updatedAt: {after: "2020-01-01T00:00:00Z", before: "2030-12-31T23:59:59Z"}) {
        edges {
          node {
            id
            updatedAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers with updatedAt strict range filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, updatedAt: {strictly_after: "2020-01-01T00:00:00Z", strictly_before: "2030-12-31T23:59:59Z"}) {
        edges {
          node {
            id
            updatedAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  # ----- Combined Date and Other Filters -----

  Scenario: Query customers with createdAt filter and email filter
    Given create customer with email "recent@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, email: "recent@example.com", createdAt: {after: "2020-01-01T00:00:00Z"}) {
        edges {
          node {
            id
            email
            createdAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers with updatedAt filter and confirmed status
    Given create customer with confirmed "true"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, confirmed: true, updatedAt: {after: "2020-01-01T00:00:00Z"}) {
        edges {
          node {
            id
            confirmed
            updatedAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  Scenario: Query customers with createdAt filter, type filter, and status filter
    Given create customer with type value "Premium" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL query:
    """
    {
      customers(
        first: 10
        type_value: "Premium"
        status_value: "Active"
        createdAt: {after: "2020-01-01T00:00:00Z"}
      ) {
        edges {
          node {
            id
            createdAt
            type {
              value
            }
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

  # ----- Date Sorting Combined with Filters -----

  Scenario: Query customers with createdAt filter and sort by createdAt descending
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(
        first: 10
        createdAt: {after: "2020-01-01T00:00:00Z"}
        order: [{createdAt: "DESC"}]
      ) {
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

  Scenario: Query customers with updatedAt filter and sort by updatedAt ascending
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(
        first: 10
        updatedAt: {after: "2020-01-01T00:00:00Z"}
        order: [{updatedAt: "ASC"}]
      ) {
        edges {
          node {
            id
            updatedAt
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.edges"

  # ----- Edge Cases -----

  Scenario: Query customers with createdAt filter that returns no results
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, createdAt: {before: "2000-01-01T00:00:00Z"}) {
        edges {
          node {
            id
          }
        }
        totalCount
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors

  Scenario: Query customers with updatedAt filter that returns no results
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, updatedAt: {after: "2100-01-01T00:00:00Z"}) {
        edges {
          node {
            id
          }
        }
        totalCount
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors

  # ----- Date Pagination Tests -----

  Scenario: Query customers with date filter and cursor pagination
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    When I send the following GraphQL query:
    """
    {
      customers(
        first: 2
        createdAt: {after: "2020-01-01T00:00:00Z"}
      ) {
        edges {
          node {
            id
            createdAt
          }
          cursor
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
    And the GraphQL response should contain "data.customers.pageInfo.endCursor"

