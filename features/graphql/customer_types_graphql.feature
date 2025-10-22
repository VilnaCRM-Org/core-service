Feature: GraphQL CustomerType CRUD Operations
  In order to manage customer types via GraphQL
  As an API consumer
  I want to perform Create, Read, Update, and Delete operations using GraphQL mutations and queries

  Scenario: Query a single customer type by ID
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
    And the GraphQL response should contain "data.customerType"
    And the GraphQL response should contain "data.customerType.value"

  Scenario: Query customer types collection
    Given create customer type with value "Premium"
    And create customer type with value "Standard"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 10) {
        edges {
          node {
            id
            value
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
    And the GraphQL response should contain "data.customerTypes.edges"

  Scenario: Create a new customer type via GraphQL mutation
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomerType(input: {
        value: "Enterprise"
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
    And the GraphQL response "data.createCustomerType.customerType.value" should be "Enterprise"
    Then delete type with value "Enterprise"

  Scenario: Update a customer type via GraphQL mutation
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomerType(input: {
        id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        value: "Updated Type"
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
    And the GraphQL response "data.updateCustomerType.customerType.value" should be "Updated Type"

  Scenario: Delete a customer type via GraphQL mutation
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomerType(input: {
        id: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
      }) {
        customerType {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.deleteCustomerType.customerType"

  Scenario: Query customer types with filtering by value
    Given create customer type with value "VIP"
    When I send the following GraphQL query:
    """
    {
      customerTypes(first: 5, value: "VIP") {
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

  Scenario: Query customer types with ordering
    Given create customer type with value "Zebra"
    And create customer type with value "Alpha"
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

  Scenario: Attempt to create a customer type with missing value
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

  Scenario: Query non-existent customer type
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
