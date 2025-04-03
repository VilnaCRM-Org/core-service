Feature: Customers Collection Endpoint
  In order to retrieve a collection of customer resources from the Core Service API
  As an API consumer
  I want to send GET requests to the /api/customers endpoint and validate the responses using the OpenAPI specification

  Background:
    And I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  Scenario: Successfully retrieve customers collection with valid query parameters
    When I send a GET request to "/api/customers?page=1&itemsPerPage=30"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection with default pagination parameters
    When I send a GET request to "/api/customers"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection with filtering parameters
    When I send a GET request to "/api/customers?initials=JS&email[]=user1@example.com&email[]=user2@example.com"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  # Negative scenarios

  Scenario: Fail to retrieve customers collection with an invalid page parameter
    When I send a GET request to "/api/customers?page=abc&itemsPerPage=30"
    Then the response status code should be equal to 400
    And the response should be in JSON

  Scenario: Fail to retrieve customers collection with a negative itemsPerPage value
    When I send a GET request to "/api/customers?page=1&itemsPerPage=-5"
    Then the response status code should be equal to 400
    And the response should be in JSON

  Scenario: Successfully create a customer resource with valid payload
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a POST request to "/api/customers" with body:
      """
      {
        "email": "postcustomer192@example.com",
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
    And the response should be valid according to the operation id "api_customers_post"

  Scenario: Fail to create a customer resource with missing required field (email)
    When I send a POST request to "/api/customers" with body:
      """
      {
        "phone": "0123456789",
        "initials": "Name Surname",
        "leadSource": "Google",
        "type": "/api/customer_types/valid-type-id",
        "status": "/api/customer_statuses/valid-status-id",
        "confirmed": true
      }
      """
    Then the response status code should be equal to 422
    And the response should be in JSON

  Scenario: Fail to create a customer resource with invalid email format
    When I send a POST request to "/api/customers" with body:
      """
      {
        "email": "invalid-email",
        "phone": "0123456789",
        "initials": "Name Surname",
        "leadSource": "Google",
        "type": "/api/customer_types/valid-type-id",
        "status": "/api/customer_statuses/valid-status-id",
        "confirmed": true
      }
      """
    Then the response status code should be equal to 422
    And the response should be in JSON

  Scenario: Fail to create a customer resource with too long initials
    When I send a POST request to "/api/customers" with body:
      """
      {
        "email": "customer@example.com",
        "phone": "0123456789",
        "initials": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
        "leadSource": "Google",
        "type": "/api/customer_types/valid-type-id",
        "status": "/api/customer_statuses/valid-status-id",
        "confirmed": true
      }
      """
    Then the response status code should be equal to 422
    And the response should be in JSON

  Scenario: Fail to create a customer resource with non-boolean confirmed value
    When I send a POST request to "/api/customers" with body:
      """
      {
        "email": "customer@example.com",
        "phone": "0123456789",
        "initials": "Name Surname",
        "leadSource": "Google",
        "type": "/api/customer_types/valid-type-id",
        "status": "/api/customer_statuses/valid-status-id",
        "confirmed": "yes"
      }
      """
    Then the response status code should be equal to 400
    And the response should be in JSON


  Scenario: Successfully retrieve a customer resource with valid ulid
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a GET request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_ulid_get"

  Scenario: Fail to retrieve a customer resource for a non-existent ulid
    When I send a GET request to "/api/customers/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON

  Scenario: Successfully replace a customer resource with valid payload
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "email": "updated9@example.com",
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
    And the response should be valid according to the operation id "api_customers_ulid_put"

  Scenario: Fail to replace a customer resource with missing required field (phone)
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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

  Scenario: Fail to replace a customer resource with invalid email format
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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

  Scenario: Fail to replace a customer resource with non-boolean confirmed value
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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

  Scenario: Fail to replace a customer resource for a non-existent ulid
    When I send a PUT request to "/api/customers/01JKX8XGXVDZ46MWYMZT94YER4" with body:
      """
      {
        "email": "updated@example.com",
        "phone": "0987654321",
        "initials": "Updated Name",
        "leadSource": "Bing",
        "type": "/api/customer_types/valid-type-id",
        "status": "/api/customer_statuses/valid-status-id",
        "confirmed": false
      }
      """
    Then the response status code should be equal to 404
    And the response should be in JSON

  Scenario: Successfully delete a customer resource with valid ulid
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a DELETE request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 204
    And the response should be empty

  Scenario: Fail to delete a customer resource for a non-existent ulid
    When I send a DELETE request to "/api/customers/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON
