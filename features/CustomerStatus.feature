Feature: Customer Statuses Collection Endpoint
  In order to manage a collection of customer status resources
  As an API consumer
  I want to send API requests to /api/customer_statuses and validate the
  responses using the OpenAPI specification

Background:
  And I add "Accept" header equal to "application/ld+json"
  And I add "Content-Type" header equal to "application/ld+json"

Scenario: Successfully retrieve customer statuses collection with valid
  query parameters
  When I send a GET request to "/api/customer_statuses?page=1&itemsPerPage=30"
  Then the response status code should be equal to 200
  And the response should be in JSON
  And the response should be valid according to the operation id "api_customer_statuses_get_collection"

Scenario: Successfully retrieve customer statuses collection with default
  pagination parameters
  When I send a GET request to "/api/customer_statuses"
  Then the response status code should be equal to 200
  And the response should be in JSON
  And the response should be valid according to the operation id "api_customer_statuses_get_collection"

Scenario: Successfully retrieve customer statuses collection with filtering
  parameters
  When I send a GET request to "/api/customer_statuses?value=Active"
  Then the response status code should be equal to 200
  And the response should be in JSON
  And the response should be valid according to the operation id "api_customer_statuses_get_collection"

Scenario: Fail to retrieve customer statuses collection with an invalid page
  parameter
  When I send a GET request to "/api/customer_statuses?page=abc&itemsPerPage=30"
  Then the response status code should be equal to 400
  And the response should be in JSON

Scenario: Fail to retrieve customer statuses collection with a negative
  itemsPerPage value
  When I send a GET request to "/api/customer_statuses?page=1&itemsPerPage=-5"
  Then the response status code should be equal to 400
  And the response should be in JSON

Scenario: Successfully create a customer status resource with valid payload
  When I send a POST request to "/api/customer_statuses" with body:
    """
    {
      "value": "Active"
    }
    """
  Then the response status code should be equal to 201
  And the response should be in JSON
  And the response should be valid according to the operation id "api_customer_statuses_post"

Scenario: Fail to create a customer status resource with missing required
  field (value)
  When I send a POST request to "/api/customer_statuses" with body:
    """
    {
    }
    """
  Then the response status code should be equal to 422
  And the response should be in JSON

Scenario: Fail to create a customer status resource with too long value
  When I send a POST request to "/api/customer_statuses" with body:
    """
    {
      "value": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
    }
    """
  Then the response status code should be equal to 422
  And the response should be in JSON
  And the JSON node "detail" should contain "value: This value is too long. It should have 255 characters or less"

Scenario: Successfully retrieve a customer status resource with valid ulid
  When I send a GET request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
  Then the response status code should be equal to 200
  And the response should be in JSON
  And the response should be valid according to the operation id "api_customer_statuses_ulid_get"

Scenario: Fail to retrieve a customer status resource for a non-existent ulid
  When I send a GET request to "/api/customer_statuses/01JKX8GXVDZ46MWYMZT94YER4"
  Then the response status code should be equal to 404
  And the response should be in JSON

Scenario: Successfully replace a customer status resource with valid payload
  When I send a PUT request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Inactive"
    }
    """
  Then the response status code should be equal to 200
  And the response should be in JSON
  And the response should be valid according to the operation id "api_customer_statuses_ulid_put"

Scenario: Fail to replace a customer status resource with missing required
  field (value)
  When I send a PUT request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
    }
    """
  Then the response status code should be equal to 422
  And the response should be in JSON

Scenario: Fail to replace a customer status resource with too long value
  When I send a PUT request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
    }
    """
  Then the response status code should be equal to 422
  And the response should be in JSON
  And the JSON node "detail" should contain "value: This value is too long. It should have 255 characters or less"

Scenario: Fail to replace a customer status resource for a non-existent ulid
  When I send a PUT request to "/api/customer_statuses/01JKX8GXVDZ46MWYMZT94YER4" with body:
    """
    {
      "value": "Inactive"
    }
    """
  Then the response status code should be equal to 404
  And the response should be in JSON

Scenario: Successfully delete a customer status resource with valid ulid
  When I send a DELETE request to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
  Then the response status code should be equal to 204
  And the response should be empty

Scenario: Fail to delete a customer status resource for a non-existent ulid
  When I send a DELETE request to "/api/customer_statuses/01JKX8GXVDZ46MWYMZT94YER4"
  Then the response status code should be equal to 404
  And the response should be in JSON 