Feature: GraphQL Customer Mutation Operations - Positive Test Cases
  In order to manage customer data via GraphQL mutations
  As an API consumer
  I want to perform create, update, and delete operations with comprehensive validation

  Background:
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"

  # ----- Create Mutation Tests -----

  Scenario: Create a customer with all required fields
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "JD"
        email: "john.doe@example.com"
        phone: "+1234567890"
        leadSource: "Website"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          initials
          email
          phone
          leadSource
          confirmed
          type {
            id
          }
          status {
            id
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.initials" should be "JD"
    And the GraphQL response "data.createCustomer.customer.email" should be "john.doe@example.com"
    And the GraphQL response "data.createCustomer.customer.phone" should be "+1234567890"
    And the GraphQL response "data.createCustomer.customer.leadSource" should be "Website"
    And the GraphQL response "data.createCustomer.customer.confirmed" should be "true"
    And the GraphQL response should contain "data.createCustomer.customer.id"
    Then delete customer with email "john.doe@example.com"

  Scenario: Create a customer with confirmed set to false
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "AB"
        email: "unconfirmed@example.com"
        phone: "+9876543210"
        leadSource: "Referral"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: false
      }) {
        customer {
          id
          email
          confirmed
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.createCustomer.customer.confirmed" should be "false"
    And the GraphQL response "data.createCustomer.customer.email" should be "unconfirmed@example.com"
    Then delete customer with email "unconfirmed@example.com"

  Scenario: Create a customer and verify timestamps are set
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "TS"
        email: "timestamp.test@example.com"
        phone: "+1111111111"
        leadSource: "API"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
        confirmed: true
      }) {
        customer {
          id
          email
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
    Then delete customer with email "timestamp.test@example.com"

  Scenario: Create multiple customers sequentially
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "C1"
        email: "customer1@example.com"
        phone: "+1000000001"
        leadSource: "Campaign1"
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
    And the GraphQL response "data.createCustomer.customer.email" should be "customer1@example.com"
    When I send the following GraphQL mutation:
    """
    mutation {
      createCustomer(input: {
        initials: "C2"
        email: "customer2@example.com"
        phone: "+1000000002"
        leadSource: "Campaign2"
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
    And the GraphQL response "data.createCustomer.customer.email" should be "customer2@example.com"
    Then delete customer with email "customer1@example.com"
    Then delete customer with email "customer2@example.com"

  # ----- Update Mutation Tests -----

  Scenario: Update customer email
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        email: "updated.email@example.com"
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
    And the GraphQL response "data.updateCustomer.customer.email" should be "updated.email@example.com"
    And the GraphQL response "data.updateCustomer.customer.id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"

  Scenario: Update customer phone
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        phone: "+9999999999"
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
    And the GraphQL response "data.updateCustomer.customer.phone" should be "+9999999999"

  Scenario: Update customer initials
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        initials: "XY"
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
    And the GraphQL response "data.updateCustomer.customer.initials" should be "XY"

  Scenario: Update customer leadSource
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        leadSource: "NewCampaign"
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
    And the GraphQL response "data.updateCustomer.customer.leadSource" should be "NewCampaign"

  Scenario: Update customer confirmed status from true to false
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
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

  Scenario: Update customer type
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        type: "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER6"
      }) {
        customer {
          id
          type {
            id
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.updateCustomer.customer.type.id"
    And the GraphQL response "data.updateCustomer.customer.type.id" should contain "01JKX8XGHVDZ46MWYMZT94YER6"

  Scenario: Update customer status
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        status: "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER6"
      }) {
        customer {
          id
          status {
            id
          }
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.updateCustomer.customer.status.id"
    And the GraphQL response "data.updateCustomer.customer.status.id" should contain "01JKX8XGHVDZ46MWYMZT94YER6"

  Scenario: Update multiple customer fields at once
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send the following GraphQL mutation:
    """
    mutation {
      updateCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER5"
        initials: "MU"
        email: "multi.update@example.com"
        phone: "+5555555555"
        leadSource: "MultiUpdate"
      }) {
        customer {
          id
          initials
          email
          phone
          leadSource
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response "data.updateCustomer.customer.initials" should be "MU"
    And the GraphQL response "data.updateCustomer.customer.email" should be "multi.update@example.com"
    And the GraphQL response "data.updateCustomer.customer.phone" should be "+5555555555"
    And the GraphQL response "data.updateCustomer.customer.leadSource" should be "MultiUpdate"

  # ----- Delete Mutation Tests -----

  Scenario: Delete a customer successfully
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER7"
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER7"
      }) {
        customer {
          id
        }
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response should not have errors
    And the GraphQL response should contain "data.deleteCustomer.customer.id"

  Scenario: Delete a customer and verify it no longer exists
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER8"
    When I send the following GraphQL mutation:
    """
    mutation {
      deleteCustomer(input: {
        id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER8"
      }) {
        customer {
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
      customer(id: "/api/customers/01JKX8XGHVDZ46MWYMZT94YER8") {
        id
      }
    }
    """
    Then the GraphQL response status code should be 200
    And the GraphQL response "data.customer" should be "null"
