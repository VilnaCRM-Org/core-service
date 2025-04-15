Feature: CustomerStatus Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses for customer statuses
  As an API consumer
  I want to perform detailed validations on every endpoint (GET, POST, PUT, PATCH, DELETE) including both positive and negative cases

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  Scenario: Retrieve customer statuses collection with unsupported query parameter
    When I send a GET request to "/api/customer_statuses?unsupportedParam=value"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should exist

  Scenario: Retrieve customer statuses collection with valid pagination parameters and check JSON keys and values
    When I send a GET request to "/api/customer_statuses?page=2&itemsPerPage=50"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "view.@id" should exist

  Scenario: Retrieve customer statuses collection with default pagination and verify JSON structure
    When I send a GET request to "/api/customer_statuses"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should exist

  Scenario: Retrieve customer statuses collection filtering by value (single)
    Given customer status with value "Active" exists
    When I send a GET request to "/api/customer_statuses?value=Active"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member[0].value" should contain "Active"

  Scenario: Retrieve customer statuses collection with ordering parameters and check JSON ordering hints
    When I send a GET request to "/api/customer_statuses?order[ulid]=asc&order[value]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "view.@id" should contain "order%5Bulid%5D=asc"
    And the JSON node "view.@id" should contain "order%5Bvalue%5D=desc"

# ----- GET /api/customer_statuses/{ulid} – Single Resource (Positive Tests) -----

  Scenario: Retrieve a customer status resource with valid ulid and validate full JSON body
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_get"
    And the JSON node "value" should match "/^[A-Za-z]{2,}$/"

# ----- POST /api/customer_statuses – Create Resource (Positive Tests) -----

  Scenario: Create a customer status resource with valid payload and verify full JSON response
    When I send a POST request to "/api/customer_statuses" with body:
    """
    {
      "value": "Active"
    }
    """
    Then the response status code should be equal to 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_post"
    And the JSON node "value" should contain "Active"
    Then delete status with value "Active"

  Scenario: Create a customer status resource with additional unrecognized property which should be ignored
    When I send a POST request to "/api/customer_statuses" with body:
    """
    {
      "value": "Inactive",
      "extraField": "Should be ignored"
    }
    """
    Then the response status code should be equal to 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_post"
    And the JSON node "value" should contain "Inactive"
    And the JSON node "extraField" should not exist
    Then delete status with value "Inactive"

# ----- PUT /api/customer_statuses/{ulid} – Replace Resource (Positive Tests) -----

  Scenario: Replace a customer status resource with valid payload and verify full JSON response
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Pending"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_put"
    And the JSON node "value" should be equal to "Pending"

  Scenario: Replace a customer status resource with an extra field that should be ignored
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Archived",
      "irrelevantField": "should be ignored"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_put"
    And the JSON node "value" should contain "Archived"
    And the JSON node "irrelevantField" should not exist

# ----- PATCH /api/customer_statuses/{ulid} – Partial Update (Positive Tests) -----

  Scenario: Partially update a customer status resource's value
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Under Review"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_patch"
    And the JSON node "value" should contain "Under Review"

  Scenario: Update customer status resource with an empty patch payload (resource remains unchanged)
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    { }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_patch"
    And the JSON node "@id" should exist
    And the JSON node "@type" should contain "CustomerStatus"
    And the JSON node "ulid" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "value" should exist

# ----- DELETE /api/customer_statuses/{ulid} – Delete Resource (Positive Test) -----

  Scenario: Delete a customer status resource with valid ulid and verify empty response
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a DELETE request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 204
    And the response should be empty
    And the header "Content-Type" should not exist
    And the response should be valid according to the operation id "api_customer_statuses_ulid_delete"

###############################################################################
#                             NEGATIVE TESTS
###############################################################################

# ----- GET /api/customer_statuses – Collection (Negative Tests) -----

  Scenario: Retrieve customer statuses collection with invalid pagination parameters (non-integer)
    When I send a GET request to "/api/customer_statuses?page=abc&itemsPerPage=50"
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "detail" should contain "Page should not be less than 1"

# ----- GET /api/customer_statuses/{ulid} – Single Resource (Negative Tests) -----

  Scenario: Retrieve a non-existent customer status resource with valid ulid and receive 404 error
    When I send a GET request to "/api/customer_statuses/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_get"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not found"
    And the JSON node "type" should contain "/errors/404"

  Scenario: Retrieve a customer status resource with an invalid ulid format
    When I send a GET request to "/api/customer_statuses/invalid-ulid-format"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_get"
    And the JSON node "detail" should contain "Not Found"

# ----- POST /api/customer_statuses – Create Resource (Negative Tests) -----

  Scenario: Fail to create a customer status resource with an empty value
    When I send a POST request to "/api/customer_statuses" with body:
    """
    {
      "value": ""
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_post"
    And the JSON node "detail" should contain "value: This value should not be blank"

  Scenario: Fail to create a customer status resource with missing required field (value)
    When I send a POST request to "/api/customer_statuses" with body:
    """
    { }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_post"
    And the JSON node "detail" should contain "value: This value should not be blank"

  Scenario: Fail to create a customer status resource with too long value and check error message
    When I send a POST request to "/api/customer_statuses" with body:
    """
    {
      "value": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_post"
    And the JSON node "detail" should contain "value: This value is too long. It should have 255 characters or less."

# ----- PUT /api/customer_statuses/{ulid} – Replace Resource (Negative Tests) -----

  Scenario: Fail to replace a customer status resource with missing required field (value)
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    { }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_put"
    And the JSON node "detail" should contain "value: This value should not be blank"

  Scenario: Fail to replace a customer status resource for a non-existent ulid
    When I send a PUT request to "/api/customer_statuses/01JKX8XGXVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Updated"
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_put"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not found"
    And the JSON node "type" should contain "/errors/404"

  Scenario: Fail to replace a customer status resource with invalid ulid format
    When I send a PUT request to "/api/customer_statuses/invalid-ulid-format" with body:
    """
    {
      "value": "Updated"
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_put"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

  Scenario: Fail to update customer status resource with too long value via PATCH
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_patch"
    And the JSON node "detail" should contain "value: This value is too long. It should have 255 characters or less."

  Scenario: Fail to update customer status resource with invalid ulid format via PATCH
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customer_statuses/invalid-ulid-format" with body:
    """
    {
      "value": "Updated"
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_patch"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

  Scenario: Fail to delete a customer status resource for a non-existent ulid and check error message
    When I send a DELETE request to "/api/customer_statuses/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_delete"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not found"

  Scenario: Fail to delete a customer status resource with invalid ulid format and check error message
    When I send a DELETE request to "/api/customer_statuses/invalid-ulid-format"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_delete"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"
