Feature: CustomerType Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses for customer types
  As an API consumer
  I want to perform detailed validations on every endpoint (GET, POST, PUT, PATCH, DELETE) including both positive and negative cases

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  Scenario: Retrieve customer types collection with unsupported query parameter
    When I send a GET request to "/api/customer_types?unsupportedParam=value"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 0

  Scenario: Retrieve customer types collection with default GET request and verify JSON structure
    When I send a GET request to "/api/customer_types"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 0

  Scenario: Retrieve customer types collection with valid cursor pagination parameters and check JSON keys and values
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER5"
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send a GET request to "/api/customer_types?order[ulid]=desc&ulid[lt]=01JKX8XGHVDZ46MWYMZT94YER6"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should exist
    And the JSON node "member" should have 2 elements
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.next" should contain "/api/customer_types?order%5Bulid%5D=desc&ulid%5Blt%5D=01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.previous" should contain "/api/customer_types?order%5Bulid%5D=desc&ulid%5Bgt%5D=01JKX8XGHVDZ46MWYMZT94YER5"

  Scenario: Retrieve customer types collection with itemsPerPage parameter and cursor pagination
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER5"
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send a GET request to "/api/customer_types?itemsPerPage=1&order[ulid]=desc&ulid[lt]=01JKX8XGHVDZ46MWYMZT94YER6"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member" should exist
    And the JSON node "member" should have 1 element
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should exist
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "view.next" should contain "/api/customer_types?itemsPerPage=1&order%5Bulid%5D=desc&ulid%5Blt%5D=01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "view.previous" should contain "/api/customer_types?itemsPerPage=1&order%5Bulid%5D=desc&ulid%5Bgt%5D=01JKX8XGHVDZ46MWYMZT94YER5"

  Scenario: Retrieve customer types collection with default pagination and verify JSON structure
    When I send a GET request to "/api/customer_types"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 0

  Scenario: Retrieve customer types collection filtering by value (single)
    Given create customer type with value "Prospect"
    When I send a GET request to "/api/customer_types?value=Prospect"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member[0].value" should contain "Prospect"
    And the JSON node "totalItems" should be equal to the number 1

  Scenario: Retrieve customer types collection sorted by ulid in ascending order
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER1"
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customer_types?order[ulid]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bulid%5D=asc"

  Scenario: Retrieve customer types collection sorted by ulid in descending order
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER1"
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customer_types?order[ulid]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bulid%5D=desc"

  Scenario: Retrieve customer types collection sorted by value in ascending order
    Given create customer type with value "Prospect"
    Given create customer type with value "Regular"
    When I send a GET request to "/api/customer_types?order[value]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member[0].value" should contain "Prospect"
    And the JSON node "member[1].value" should contain "Regular"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bvalue%5D=asc"

  Scenario: Retrieve customer types collection sorted by value in descending order
    Given create customer type with value "Prospect"
    Given create customer type with value "Regular"
    When I send a GET request to "/api/customer_types?order[value]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "member[0].value" should contain "Regular"
    And the JSON node "member[1].value" should contain "Prospect"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bvalue%5D=desc"

  Scenario: Retrieve a customer type resource with valid ulid and validate full JSON body
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_get"
    And the JSON node "value" should match "/^[A-Za-z]{2,}$/"

  Scenario: Create a customer type resource with valid payload and verify full JSON response
    When I send a POST request to "/api/customer_types" with body:
    """
    {
      "value": "Prospect"
    }
    """
    Then the response status code should be equal to 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_post"
    And the JSON node "value" should contain "Prospect"
    Then delete type with value "Prospect"

  Scenario: Create a customer type resource with additional unrecognized property which should be ignored
    When I send a POST request to "/api/customer_types" with body:
    """
    {
      "value": "Lead",
      "extraField": "Should be ignored"
    }
    """
    Then the response status code should be equal to 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_post"
    And the JSON node "value" should contain "Lead"
    And the JSON node "extraField" should not exist
    Then delete type with value "Lead"

