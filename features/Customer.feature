Feature: Customers Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses
  As an API consumer
  I want to perform detailed validations on every endpoint (GET, POST, PUT, PATCH, DELETE) including positive and negative cases

  Background:
    And I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"


  # *******************************
  # GET /api/customers – Collection (Positive Tests)
  # *******************************

  Scenario: Retrieve customers collection with valid pagination parameters and check JSON keys and values
    # (Assume that enough test customers exist in the database.)
    When I send a GET request to "/api/customers?page=2&itemsPerPage=50"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "view.@id" should exist

  Scenario: Retrieve customers collection with default pagination and verify JSON structure
    When I send a GET request to "/api/customers"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member" should exist
    And the JSON node "totalItems" should exist

  # ----- Filtering by Basic String Parameters (Positive Tests) -----

  Scenario: Retrieve customers collection filtering by initials (single value) and check JSON key and value
    Given customer with initials "JD" exists
    When I send a GET request to "/api/customers?initials=JD"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].initials" should contain "JD"

  Scenario: Retrieve customers collection filtering by initials (array values) and check JSON values
    Given customer with initials "AB" exists
    And customer with initials "CD" exists
    When I send a GET request to "/api/customers?initials[]=AB&initials[]=CD"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].initials" should match "/(AB|CD)/"

  Scenario: Retrieve customers collection filtering by email (single value) and validate JSON key
    Given customer with email "john.doe@example.com" exists
    When I send a GET request to "/api/customers?email=john.doe@example.com"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].email" should contain "john.doe@example.com"

  Scenario: Retrieve customers collection filtering by email (array values) and validate JSON values
    Given customer with email "john.doe@example.com" exists
    And customer with email "jane.doe@example.com" exists
    When I send a GET request to "/api/customers?email[]=john.doe@example.com&email[]=jane.doe@example.com"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON nodes should contain:
      | member[0].email | john.doe@example.com |
      | member[1].email | jane.doe@example.com  |

  Scenario: Retrieve customers collection filtering by phone (single value) and verify JSON key
    Given customer with phone "0123456789" exists
    When I send a GET request to "/api/customers?phone=0123456789"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].phone" should contain "0123456789"

  Scenario: Retrieve customers collection filtering by phone (array values) and verify JSON values
    Given customer with phone "0123456789" exists
    And customer with phone "0987654321" exists
    When I send a GET request to "/api/customers?phone[]=0123456789&phone[]=0987654321"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON nodes should contain:
      | member[0].phone | 0123456789 |
      | member[1].phone | 0987654321 |

  Scenario: Retrieve customers collection filtering by leadSource (single value) and check JSON
    Given customer with leadSource "Google" exists
    When I send a GET request to "/api/customers?leadSource=Google"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member[0].leadSource" should contain "Google"

  Scenario: Retrieve customers collection filtering by leadSource (array values) and check JSON
    Given customer with leadSource "Google" exists
    And customer with leadSource "Bing" exists
    When I send a GET request to "/api/customers?leadSource[]=Google&leadSource[]=Bing"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON nodes should contain:
      | member[0].leadSource | Google |
      | member[1].leadSource | Bing   |

  Scenario: Retrieve customers collection filtering by type.value and status.value and check JSON
    Given customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a GET request to "/api/customers?type.value=VIP&status.value=Active"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].type" should contain "01JKX8XGHVDZ46MWYMZT94YER4"

  # ----- Filtering by Boolean Parameter (Positive Tests) -----

  Scenario: Retrieve customers collection filtering by confirmed (single boolean) and verify JSON
    Given customer with confirmed true exists
    When I send a GET request to "/api/customers?confirmed=true"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].confirmed" should be true

  Scenario: Retrieve customers collection filtering by confirmed (array) and verify JSON
    And customer with confirmed " " exists
    Given customer with confirmed true exists
    When I send a GET request to "/api/customers?confirmed[]=true&confirmed[]"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].confirmed" should be false
    And the JSON node "member[1].confirmed" should be true

  # ----- Ordering Parameters (Positive Tests) -----

  Scenario: Retrieve customers collection with ordering parameters and check JSON ordering hints
    When I send a GET request to "/api/customers?order[ulid]=asc&order[createdAt]=desc&order[email]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "view.@id" should contain "order%5Bulid%5D=asc"
    And the JSON node "view.@id" should contain "order%5BcreatedAt%5D=desc"

  # ----- Date Filters (Positive Tests) -----

  Scenario: Retrieve customers collection with createdAt date filters and verify JSON nodes
    When I send a GET request to "/api/customers?createdAt[before]=2025-12-31T23:59:59Z&createdAt[strictly_before]=2025-12-31T23:59:59Z&createdAt[after]=2020-01-01T00:00:00Z&createdAt[strictly_after]=2020-01-01T00:00:00Z"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "view.@type" should contain "PartialCollectionView"

  Scenario: Retrieve customers collection with updatedAt date filters and verify JSON nodes
    Given customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a GET request to "/api/customers?updatedAt[before]=2025-12-31T23:59:59Z&updatedAt[strictly_before]=2025-12-31T23:59:59Z&updatedAt[after]=2020-01-01T00:00:00Z&updatedAt[strictly_after]=2020-01-01T00:00:00Z"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "view.next" should contain "01JKX8XGHVDZ46MWYMZT94YER4"

  # ----- Ulid Filter Operators (Positive Tests) -----
  Scenario: Retrieve customers collection with ulid filter operator lt and check JSON value
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a GET request to "/api/customers?ulid[lt]=01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "view.@id" should contain "/api/customers?ulid%5Blt%5D=01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"

  # Scenario for "lte"
  Scenario: Retrieve customers collection with ulid filter operator lte and check JSON value
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a GET request to "/api/customers?ulid[lte]=01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "view.@id" should contain "/api/customers?ulid%5Blte%5D=01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"

  # Scenario for "gt"
  Scenario: Retrieve customers collection with ulid filter operator gt and check JSON value
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a GET request to "/api/customers?ulid[gt]=01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "view.@id" should contain "/api/customers?ulid%5Bgt%5D=01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"

  # Scenario for "gte"
  Scenario: Retrieve customers collection with ulid filter operator gte and check JSON value
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a GET request to "/api/customers?ulid[gte]=01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "view.@id" should contain "/api/customers?ulid%5Bgte%5D=01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"

  # Scenario for "between"
  Scenario: Retrieve customers collection with ulid filter operator between and check JSON value
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a GET request to "/api/customers?ulid[between]=01JKX8XGHVDZ46MWYMZT94YER3,01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "view.@id" should contain "/api/customers?ulid%5Bbetween%5D=01JKX8XGHVDZ46MWYMZT94YER3%2C01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"

  # **************************************
  # GET /api/customers/{ulid} – Single Resource (Positive Tests)
  # **************************************

  Scenario: Retrieve a customer resource with valid ulid and validate full JSON body
    Given customer with id 01JKX8XGHVDZ46MWYMZT94YER4 exists
    When I send a GET request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_ulid_get"
    And the JSON node "email" should contain "@"
    And the JSON node "phone" should match "/^\+?[1-9]\d{9,14}$/"
    And the JSON node "confirmed" should be true
    And the JSON node "initials" should exist

  # **************************************
  # POST /api/customers – Create Resource (Positive & Negative Tests)
  # **************************************

  Scenario: Create a customer resource with valid payload and verify full JSON response
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "email" should contain "postcustomer@example.com"
    And the JSON node "phone" should contain "0123456789"
    And the JSON node "initials" should contain "Name Surname"
    And the JSON node "leadSource" should contain "Google"
    And the JSON node "confirmed" should be true
    Then delete customer with email "postcustomer@example.com"

  Scenario: Fail to create a customer resource with missing required field (email) and check error message
    Given status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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
    And the JSON node "detail" should contain "email: not.blank"

  Scenario: Fail to create a customer resource with invalid email format and check error message
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
    And the JSON node "detail" should contain "email: email.invalid"

  Scenario: Fail to create a customer resource with too long initials and check error message
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
    And the JSON node "detail" should contain "initials: This value is too long. It should have 255 characters or less."

  Scenario: Fail to create a customer resource with non-boolean confirmed and check error message
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
    And the JSON node "detail" should contain "The input data is misformatted"

  Scenario: Fail to create a customer resource with too long phone number and check error message
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
    And the JSON node "detail" should contain "This value is too long. It should have 255 characters or less"

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
    And the JSON node "detail" should contain 'No route matches "invalid-iri"'

  # **************************************
  # PUT /api/customers/{ulid} – Replace Resource (Positive & Negative Tests)
  # **************************************

  Scenario: Replace a customer resource with valid payload and verify full JSON response
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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
    And the JSON node "email" should be equal to "updated9@example.com"
    And the JSON node "phone" should be equal to "0987654321"
    And the JSON node "confirmed" should be false

  Scenario: Fail to replace a customer resource with missing required field (phone) and check error message
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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
    And the JSON node "detail" should contain "phone: not.blank"

  Scenario: Fail to replace a customer resource with invalid email format and check error message
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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
    And the JSON node "detail" should contain "email: email.invalid"

  Scenario: Fail to replace a customer resource with non-boolean confirmed and check error message
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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
    And the JSON node "detail" should contain "The input data is misformatted"

  Scenario: Fail to replace a customer resource with too long phone number and check error message
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And status with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And type with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
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
    And the JSON node "detail" should contain "This value is too long. It should have 255 characters or less."

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
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"
    And the JSON node "type" should contain "/errors/404"

  # **************************************
  # PATCH /api/customers/{ulid} – Partial Update (Positive & Negative Tests)
  # **************************************

  Scenario: Update customer resource with valid patch payload and verify changed JSON key
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "email": "patched@example.com"
      }
      """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "email" should contain "patched@example.com"

  Scenario: Fail to update customer resource with invalid email format via PATCH and check error message
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "email": "invalid-email-format"
      }
      """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the JSON node "detail" should contain "email.invalid"

  Scenario: Fail to update customer resource with non-boolean confirmed via PATCH and check error message
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "confirmed": "not-boolean"
      }
      """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the JSON node "detail" should contain "input data is misformatted"

  Scenario: Update customer resource ignoring unknown properties via PATCH and verify JSON response
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
      """
      {
        "unknownField": "should be ignored"
      }
      """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "unknownField" should not exist

  # ***************************************
  # DELETE /api/customers/{ulid} – Delete Resource (Positive & Negative Tests)
  # ***************************************

  Scenario: Delete a customer resource with valid ulid and verify empty response
    Given customer with id "01JKX8XGHVDZ46MWYMZT94YER4" exists
    When I send a DELETE request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 204
    And the response should be empty

  Scenario: Fail to delete a customer resource for a non-existent ulid and check error message
    When I send a DELETE request to "/api/customers/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the JSON node "detail" should contain "Not Found"
