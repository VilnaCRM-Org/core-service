Feature: GraphQL CustomerStatus CRUD Operations
  In order to manage customer statuses via GraphQL
  As an API consumer
  I want to perform Create, Read, Update, and Delete operations using GraphQL mutations and queries

  Scenario: Query a single customer status by ID
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL query:
    """
    {
      customerStatus(id: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4") {
        id
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customerStatus"

  Scenario: Query customer statuses collection
    Given create customer status with value "Active"
    And create customer status with value "Inactive"
    When I send the following GraphQL query:
    """
    {
      customerStatuses(first: 10) {
        edges {
          node {
            id
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
    And the GraphQL response should contain "data.customerStatuses.edges"

  Scenario: Create a new customer status via GraphQL mutation
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerStatus(input: {
        value: "Pending"
      }) {
        customerStatus {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.createCustomerStatus.customerStatus"
    Then delete status with value "Pending"

  Scenario: Update a customer status via GraphQL mutation
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomerStatus(input: {
        id: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        value: "Updated"
      }) {
        customerStatus {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.updateCustomerStatus.customerStatus"

  Scenario: Delete a customer status via GraphQL mutation
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomerStatus(input: {
        id: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
      }) {
        customerStatus {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.deleteCustomerStatus.customerStatus"

  Scenario: Query customer statuses with filtering by value
    Given create customer status with value "VIP"
    When I send the following GraphQL query:
    """
    {
      customerStatuses(first: 5, value: "VIP") {
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
    And the GraphQL response should contain "data.customerStatuses.edges"

  Scenario: Attempt to create a customer status with missing value
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerStatus(input: {}) {
        customerStatus {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors
