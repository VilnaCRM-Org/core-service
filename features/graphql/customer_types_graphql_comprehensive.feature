Feature: GraphQL CustomerType Operations - Comprehensive Test Cases
  In order to manage customer types via GraphQL
  As an API consumer
  I want to perform comprehensive query and mutation operations with validation

  # ----- Query Operations - Positive Cases -----

  Scenario: Query a single customer type with all fields
    Given create customer type with value "Enterprise"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 1) {
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
    And the GraphQL response should contain "data.customerTypes.edges"

  Scenario: Query customer types collection with cursor pagination
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER3"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 2) {
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
    And the GraphQL response should contain "data.customerTypes.edges"
    And the GraphQL response should contain "data.customerTypes.pageInfo.hasNextPage"
    And the GraphQL response should contain "data.customerTypes.pageInfo.endCursor"

  Scenario: Query customer types with cursor pagination, ordering, and ULID range filter
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER5"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 1, order: [{ulid: "DESC"}], ulid: [{lt: "01JKX8XGHVDZ46MWYMZT94YER6"}]) {
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
    And the GraphQL response should contain "data.customerTypes.edges"
    And the GraphQL response "data.customerTypes.edges.0.node.id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the GraphQL response "data.customerTypes.pageInfo.hasNextPage" should be "true"
    And the GraphQL response should contain "data.customerTypes.pageInfo.endCursor"

  Scenario: Query customer types with cursor pagination and ULID greater than filter
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4" and value "Type A"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER5" and value "Type B"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER6" and value "Type C"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 10, order: [{ulid: "ASC"}], ulid: [{gt: "01JKX8XGHVDZ46MWYMZT94YER4"}]) {
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
        totalCount
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customerTypes.edges"
    And the GraphQL response "data.customerTypes.edges" should have 2 items
    And the GraphQL response "data.customerTypes.totalCount" should be equal to 2
    And the GraphQL response "data.customerTypes.edges.0.node.id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the GraphQL response "data.customerTypes.edges.0.node.value" should be equal to "Type B"
    And the GraphQL response "data.customerTypes.edges.1.node.id" should contain "01JKX8XGHVDZ46MWYMZT94YER6"
    And the GraphQL response "data.customerTypes.edges.1.node.value" should be equal to "Type C"
    And the GraphQL response "data.customerTypes.pageInfo" should be an object with properties ["hasNextPage", "hasPreviousPage", "startCursor", "endCursor"]
    And the GraphQL response "data.customerTypes.pageInfo.hasNextPage" should be "false"
    And the GraphQL response "data.customerTypes.pageInfo.hasPreviousPage" should be "false"

  Scenario: Query customer types with cursor navigation using after parameter
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER7"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER8"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER9"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 1, order: [{ulid: "DESC"}]) {
        edges {
          node {
            id
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
    And the GraphQL response "data.customerTypes.pageInfo.hasNextPage" should be "true"
    And the GraphQL response should contain "data.customerTypes.pageInfo.endCursor"

  Scenario: Query customer types with filtering by value
    Given create customer type with value "Premium"
    And create customer type with value "Standard"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 10, value: "Premium") {
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
    And the GraphQL response should contain "data.customerTypes.edges"



  Scenario: Query customer types with sorting by value ascending
    Given create customer type with value "Alpha"
    And create customer type with value "Beta"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 10, order: [{value: "ASC"}]) {
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
    And the GraphQL response should contain "data.customerTypes.edges"

  Scenario: Query customer types with sorting by value descending
    Given create customer type with value "Zebra"
    And create customer type with value "Apple"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 10, order: [{value: "DESC"}]) {
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
    And the GraphQL response should contain "data.customerTypes.edges"

  Scenario: Query empty customer types collection
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 10) {
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
    And the GraphQL response "data.customerTypes.pageInfo.hasNextPage" should be "false"

  Scenario: Query single customer type by ID
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL query:
    """
    {
      customerType(id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4") {
        id
        value
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customerType.id"
    And the GraphQL response should contain "data.customerType.value"
    And the GraphQL response "data.customerType.id" should be "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"

  Scenario: Query customer types with minimal field selection
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL query:
    """
    {
      customerType(id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4") {
        id
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.customerType.id"

  # ----- Mutation Operations - Positive Cases -----

  Scenario: Create a customer type with valid value
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerType(input: {
        value: "NewType"
      }) {
        customerType {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomerType.customerType.value" should be "NewType"
    And the GraphQL response should contain "data.createCustomerType.customerType.id"
    Then delete type with value "NewType"

  Scenario: Create customer type and verify ULID is generated
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerType(input: {
        value: "ULIDTest"
      }) {
        customerType {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    Then delete type with value "ULIDTest"

  Scenario: Update a customer type value
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomerType(input: {
        id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        value: "UpdatedValue"
      }) {
        customerType {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.updateCustomerType.customerType.value" should be "UpdatedValue"
    And the GraphQL response "data.updateCustomerType.customerType.id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL query:
    """
    {
      customerType(id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4") {
        value
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response "data.customerType.value" should be "UpdatedValue"

  Scenario: Delete a customer type
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomerType(input: {
        id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER5"
      }) {
        customerType {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.deleteCustomerType.customerType.id"
    When I send the following GraphQL query:
    """
    {
      customerType(id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER5") {
        id
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response "data.customerType" should be "null"

  Scenario: Delete a customer type and verify it no longer exists
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomerType(input: {
        id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER6"
      }) {
        customerType {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    When I send the following GraphQL query:
    """
    {
      customerType(id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER6") {
        id
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response "data.customerType" should be "null"

  Scenario: Create multiple customer types sequentially
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerType(input: {
        value: "Type1"
      }) {
        customerType {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomerType.customerType.value" should be "Type1"
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerType(input: {
        value: "Type2"
      }) {
        customerType {
          id
          value
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomerType.customerType.value" should be "Type2"
    Then delete type with value "Type1"
    Then delete type with value "Type2"

  # ----- Negative Cases -----

  Scenario: Attempt to create a customer type without required value field
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerType(input: {}) {
        customerType {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to update a non-existent customer type
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomerType(input: {
        id: "/api/customer_types/01ZZZZZZZZZZZZZZZZZZZZZZZZ"
        value: "NonExistent"
      }) {
        customerType {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to delete a non-existent customer type
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomerType(input: {
        id: "/api/customer_types/01ZZZZZZZZZZZZZZZZZZZZZZZZ"
      }) {
        customerType {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Query non-existent customer type by ID returns null
    When I send the following GraphQL query:
    """
    {
      customerType(id: "/api/customer_types/01ZZZZZZZZZZZZZZZZZZZZZZZZ") {
        id
        value
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response "data.customerType" should be "null"

  Scenario: Query with invalid customer type ID format
    When I send the following GraphQL query:
    """
    {
      customerType(id: "invalid-id-format") {
        id
        value
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response "data.customerType" should be "null"
