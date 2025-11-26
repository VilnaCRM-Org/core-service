Feature: CustomerStatus Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses for customer statuses
  As an API consumer
  I want to perform detailed validations on every endpoint (GET, POST, PUT, PATCH, DELETE) including negative cases

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
    And the JSON node "totalItems" should be equal to the number 0

  Scenario: Retrieve customer statuses collection with default GET request and verify JSON structure
    When I send a GET request to "/api/customer_statuses"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 0

  Scenario: Retrieve customer statuses collection with valid cursor pagination parameters and check JSON keys and values
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER5"
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send a GET request to "/api/customer_statuses?order[ulid]=desc&ulid[lt]=01JKX8XGHVDZ46MWYMZT94YER6"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should exist
    And the JSON node "member" should have 2 elements
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.next" should contain "/api/customer_statuses?order%5Bulid%5D=desc&ulid%5Blt%5D=01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.previous" should contain "/api/customer_statuses?order%5Bulid%5D=desc&ulid%5Bgt%5D=01JKX8XGHVDZ46MWYMZT94YER5"

  Scenario: Retrieve customer statuses collection with itemsPerPage parameter and cursor pagination
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER5"
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send a GET request to "/api/customer_statuses?itemsPerPage=1&order[ulid]=desc&ulid[lt]=01JKX8XGHVDZ46MWYMZT94YER6"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member" should exist
    And the JSON node "member" should have 1 element
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should exist
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "view.next" should contain "/api/customer_statuses?itemsPerPage=1&order%5Bulid%5D=desc&ulid%5Blt%5D=01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "view.previous" should contain "/api/customer_statuses?itemsPerPage=1&order%5Bulid%5D=desc&ulid%5Bgt%5D=01JKX8XGHVDZ46MWYMZT94YER5"

  Scenario: Retrieve customer statuses collection filtering by value (single)
    Given create customer status with value "Active"
    Given create customer status with value "Inactive"
    When I send a GET request to "/api/customer_statuses?value=Active"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member" should have 1 element

  Scenario: Retrieve customer statuses collection sorted by ulid in ascending order
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER1"
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customer_statuses?order[ulid]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bulid%5D=asc"

  Scenario: Retrieve customer statuses collection sorted by ulid in descending order
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER1"
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customer_statuses?order[ulid]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bulid%5D=desc"

  Scenario: Retrieve customer statuses collection sorted by value in ascending order
    Given create customer status with value "Draft"
    Given create customer status with value "Published"
    When I send a GET request to "/api/customer_statuses?order[value]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member" should have 2 elements
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bvalue%5D=asc"

  Scenario: Retrieve customer statuses collection sorted by value in descending order
    Given create customer status with value "Draft"
    Given create customer status with value "Published"
    When I send a GET request to "/api/customer_statuses?order[value]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_get_collection"
    And the JSON node "member" should have 2 elements
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bvalue%5D=desc"

  # ----- GET /api/customer_statuses/{ulid} – Single Resource (Positive Tests) -----

  Scenario: Retrieve a customer status resource with valid ulid and validate full JSON body
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customer_statuses_ulid_get"
    And the JSON node "value" should exist

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
    And the JSON node "ulid" should exist
    And the JSON node "@id" should exist
    And the JSON node "@type" should be equal to "CustomerStatus"
    And the JSON node "value" should be equal to "Active"
    Then delete status with value "Active"

  Scenario: Create a customer status resource with additional unrecognized property should be rejected
    When I send a POST request to "/api/customer_statuses" with body:
    """
    {
      "value": "Inactive",
      "extraField": "Should be rejected"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "title" should exist
    And the JSON node "detail" should exist

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

  Scenario: Replace a customer status resource with an extra field should be rejected
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Archived",
      "irrelevantField": "should be rejected"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "title" should exist
    And the JSON node "detail" should exist

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
    When I send a GET request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the JSON node "value" should be equal to "Under Review"

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
    When I send a GET request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the JSON node "ulid" should be equal to "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "value" should exist

  # ----- DELETE /api/customer_statuses/{ulid} – Delete Resource (Positive Test) -----

  Scenario: Delete a customer status resource with valid ulid and verify empty response
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a DELETE request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 204
    And the response should be empty
    And the header "Content-Type" should not exist
    And the response should be valid according to the operation id "api_customer_statuses_ulid_delete"
    When I send a GET request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404

