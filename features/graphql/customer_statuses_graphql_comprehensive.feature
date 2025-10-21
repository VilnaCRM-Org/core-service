Feature: GraphQL CustomerStatus Operations - Comprehensive Test Cases
  In order to manage customer statuses via GraphQL
  As an API consumer
  I want to perform comprehensive query and mutation operations with validation

  # ----- Query Operations - Positive Cases -----

  Scenario: Query a single customer status with all fields
    Given create customer status with value "Active"
    When I send the following GraphQL query:
    """
    {
      customerStatuses(first: 1, value: "Active") {
        edges {
          node {
            id
            value
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customerStatuses.edges"

  Scenario: Query customer statuses collection with cursor pagination
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER3"
    When I send the following GraphQL query:
    """
    {
      customerStatuses(first: 2) {
        edges {
          node {
            id
            value
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
    And the GraphQL response should contain "data.customerStatuses.edges"
    And the GraphQL response should contain "data.customerStatuses.pageInfo.hasNextPage"
    And the GraphQL response should contain "data.customerStatuses.pageInfo.endCursor"

  Scenario: Query customer statuses with filtering by value
    Given create customer status with value "Premium"
    And create customer status with value "Standard"
    When I send the following GraphQL query:
    """
    {
      customerStatuses(first: 10, value: "Premium") {
        edges {
          node {
            id
            value
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customerStatuses.edges"



  Scenario: Query customer statuses with sorting by value ascending
    Given create customer status with value "Alpha"
    And create customer status with value "Beta"
    When I send the following GraphQL query:
    """
    {
      customerStatuses(first: 10, order: [{value: "ASC"}]) {
        edges {
          node {
            id
            value
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customerStatuses.edges"

  Scenario: Query empty customer statuses collection
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
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.customerStatuses.pageInfo.hasNextPage" should be "false"

  Scenario: Query single customer status by ID
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL query:
    """
    {
      customerStatus(id: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4") {
        id
        value
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customerStatus.id"
    And the GraphQL response should contain "data.customerStatus.value"

  # ----- Mutation Operations - Positive Cases -----

  Scenario: Create a customer status with valid value
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerStatus(input: {
        value: "NewStatus"
      }) {
        customerStatus {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomerStatus.customerStatus.value" should be "NewStatus"
    And the GraphQL response should contain "data.createCustomerStatus.customerStatus.id"
    Then delete status with value "NewStatus"

  Scenario: Update a customer status value
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomerStatus(input: {
        id: "01JKX8XGHVDZ46MWYMZT94YER4"
        value: "UpdatedValue"
      }) {
        customerStatus {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.updateCustomerStatus.customerStatus.value" should be "UpdatedValue"
    And the GraphQL response "data.updateCustomerStatus.customerStatus.id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"

  Scenario: Delete a customer status
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomerStatus(input: {
        id: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER5"
      }) {
        customerStatus {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.deleteCustomerStatus.customerStatus.id"

  Scenario: Create multiple customer statuses sequentially
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerStatus(input: {
        value: "Status1"
      }) {
        customerStatus {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomerStatus.customerStatus.value" should be "Status1"
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerStatus(input: {
        value: "Status2"
      }) {
        customerStatus {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomerStatus.customerStatus.value" should be "Status2"
    Then delete status with value "Status1"
    Then delete status with value "Status2"

  # ----- Negative Cases -----

  Scenario: Attempt to create a customer status without required value field
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

  Scenario: Attempt to update a non-existent customer status
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomerStatus(input: {
        id: "01ZZZZZZZZZZZZZZZZZZZZZZZZ"
        value: "NonExistent"
      }) {
        customerStatus {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to delete a non-existent customer status
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomerStatus(input: {
        id: "/api/customer_statuses/01ZZZZZZZZZZZZZZZZZZZZZZZZ"
      }) {
        customerStatus {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Query non-existent customer status by ID returns null
    When I send the following GraphQL query:
    """
    {
      customerStatus(id: "/api/customer_statuses/01ZZZZZZZZZZZZZZZZZZZZZZZZ") {
        id
        value
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response "data.customerStatus" should be "null"
