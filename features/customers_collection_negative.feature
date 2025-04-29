Feature: Customers Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses
  As an API consumer
  I want to perform detailed validations on every endpoint (GET, POST, PUT, PATCH, DELETE) including negative cases

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

# ----- POST /api/customers – Create Resource (Negative Tests) -----

  Scenario: Fail to create a customer resource with duplicate email
    Given create customer with email "duplicate@example.com"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a POST request to "/api/customers" with body:
"""
    {
      "email": "duplicate@example.com",
      "phone": "0123456789",
      "initials": "Duplicate User",
      "leadSource": "Referral",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "email: This email address is already registered"

  Scenario: Create a customer resource with an empty initials field
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a POST request to "/api/customers" with body:
"""
    {
      "email": "extra@example.com",
      "phone": "0123456789",
      "initials": "  ",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "initials: Initials can not consist only of spaces"

  Scenario: Fail to create a customer resource with missing required field (email) and check error message
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a POST request to "/api/customers" with body:
"""
    {
      "phone": "0123456789",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "email: This value should not be blank"

  Scenario: Fail to create a customer resource with invalid email format and check error message
    When I send a POST request to "/api/customers" with body:
"""
    {
      "email": "invalid-email",
      "phone": "0123456789",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "email: This value is not a valid email address."

  Scenario: Fail to create a customer resource with too long initials and check error message
    When I send a POST request to "/api/customers" with body:
"""
    {
      "email": "customer@example.com",
      "phone": "0123456789",
      "initials": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "initials: This value is too long. It should have 255 characters or less."

  Scenario: Fail to create a customer resource with non-boolean confirmed and check error message
    When I send a POST request to "/api/customers" with body:
"""
    {
      "email": "customer@example.com",
      "phone": "0123456789",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": "yes"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "The input data is misformatted"

  Scenario: Fail to create a customer resource with too long phone number and check error message
    When I send a POST request to "/api/customers" with body:
"""
    {
      "email": "customer@example.com",
      "phone": "1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "This value is too long. It should have 255 characters or less"

  Scenario: Fail to create a customer resource with not valid email address
    When I send a POST request to "/api/customers" with body:
"""
    {
      "email": "212213",
      "phone": "+324312232",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "/api/customer_types/valid-type-id",
      "status": "/api/customer_statuses/valid-status-id",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "email: This value is not a valid email address"

  Scenario: Fail to create a customer resource with invalid type and status references and check error message
    When I send a POST request to "/api/customers" with body:
"""
    {
      "email": "customer@example.com",
      "phone": "0123456789",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "invalid-iri",
      "status": "invalid-iri",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain 'No route matches "invalid-iri"'

  Scenario: Fail to replace a customer resource with duplicate email
    Given create customer with email "existing@example.com"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "existing@example.com",
      "phone": "0987654321",
      "initials": "Updated Duplicate",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "email: This email address is already registered"

  Scenario: Replace a customer resource with an empty initials field
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "extra@example.com",
      "phone": "0123456789",
      "initials": "  ",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "initials: Initials can not consist only of spaces"

  Scenario: Fail to replace a customer resource with missing required field (phone) and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "updated@example.com",
      "initials": "Updated Name",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "phone: This value should not be blank"

  Scenario: Fail to replace a customer resource with invalid email format and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "invalid-email",
      "phone": "0987654321",
      "initials": "Updated Name",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "email: This value is not a valid email address"

  Scenario: Fail to replace a customer resource with non-boolean confirmed and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "updated@example.com",
      "phone": "0987654321",
      "initials": "Updated Name",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": "non-bulean"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "The input data is misformatted"

  Scenario: Fail to replace a customer resource with invalid type and status references and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "customer@example.com",
      "phone": "0123456789",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "invalid-iri",
      "status": "invalid-iri",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain 'No route matches "invalid-iri"'

  Scenario: Fail to replace a customer resource with too long phone number and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "updated@example.com",
      "phone": "1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890",
      "initials": "Updated Name",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "This value is too long. It should have 255 characters or less."

  Scenario: Fail to update customer resource with initials exceeding maximum length via PUT
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "updated@example.com",
      "phone": "0987654321",
      "initials": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "initials: This value is too long. It should have 255 characters or less."

  Scenario: Fail to replace a customer resource for a non-existent ulid
    When I send a PUT request to "/api/customers/01JKX8XGXVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "updated@example.com",
      "phone": "0987654321",
      "initials": "Updated Name",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"
    And the JSON node "type" should contain "/errors/404"

  Scenario: Fail to replace a customer resource with invalid ulid format and check error message
    When I send a PUT request to "/api/customers/invalid-ulid-format" with body:
"""
    {
      "email": "updated@example.com",
      "phone": "0987654321",
      "initials": "Updated Name",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

  Scenario: Partially update a customer resource with an empty initials field
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "initials": "  "
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "initials: Initials can not consist only of spaces"

  Scenario: Fail to update customer resource with malformed JSON payload via PATCH
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    { "email": "malformed@example.com",
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Syntax error"

  Scenario: Fail to patch a customer resource with invalid type and status references and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "type": "invalid-iri",
      "status": "invalid-iri"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain 'No route matches "invalid-iri"'

  Scenario: Fail to patch a customer resource with duplicate email
    Given create customer with email "existing@example.com"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "existing@example.com"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "email: This email address is already registered"

  Scenario: Fail to update customer resource with initials exceeding maximum length via PATCH
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "initials": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "initials: This value is too long. It should have 255 characters or less."

  Scenario: Fail to update customer resource with phone exceeding maximum length via PATCH
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "phone": "+37903203203203200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320320320320032032049379032032032032003203204937903200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320032032049"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "phone: This value is too long. It should have 255 characters or less."

  Scenario: Fail to update customer resource with invalid email format via PATCH and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "email": "invalid-email-format"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "email: This value is not a valid email address."

  Scenario: Fail to update customer resource with non-boolean confirmed via PATCH and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
"""
    {
      "confirmed": "not-boolean"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "input data is misformatted"

  Scenario: Fail to replace a customer resource with invalid ulid format and check error message (PATCH case)
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/invalid-ulid-format" with body:
"""
    {
      "confirmed": false
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

# ----- DELETE /api/customers/{ulid} – Delete Resource (Negative Tests) -----

  Scenario: Fail to delete a customer resource for a non-existent ulid and check error message
    When I send a DELETE request to "/api/customers/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_delete"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

  Scenario: Fail to delete a customer resource with invalid ulid format and check error message
    When I send a DELETE request to "/api/customers/invalid-ulid-format"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_delete"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"