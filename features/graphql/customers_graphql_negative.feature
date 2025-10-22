Feature: GraphQL Customer Operations - Negative Test Cases
  In order to ensure robust error handling in GraphQL Customer operations
  As an API consumer
  I want to verify that invalid operations return appropriate errors

  Background:
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"

  # ----- Create Mutation Error Cases -----

  Scenario: Attempt to create a customer without required initials field
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        email: "noinitials@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer without required email field
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "NE"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer without required phone field
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "NP"
        email: "nophone@example.com"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer without required leadSource field
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "NL"
        email: "noleadsource@example.com"
        phone: "+1234567890"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer without required type field
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "NT"
        email: "notype@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer without required status field
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "NS"
        email: "nostatus@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer without required confirmed field
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "NC"
        email: "noconfirmed@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer with empty input
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {}) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer with non-existent type ID
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "IT"
        email: "invalidtype@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01ZZZZZZZZZZZZZZZZZZZZZZZZ"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer with non-existent status ID
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "IS"
        email: "invalidstatus@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01ZZZZZZZZZZZZZZZZZZZZZZZZ"
        confirmed: true
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to create a customer with duplicate email
    Given create customer with email "duplicate@example.com"
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "DU"
        email: "duplicate@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors
    Then delete customer with email "duplicate@example.com"

  # ----- Update Mutation Error Cases -----

  Scenario: Attempt to update a non-existent customer
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01ZZZZZZZZZZZZZZZZZZZZZZZZ"
        email: "nonexistent@example.com"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to update customer with non-existent type
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        type: "/api/customer_types/01ZZZZZZZZZZZZZZZZZZZZZZZZ"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to update customer with non-existent status
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        status: "/api/customer_statuses/01ZZZZZZZZZZZZZZZZZZZZZZZZ"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to update customer email to duplicate email
    Given create customer with email "original@example.com"
    And create customer with email "existing@example.com"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        email: "existing@example.com"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  # ----- Delete Mutation Error Cases -----

  Scenario: Attempt to delete a non-existent customer
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomer(input: {
        id: "/api/customers/01ZZZZZZZZZZZZZZZZZZZZZZZZ"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  # ----- Query Error Cases -----

  Scenario: Query non-existent customer by ID returns null
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

  Scenario: Query with invalid ID format
    When I send the following GraphQL query:
    """
    {
      customer(id: "invalid-id-format") {
        id
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response "data.customer" should be "null"
