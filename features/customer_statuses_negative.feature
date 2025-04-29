Feature: CustomerStatus Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses for customer statuses
  As an API consumer
  I want to perform detailed validations on every endpoint (GET, POST, PUT, PATCH, DELETE) including positive cases

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

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

  # ----- PATCH /api/customer_statuses/{ulid} – Partial Update (Negative Tests) -----

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

  # ----- DELETE /api/customer_statuses/{ulid} – Delete Resource (Negative Test) -----

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
