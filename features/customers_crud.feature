Feature: Customers Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses
  As an API consumer
  I want to perform detailed validations on every endpoint (GET, POST, PUT, PATCH, DELETE) including positive cases

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  Scenario: Retrieve a customer resource with valid ulid and validate full JSON body
    Given create customer with id 01JKX8XGHVDZ46MWYMZT94YER4
    When I send a GET request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_get"
    And the JSON node "email" should contain "@"
    And the JSON node "phone" should match "/^\+?[1-9]\d{9,14}$/"
    And the JSON node "confirmed" should be true
    And the JSON node "initials" should exist

  Scenario: Create a customer resource with valid payload and verify full JSON response
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a POST request to "/api/customers" with body:
    """
    {
      "email": "postcustomer@example.com",
      "phone": "0123456789",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "email" should contain "postcustomer@example.com"
    And the JSON node "phone" should contain "0123456789"
    And the JSON node "initials" should contain "Name Surname"
    And the JSON node "leadSource" should contain "Google"
    And the JSON node "type" should exist
    And the JSON node "status" should exist
    And the JSON node "confirmed" should be true
    Then delete customer with email "postcustomer@example.com"

  Scenario: Create a customer resource with additional unrecognized property should be rejected
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a POST request to "/api/customers" with body:
    """
    {
      "email": "extra@example.com",
      "phone": "0123456789",
      "initials": "Extra Field",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true,
      "extraField": "Should be rejected"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

#PUT /api/customers/{ulid} – Replace Resource (Positive Tests) -----

  Scenario: Replace a customer resource with valid payload and verify full JSON response
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
      "confirmed": false
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "email" should be equal to "updated@example.com"
    And the JSON node "phone" should be equal to "0987654321"
    And the JSON node "initials" should be equal to "Updated Name"
    And the JSON node "leadSource" should be equal to "Bing"
    And the JSON node "type" should exist
    And the JSON node "status" should exist
    And the JSON node "confirmed" should be false

  Scenario: Replace a customer resource with updated leadSource and initials
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "updated@example.com",
      "phone": "0123456789",
      "initials": "AB",
      "leadSource": "LinkedIn",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "email" should be equal to "updated@example.com"
    And the JSON node "phone" should be equal to "0123456789"
    And the JSON node "initials" should be equal to "AB"
    And the JSON node "leadSource" should be equal to "LinkedIn"
    And the JSON node "type" should exist
    And the JSON node "status" should exist
    And the JSON node "confirmed" should be true

  Scenario: Replace a customer resource with updated email
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "NEW.EMAIL@EXAMPLE.COM",
      "phone": "0123456789",
      "initials": "CA",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "email" should be equal to "NEW.EMAIL@EXAMPLE.COM"
    And the JSON node "phone" should be equal to "0123456789"
    And the JSON node "initials" should be equal to "CA"
    And the JSON node "leadSource" should be equal to "Google"
    And the JSON node "type" should exist
    And the JSON node "status" should exist
    And the JSON node "confirmed" should be true

  Scenario: Replace a customer resource with all updated fields (verify complete replacement)
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "completelynew@example.com",
      "phone": "0987654321",
      "initials": "CN",
      "leadSource": "Twitter",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "email" should be equal to "completelynew@example.com"
    And the JSON node "phone" should be equal to "0987654321"
    And the JSON node "initials" should be equal to "CN"
    And the JSON node "leadSource" should be equal to "Twitter"
    And the JSON node "type" should exist
    And the JSON node "status" should exist
    And the JSON node "confirmed" should be false

  Scenario: Replace a customer resource with an extra field should be rejected
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "updatedextra@example.com",
      "phone": "0987654321",
      "initials": "Updated Extra",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false,
      "irrelevantField": "should be rejected"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

# ----- PATCH /api/customers/{ulid} – Partial Update (Positive Tests) -----

  Scenario: Partially update a customer resource's phone and leadSource
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "phone": "0987654321",
      "leadSource": "Facebook"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "phone" should be equal to "0987654321"
    And the JSON node "leadSource" should contain "Facebook"

  Scenario: Partially update a customer resource's type and status references
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "type" should exist
    And the JSON node "status" should exist

  Scenario: Partially update a customer resource and verify unchanged fields remain intact
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "confirmed": false
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "confirmed" should be false
    And the JSON node "email" should exist
    And the JSON node "phone" should exist

  Scenario: Partially update a customer resource's email and phone simultaneously
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "changed@example.com",
      "phone": "0555123456"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "email" should contain "changed@example.com"
    And the JSON node "phone" should be equal to "0555123456"

  Scenario: Update customer resource with valid patch payload and verify changed JSON key
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "patched@example.com"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "email" should contain "patched@example.com"

  Scenario: Update customer resource with an empty patch payload (resource remains unchanged)
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    { }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "@id" should exist
    And the JSON node "@type" should contain "Customer"
    And the JSON node "ulid" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "email" should exist
    And the JSON node "initials" should exist
    And the JSON node "phone" should exist
    And the JSON node "leadSource" should exist
    And the JSON node "type" should exist
    And the JSON node "status" should exist
    And the JSON node "createdAt" should exist
    And the JSON node "updatedAt" should exist

  Scenario: Update customer resource with unknown properties via PATCH should be rejected
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "unknownField": "should be rejected"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

  Scenario: Delete a customer resource with valid ulid and verify empty response
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a DELETE request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 204
    And the response should be empty
    And the header "Content-Type" should not exist
    And the response should be valid according to the operation id "api_customers_ulid_delete"

  Scenario: Retrieve customers collection with invalid pagination parameters (non-integer)
    When I send a GET request to "/api/customers?page=abc&itemsPerPage=50"
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "detail" should contain "Page should not be less than 1"

# ----- GET /api/customers/{ulid} – Single Resource (Negative Tests) -----

  Scenario: Retrieve a non-existent customer resource with valid ulid and receive 404 error
    When I send a GET request to "/api/customers/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_get"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"
    And the JSON node "type" should contain "/errors/404"

  Scenario: Retrieve a customer resource with an invalid ulid format
    When I send a GET request to "/api/customers/invalid-ulid-format"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_get"
    And the JSON node "detail" should contain "Not Found"