# ----- PUT /api/customer_types/{ulid} – Replace Resource (Positive Tests) -----

  Scenario: Replace a customer type resource with valid payload and verify full JSON response
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Qualified"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_put"
    And the JSON node "value" should be equal to "Qualified"

  Scenario: Replace a customer type resource while including an extra field that should be ignored
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Converted",
      "irrelevantField": "should be ignored"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_put"
    And the JSON node "value" should contain "Converted"
    And the JSON node "irrelevantField" should not exist

# ----- PATCH /api/customer_types/{ulid} – Partial Update (Positive Tests) -----

  Scenario: Partially update a customer type resource's value
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Nurtured"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_patch"
    And the JSON node "value" should contain "Nurtured"

  Scenario: Update customer type resource with an empty patch payload (resource remains unchanged)
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    { }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_patch"
    And the JSON node "@id" should exist
    And the JSON node "@type" should contain "CustomerType"
    And the JSON node "ulid" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "value" should exist

# ----- DELETE /api/customer_types/{ulid} – Delete Resource (Positive Test) -----

  Scenario: Delete a customer type resource with valid ulid and verify empty response
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a DELETE request to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 204
    And the response should be empty
    And the header "Content-Type" should not exist
    And the response should be valid according to the operation id "api_customer_types_ulid_delete"

  Scenario: Retrieve customer types collection with invalid pagination parameters (non-integer)
    When I send a GET request to "/api/customer_types?page=abc&itemsPerPage=50"
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_get_collection"
    And the JSON node "detail" should contain "Page should not be less than 1"

  Scenario: Retrieve a non-existent customer type resource with valid ulid and receive 404 error
    When I send a GET request to "/api/customer_types/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_get"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not found"
    And the JSON node "type" should contain "/errors/404"

  Scenario: Retrieve a customer type resource with an invalid ulid format
    When I send a GET request to "/api/customer_types/invalid-ulid-format"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_get"
    And the JSON node "detail" should contain "Not Found"

# ----- POST /api/customer_types – Create Resource (Negative Tests) -----

  Scenario: Fail to create a customer type resource with an empty value
    When I send a POST request to "/api/customer_types" with body:
    """
    {
      "value": ""
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_post"
    And the JSON node "detail" should contain "value: This value should not be blank"

  Scenario: Fail to create a customer type resource with missing required field (value)
    When I send a POST request to "/api/customer_types" with body:
    """
    { }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_post"
    And the JSON node "detail" should contain "value: This value should not be blank"

  Scenario: Fail to create a customer type resource with too long value and check error message
    When I send a POST request to "/api/customer_types" with body:
    """
    {
      "value": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_post"
    And the JSON node "detail" should contain "value: This value is too long. It should have 255 characters or less."

  Scenario: Fail to replace a customer type resource with missing required field (value)
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    { }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_put"
    And the JSON node "detail" should contain "value: This value should not be blank"

  Scenario: Fail to replace a customer type resource for a non-existent ulid
    When I send a PUT request to "/api/customer_types/01JKX8XGXVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Updated"
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_put"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not found"
    And the JSON node "type" should contain "/errors/404"

  Scenario: Fail to replace a customer type resource with invalid ulid format
    When I send a PUT request to "/api/customer_types/invalid-ulid-format" with body:
    """
    {
      "value": "Updated"
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_put"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

  Scenario: Fail to update customer type resource with too long value via PATCH
    Given create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_patch"
    And the JSON node "detail" should contain "value: This value is too long. It should have 255 characters or less."

  Scenario: Fail to update customer type resource with invalid ulid format via PATCH
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customer_types/invalid-ulid-format" with body:
    """
    {
      "value": "Updated"
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_patch"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

  Scenario: Fail to delete a customer type resource for a non-existent ulid and check error message
    When I send a DELETE request to "/api/customer_types/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_delete"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

  Scenario: Fail to delete a customer type resource with invalid ulid format and check error message
    When I send a DELETE request to "/api/customer_types/invalid-ulid-format"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_types_ulid_delete"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"
