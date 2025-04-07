Feature: Customers Collection and Resource Endpoints
  In order to manage customer resources in the Core Service API
  As an API consumer
  I want to send requests to /api/customers endpoints and validate the responses using the OpenAPI specification

  Background:
    And I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  # *******************************
  # GET /api/customers – Collection
  # *******************************

  Scenario: Successfully retrieve customers collection with valid pagination parameters
    When I send a GET request to "/api/customers?page=2&itemsPerPage=50"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection with default pagination parameters
    When I send a GET request to "/api/customers"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  # ----- Filtering by Basic String Parameters -----

  Scenario: Successfully retrieve customers collection filtering by initials (single value)
    When I send a GET request to "/api/customers?initials=AB"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by initials (array values)
    When I send a GET request to "/api/customers?initials[]=AB&initials[]=CD"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by email (single value)
    When I send a GET request to "/api/customers?email=john.doe@example.com"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by email (array values)
    When I send a GET request to "/api/customers?email[]=john.doe@example.com&email[]=jane.doe@example.com"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by phone (single value)
    When I send a GET request to "/api/customers?phone=0123456789"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by phone (array values)
    When I send a GET request to "/api/customers?phone[]=0123456789&phone[]=0987654321"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by leadSource (single value)
    When I send a GET request to "/api/customers?leadSource=Google"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by leadSource (array values)
    When I send a GET request to "/api/customers?leadSource[]=Google&leadSource[]=Bing"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by type.value (single value)
    When I send a GET request to "/api/customers?type.value=VIP"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by type.value (array values)
    When I send a GET request to "/api/customers?type.value[]=VIP&type.value[]=Regular"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by status.value (single value)
    When I send a GET request to "/api/customers?status.value=Active"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by status.value (array values)
    When I send a GET request to "/api/customers?status.value[]=Active&status.value[]=Inactive"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  # ----- Filtering by Boolean Parameter -----

  Scenario: Successfully retrieve customers collection filtering by confirmed (single boolean)
    When I send a GET request to "/api/customers?confirmed=true"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection filtering by confirmed (array of booleans)
    When I send a GET request to "/api/customers?confirmed[]=true&confirmed[]=false"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  # ----- Ordering Parameters -----

  Scenario: Successfully retrieve customers collection with ordering parameters on multiple fields
    When I send a GET request to "/api/customers?order[ulid]=asc&order[createdAt]=desc&order[updatedAt]=asc&order[email]=asc&order[phone]=desc&order[leadSource]=asc&order[type.value]=desc&order[status.value]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  # ----- Date Filters -----

  Scenario: Successfully retrieve customers collection with createdAt date filters
    When I send a GET request to "/api/customers?createdAt[before]=2025-12-31T23:59:59Z&createdAt[strictly_before]=2025-12-31T23:59:59Z&createdAt[after]=2020-01-01T00:00:00Z&createdAt[strictly_after]=2020-01-01T00:00:00Z"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection with updatedAt date filters
    When I send a GET request to "/api/customers?updatedAt[before]=2025-12-31T23:59:59Z&updatedAt[strictly_before]=2025-12-31T23:59:59Z&updatedAt[after]=2020-01-01T00:00:00Z&updatedAt[strictly_after]=2020-01-01T00:00:00Z"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  # ----- Ulid Filter Operators -----

  Scenario: Successfully retrieve customers collection with ulid filter operator lt
    When I send a GET request to "/api/customers?ulid[lt]=01ABCDEF1234567890"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection with ulid filter operator lte
    When I send a GET request to "/api/customers?ulid[lte]=01ABCDEF1234567890"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection with ulid filter operator gt
    When I send a GET request to "/api/customers?ulid[gt]=01ABCDEF1234567890"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection with ulid filter operator gte
    When I send a GET request to "/api/customers?ulid[gte]=01ABCDEF1234567890"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  Scenario: Successfully retrieve customers collection with ulid filter operator between
    When I send a GET request to "/api/customers?ulid[between]=01ABCDEF1234567890,01ABCDEF1234567899"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"

  # **************************************
  # GET /api/customers/{ulid} – Single Resource
  # **************************************

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

  # ***************************************
  # POST /api/customers – Create Resource
  # ***************************************

  Scenario: Successfully create a customer resource with valid payload
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a POST request to "/api/customers" with body:
      """
      {
        "email": "postcustomer19412@example.com",
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

  Scenario: Fail to create a customer resource with too long phone number
    When I send a POST request to "/api/customers" with body:
      """
      {
        "email": "customer@example.com",
        "phone": "1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890",
        "initials": "Name Surname",
        "leadSource": "Google",
        "type": "/api/customer_types/valid-type-id",
        "status": "/api/customer_statuses/valid-status-id",
        "confirmed": true
      }
      """
    Then the response status code should be equal to 422
    And the response should be in JSON

  Scenario: Fail to create a customer resource with invalid type and status references
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
    Then the response status code should be equal to 422
    And the response should be in JSON

  Scenario: Successfully create a customer resource with extra optional fields
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a POST request to "/api/customers" with body:
      """
      {
        "email": "extracustomer1@example.com",
        "phone": "0123456789",
        "initials": "Name Surname",
        "leadSource": "Google",
        "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
        "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
        "confirmed": true,
        "extraField": "ExtraValue"
      }
      """
    Then the response status code should be equal to 201
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_post"

  # ***************************************
  # PUT /api/customers/{ulid} – Replace Resource
  # ***************************************

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

  Scenario: Fail to replace a customer resource with too long phone number
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    Given type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "email": "updated@example.com",
        "phone": "12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890",
        "initials": "Updated Name",
        "leadSource": "Bing",
        "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
        "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
        "confirmed": false
      }
      """
    Then the response status code should be equal to 422
    And the response should be in JSON

  Scenario: Fail to replace a customer resource with invalid type and status references
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "email": "updated@example.com",
        "phone": "0987654321",
        "initials": "Updated Name",
        "leadSource": "Bing",
        "type": "invalid-iri",
        "status": "invalid-iri",
        "confirmed": false
      }
      """
    Then the response status code should be equal to 422
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

  # ***************************************
  # PATCH /api/customers/{ulid} – Partial Update
  # ***************************************

  Scenario: Successfully update customer resource with valid patch payload
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "email": "patched@example.com"
      }
      """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_ulid_patch"

  Scenario: Fail to update customer resource with invalid email format via PATCH
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "email": "invalid-email-format"
      }
      """
    Then the response status code should be equal to 422
    And the response should be in JSON

  Scenario: Fail to update customer resource with non-boolean confirmed via PATCH
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "confirmed": "not-boolean"
      }
      """
    Then the response status code should be equal to 400
    And the response should be in JSON

  Scenario: Successfully update customer resource ignoring unknown properties via PATCH
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "unknownField": "should be ignored"
      }
      """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_ulid_patch"

  # ***************************************
  # DELETE /api/customers/{ulid} – Delete Resource
  # ***************************************

  Scenario: Successfully delete a customer resource with valid ulid
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a DELETE request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 204
    And the response should be empty

  Scenario: Fail to delete a customer resource for a non-existent ulid
    When I send a DELETE request to "/api/customers/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON

  Scenario: Fail to delete a customer resource with invalid ulid format
    When I send a DELETE request to "/api/customers/invalid-ulid-format"
    Then the response status code should be equal to 400
    And the response should be in JSON
