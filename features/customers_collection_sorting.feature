Feature: Customers Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses
  As an API consumer
  I want to perform detailed validations for sorting

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  Scenario: Retrieve customers collection sorted by leadSource in ascending order
    Given create customer with leadSource "Bing"
    And create customer with leadSource "Google"
    When I send a GET request to "/api/customers?order[leadSource]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].leadSource" should contain "Bing"
    And the JSON node "member[1].leadSource" should contain "Google"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5BleadSource%5D=asc"

  Scenario: Retrieve customers collection sorted by leadSource in descending order
    Given create customer with leadSource "Bing"
    And create customer with leadSource "Google"
    When I send a GET request to "/api/customers?order[leadSource]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].leadSource" should contain "Google"
    And the JSON node "member[1].leadSource" should contain "Bing"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5BleadSource%5D=desc"

  Scenario: Retrieve customers collection sorted by ulid in ascending order
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customers?order[ulid]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bulid%5D=asc"

  Scenario: Retrieve customers collection sorted by ulid in descending order
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customers?order[ulid]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bulid%5D=desc"

  Scenario: Retrieve customers collection sorted by createdAt in ascending order
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customers?order[createdAt]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5BcreatedAt%5D=asc"

  Scenario: Retrieve customers collection sorted by createdAt in descending order
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customers?order[createdAt]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5BcreatedAt%5D=desc"

  Scenario: Retrieve customers collection sorted by updatedAt in ascending order
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customers?order[updatedAt]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5BupdatedAt%5D=asc"

  Scenario: Retrieve customers collection sorted by updatedAt in descending order
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    When I send a GET request to "/api/customers?order[updatedAt]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5BupdatedAt%5D=desc"

  Scenario: Retrieve customers collection sorted by phone in ascending order
    Given create customer with phone "0123456789"
    And create customer with phone "0987654321"
    When I send a GET request to "/api/customers?order[phone]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].phone" should contain "0123456789"
    And the JSON node "member[1].phone" should contain "0987654321"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bphone%5D=asc"

  Scenario: Retrieve customers collection sorted by phone in descending order
    Given create customer with phone "0123456789"
    And create customer with phone "0987654321"
    When I send a GET request to "/api/customers?order[phone]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].phone" should contain "0987654321"
    And the JSON node "member[1].phone" should contain "0123456789"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bphone%5D=desc"

  Scenario: Retrieve customers collection sorted by type.value in ascending order
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with type value "Basic" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET request to "/api/customers?order[type.value]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Btype.value%5D=asc"

  Scenario: Retrieve customers collection sorted by type.value in descending order
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with type value "Basic" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET request to "/api/customers?order[type.value]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Btype.value%5D=desc"

  # ---------------------------------------------------------------------------
  # Sorting by status.value
  # ---------------------------------------------------------------------------
  Scenario: Retrieve customers collection sorted by status.value in ascending order
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with type value "Premium" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET request to "/api/customers?order[status.value]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bstatus.value%5D=asc"
