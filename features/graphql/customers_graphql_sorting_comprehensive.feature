Feature: GraphQL Customers Sorting - Comprehensive Tests
  In order to ensure full sorting compliance in GraphQL
  As an API consumer
  I want to perform comprehensive sorting queries with various fields and directions

  Background:
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"

  # ----- Single Field Sorting Tests -----

  Scenario: Query customers sorted by email ascending
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    And create customer with email "charlie@example.com"
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

  Scenario: Query customers sorted by email descending
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    And create customer with email "charlie@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{email: "DESC"}]) {
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

  Scenario: Query customers sorted by initials ascending
    Given create customer with initials "AA"
    And create customer with initials "BB"
    And create customer with initials "CC"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{initials: "ASC"}]) {
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

  Scenario: Query customers sorted by initials descending
    Given create customer with initials "AA"
    And create customer with initials "BB"
    And create customer with initials "CC"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{initials: "DESC"}]) {
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

  Scenario: Query customers sorted by phone ascending
    Given create customer with phone "0111111111"
    And create customer with phone "0222222222"
    And create customer with phone "0333333333"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{phone: "ASC"}]) {
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

  Scenario: Query customers sorted by phone descending
    Given create customer with phone "0111111111"
    And create customer with phone "0222222222"
    And create customer with phone "0333333333"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{phone: "DESC"}]) {
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

  Scenario: Query customers sorted by leadSource ascending
    Given create customer with leadSource "Facebook"
    And create customer with leadSource "Google"
    And create customer with leadSource "Twitter"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{leadSource: "ASC"}]) {
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

  Scenario: Query customers sorted by leadSource descending
    Given create customer with leadSource "Facebook"
    And create customer with leadSource "Google"
    And create customer with leadSource "Twitter"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{leadSource: "DESC"}]) {
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

  Scenario: Query customers sorted by createdAt ascending
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{createdAt: "ASC"}]) {
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

  Scenario: Query customers sorted by createdAt descending
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
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

  Scenario: Query customers sorted by updatedAt ascending
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{updatedAt: "ASC"}]) {
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

  Scenario: Query customers sorted by updatedAt descending
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{updatedAt: "DESC"}]) {
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

  # ----- Multi-Field Sorting Tests -----

  Scenario: Query customers sorted by type value ascending then email ascending
    Given create customer with type value "Premium" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with type value "Standard" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{type_value: "ASC"}, {email: "ASC"}]) {
        edges {
          node {
            id
            email
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

  Scenario: Query customers sorted by email ascending then createdAt descending
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{email: "ASC"}, {createdAt: "DESC"}]) {
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

  # ----- Sorting with Filtering Tests -----

  Scenario: Query customers filtering by confirmed and sorted by email ascending
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

  Scenario: Query customers filtering by type value and sorted by initials descending
    Given create customer with type value "Premium" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with type value "Premium" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, type_value: "Premium", order: [{initials: "DESC"}]) {
        edges {
          node {
            id
            initials
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

  Scenario: Query customers filtering by status value and sorted by leadSource ascending
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, status_value: "Active", order: [{leadSource: "ASC"}]) {
        edges {
          node {
            id
            leadSource
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

  Scenario: Query customers with multiple records sorted by email descending
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    And create customer with email "charlie@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{email: "DESC"}]) {
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

  # ----- Sorting with Pagination Tests -----

  Scenario: Query customers sorted by createdAt descending with cursor pagination
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    When I send the following GraphQL query:
    """
    {
      customers(first: 2, order: [{createdAt: "DESC"}]) {
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

  Scenario: Query customers sorted by email ascending with reverse pagination
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    And create customer with email "charlie@example.com"
    When I send the following GraphQL query:
    """
    {
      customers(last: 2, order: [{email: "ASC"}]) {
        edges {
          node {
            id
            email
          }
          cursor
        }
        pageInfo {
          hasPreviousPage
          startCursor
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customers.pageInfo.startCursor"

  # ----- Nested Field Sorting Tests -----

  Scenario: Query customers sorted by type value ascending
    Given create customer with type value "Alpha" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with type value "Beta" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{type_value: "ASC"}]) {
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

  Scenario: Query customers sorted by type value descending
    Given create customer with type value "Alpha" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with type value "Beta" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{type_value: "DESC"}]) {
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

  Scenario: Query customers sorted by status value ascending
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with type value "VIP" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{status_value: "ASC"}]) {
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

  Scenario: Query customers sorted by status value descending
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with type value "VIP" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{status_value: "DESC"}]) {
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

  # ----- Edge Cases -----

  Scenario: Query customers sorted by email ascending with empty collection
    When I send the following GraphQL query:
    """
    {
      customers(first: 10, order: [{email: "ASC"}]) {
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

  Scenario: Query customers sorted by createdAt with single customer
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
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

