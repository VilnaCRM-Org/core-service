Feature: GraphQL Customers Validation - Comprehensive Tests
  In order to ensure robust validation in GraphQL Customer operations
  As an API consumer
  I want to verify comprehensive validation rules and edge cases

  Background:
    Given ensure status exists with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And ensure type exists with id "01JKX8XGHVDZ46MWYMZT94YER4"

  # ----- Email Validation Tests -----

  Scenario: Attempt to create customer with invalid email format
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "IE"
        email: "invalid-email"
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

  Scenario: Attempt to create customer with empty email
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "EE"
        email: ""
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

  Scenario: Create customer with valid uppercase email
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "UC"
        email: "UPPERCASE@EXAMPLE.COM"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          email
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.email" should be "UPPERCASE@EXAMPLE.COM"
    Then delete customer with email "UPPERCASE@EXAMPLE.COM"

  Scenario: Attempt to create customer with duplicate email
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

  # ----- Phone Validation Tests -----

  Scenario: Attempt to create customer with empty phone
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "EP"
        email: "emptyphone@example.com"
        phone: ""
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

  Scenario: Create customer with valid international phone format
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "IP"
        email: "intlphone@example.com"
        phone: "+380631234567"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          phone
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.phone" should be "+380631234567"
    Then delete customer with email "intlphone@example.com"

  # ----- Initials Validation Tests -----

  Scenario: Attempt to create customer with empty initials
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: ""
        email: "emptyinitials@example.com"
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

  Scenario: Create customer with minimal valid initials
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "A"
        email: "mininitials@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          initials
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.initials" should be "A"
    Then delete customer with email "mininitials@example.com"

  Scenario: Create customer with long initials
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "Very Long Initials Name"
        email: "longinitials@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          initials
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.initials" should be "Very Long Initials Name"
    Then delete customer with email "longinitials@example.com"

  # ----- LeadSource Validation Tests -----

  Scenario: Attempt to create customer with empty leadSource
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "EL"
        email: "emptyleadsource@example.com"
        phone: "+1234567890"
        leadSource: ""
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

  Scenario: Create customer with special characters in leadSource
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "SC"
        email: "specialleadsource@example.com"
        phone: "+1234567890"
        leadSource: "Google Ads - Campaign #123"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          leadSource
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.leadSource" should be "Google Ads - Campaign #123"
    Then delete customer with email "specialleadsource@example.com"

  # ----- Type and Status Validation Tests -----

  Scenario: Attempt to create customer with non-existent type ID
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "NT"
        email: "notype@example.com"
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

  Scenario: Attempt to create customer with non-existent status ID
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "NS"
        email: "nostatus@example.com"
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

  Scenario: Attempt to create customer with invalid type IRI format
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "IT"
        email: "invalidtypeiri@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "invalid-iri"
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

  Scenario: Attempt to create customer with invalid status IRI format
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "IS"
        email: "invalidstatusiri@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "invalid-iri"
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

  # ----- Update Validation Tests -----

  Scenario: Attempt to update customer with invalid email
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        email: "invalid-email-format"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should have errors

  Scenario: Attempt to update customer with duplicate email
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YERA" and email "original@example.com"
    And create customer with email "existing@example.com"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YERA"
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
    Then delete customer with email "original@example.com"
    And delete customer with email "existing@example.com"

  Scenario: Attempt to update customer with initials containing only spaces
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YERB"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YERB"
        initials: "  "
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
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YERC"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YERC"
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
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YERD"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YERD"
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

  # ----- Boundary Tests -----

  Scenario: Create customer and verify ULID is automatically generated
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "UG"
        email: "ulidgen@example.com"
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
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.createCustomer.customer.id"
    Then delete customer with email "ulidgen@example.com"

  Scenario: Create customer and verify timestamps are set
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "TS"
        email: "timestamps@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          createdAt
          updatedAt
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.createCustomer.customer.createdAt"
    And the GraphQL response should contain "data.createCustomer.customer.updatedAt"
    And the GraphQL response "data.createCustomer.customer.createdAt" should match regex "/^\d{4}-\d{2}-\d{2}T/"
    And the GraphQL response "data.createCustomer.customer.updatedAt" should match regex "/^\d{4}-\d{2}-\d{2}T/"
    Then delete customer with email "timestamps@example.com"

  Scenario: Update customer and verify updatedAt changes
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YERE"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YERE"
        email: "updated@example.com"
      }) {
        customer {
          id
          updatedAt
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.updateCustomer.customer.updatedAt"
    Then delete customer with email "updated@example.com"

  # ----- Confirmed Field Tests -----

  Scenario: Create customer with confirmed set to true
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "CT"
        email: "confirmedtrue@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          confirmed
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.confirmed" should be "true"
    Then delete customer with email "confirmedtrue@example.com"

  Scenario: Create customer with confirmed set to false
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "CF"
        email: "confirmedfalse@example.com"
        phone: "+1234567890"
        leadSource: "Test"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: false
      }) {
        customer {
          id
          confirmed
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.confirmed" should be "false"
    Then delete customer with email "confirmedfalse@example.com"

  Scenario: Update customer confirmed from true to false
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YERF"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YERF"
        confirmed: false
      }) {
        customer {
          id
          confirmed
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.updateCustomer.customer.confirmed" should be "false"

  Scenario: Update customer confirmed from false to true
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YERG"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YERG"
        confirmed: true
      }) {
        customer {
          id
          confirmed
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.updateCustomer.customer.confirmed" should be "true"

