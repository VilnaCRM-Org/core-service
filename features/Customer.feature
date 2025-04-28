Feature: Customers Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses
  As an API consumer
  I want to perform detailed validations on every endpoint (GET, POST, PUT, PATCH, DELETE) including both positive and negative cases

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  Scenario: Retrieve customers collection with unsupported query parameter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customers?unsupportedParam=value"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 1

  Scenario: Retrieve customers collection with valid cursor pagination parameters and check JSON keys and values
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send a GET request to "/api/customers?order[ulid]=desc&ulid[lt]=01JKX8XGHVDZ46MWYMZT94YER6"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should exist
    And the JSON node "member" should have 2 elements
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.next" should contain "/api/customers?order%5Bulid%5D=desc&ulid%5Blt%5D=01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.previous" should contain "customers?order%5Bulid%5D=desc&ulid%5Bgt%5D=01JKX8XGHVDZ46MWYMZT94YER5"

  Scenario: Retrieve customers collection with valid cursor pagination parameters and check JSON keys and values with itemsPerPage parameter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send a GET request to "/api/customers?itemsPerPage=1&order[ulid]=desc&ulid[lt]=01JKX8XGHVDZ46MWYMZT94YER6"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "member" should have 1 element
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should exist
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "view.next" should contain "/api/customers?itemsPerPage=1&order%5Bulid%5D=desc&ulid%5Blt%5D=01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "view.previous" should contain "/api/customers?itemsPerPage=1&order%5Bulid%5D=desc&ulid%5Bgt%5D=01JKX8XGHVDZ46MWYMZT94YER5"

  Scenario: Retrieve customers collection with empty and verify JSON structure
    When I send a GET request to "/api/customers"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 0

  Scenario: Retrieve customers collection filtering by initials (single value) and check JSON key and value
    Given create customer with initials "JD"
    Given create customer with initials "FJ"
    When I send a GET request to "/api/customers?initials=JD"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member[0].initials" should contain "JD"
    And the JSON node "totalItems" should be equal to the number 1

  Scenario: Retrieve customers collection filtering by initials (array values) and check JSON values
    Given create customer with initials "AB"
    Given create customer with initials "CD"
    Given create customer with initials "DC"
    When I send a GET request to "/api/customers?initials[]=AB&initials[]=CD"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member[0].initials" should match "/(AB|CD)/"
    And the JSON node "member[1].initials" should match "/(AB|CD)/"
    And the JSON node "totalItems" should be equal to the number 2

  Scenario: Retrieve customers collection filtering by email (single value) and validate JSON key
    Given create customer with email "john.doe@example.com"
    Given create customer with email "jane.doe@example.com"
    When I send a GET request to "/api/customers?email=john.doe@example.com"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member[0].email" should contain "john.doe@example.com"
    And the JSON node "totalItems" should be equal to the number 1

  Scenario: Retrieve customers collection filtering by email (array values) and validate JSON values
    Given create customer with email "john.doe@example.com"
    And create customer with email "jane.doe@example.com"
    And create customer with email "jake.doe@example.com"
    When I send a GET request to "/api/customers?email[]=john.doe@example.com&email[]=jane.doe@example.com"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON nodes should contain:
      | member[0].email | john.doe@example.com |
      | member[1].email | jane.doe@example.com  |

  Scenario: Retrieve customers collection filtering by phone (single value) and verify JSON key
    Given create customer with phone "0123456789"
    Given create customer with phone "3806312833"
    When I send a GET request to "/api/customers?phone=0123456789"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member[0].phone" should contain "0123456789"

  Scenario: Retrieve customers collection filtering by phone (array values) and verify JSON values
    Given create customer with phone "0123456789"
    And create customer with phone "0987654321"
    And create customer with phone "3806312833"
    When I send a GET request to "/api/customers?phone[]=0123456789&phone[]=0987654321"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON nodes should contain:
      | member[0].phone | 0123456789 |
      | member[1].phone | 0987654321 |

  Scenario: Retrieve customers collection filtering by leadSource (single value) and check JSON
    Given create customer with leadSource "Google"
    Given create customer with leadSource "Reddit"
    When I send a GET request to "/api/customers?leadSource=Google"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member[0].leadSource" should contain "Google"

  Scenario: Retrieve customers collection filtering by leadSource (array values) and check JSON
    Given create customer with leadSource "Google"
    And create customer with leadSource "Bing"
    And create customer with leadSource "Reddit"
    When I send a GET request to "/api/customers?leadSource[]=Google&leadSource[]=Bing"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "totalItems" should be equal to the number 2
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON nodes should contain:
      | member[0].leadSource | Google |
      | member[1].leadSource | Bing   |

  Scenario: Retrieve customers collection filtering by status.value and check JSON
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with type value "VIP" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET request to "/api/customers?status.value=Active"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member[0].type" should contain "01JKX8XGHVDZ46MWYMZT94YER4"

  Scenario: Retrieve customers collection filtering by status.value and check JSON
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with type value "VIP" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    Given create customer with type value "VIP" and status value "Expired" and id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send a GET request to "/api/customers?status.value[]=Active&status.value[]=Inactive"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "member[0].type" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "member[1].type" should contain "01JKX8XGHVDZ46MWYMZT94YER5"

  Scenario: Retrieve customers collection filtering by type.value and check JSON
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with type value "Premium" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET request to "/api/customers?type.value=VIP"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member[0].type" should contain "01JKX8XGHVDZ46MWYMZT94YER4"

  Scenario: Retrieve customers collection filtering by type.value and check JSON
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with type value "Premium" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    Given create customer with type value "Plus" and status value "Expired" and id "01JKX8XGHVDZ46MWYMZT94YER6"
    When I send a GET request to "/api/customers?type.value[]=VIP&type.value[]=Premium"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "member[0].type" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "member[1].type" should contain "01JKX8XGHVDZ46MWYMZT94YER5"

  Scenario: Retrieve customers collection filtering by confirmed (single boolean) and verify JSON
    Given create customer with confirmed true
    Given create customer with confirmed false
    When I send a GET request to "/api/customers?confirmed=true"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member[0].confirmed" should be true

  Scenario: Retrieve customers collection filtering by confirmed (array) and verify JSON
    Given create customer with confirmed true
    Given create customer with confirmed false
    When I send a GET request to "/api/customers?confirmed[]=true&confirmed[]"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "member[0].confirmed" should be true
    And the JSON node "member[1].confirmed" should be false

  Scenario: Retrieve customers collection filtering by email domain and sorted by email ascending
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    And create customer with email "charlie@test.com"
    When I send a GET request to "/api/customers?email[]=alice@example.com&email[]=bob@example.com&order[email]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "member[0].email" should contain "alice@example.com"
    And the JSON node "member[1].email" should contain "bob@example.com"
    And the JSON node "view.@id" should contain "order%5Bemail%5D=asc"

  Scenario: Retrieve customers collection sorted by email in descending order
    Given create customer with email "alice@example.com"
    And create customer with email "bob@example.com"
    When I send a GET request to "/api/customers?email[]=alice@example.com&email[]=bob@example.com&order[email]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "member[0].email" should contain "bob@example.com"
    And the JSON node "member[1].email" should contain "alice@example.com"
    And the JSON node "view.@id" should contain "order%5Bemail%5D=desc"

  Scenario: Retrieve customers collection filtering by confirmed false and sorted by initials ascending
    Given create customer with initials "BB"
    And create customer with initials "AA"
    When I send a GET request to "/api/customers?order[initials]=asc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "member[0].initials" should contain "AA"
    And the JSON node "member[1].initials" should contain "BB"
    And the JSON node "view.@id" should contain "/api/customers?order%5Binitials%5D=asc"

  Scenario: Retrieve customers collection filtering sorted by initials descending
    Given create customer with initials "BB"
    And create customer with initials "AA"
    When I send a GET request to "/api/customers?order[initials]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "member[0].initials" should contain "BB"
    And the JSON node "member[1].initials" should contain "AA"
    And the JSON node "view.@id" should contain "order%5Binitials%5D=desc"

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
    And the JSON node "member[0].type" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "member[1].type" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Btype.value%5D=asc"

  Scenario: Retrieve customers collection sorted by type.value in descending order
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with type value "Basic" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET request to "/api/customers?order[type.value]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].type" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "member[1].type" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
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
    And the JSON node "member[0].status" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "member[1].status" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bstatus.value%5D=asc"

  Scenario: Retrieve customers collection sorted by status.value in descending order
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    Given create customer with type value "Premium" and status value "Inactive" and id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET request to "/api/customers?order[status.value]=desc"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "member[0].status" should contain "01JKX8XGHVDZ46MWYMZT94YER5"
    And the JSON node "member[1].status" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "totalItems" should be equal to the number 2
    And the JSON node "view.@id" should contain "order%5Bstatus.value%5D=desc"

  Scenario: Retrieve customers collection with updatedAt date filters and verify JSON nodes
    Given create customer with type value "VIP" and status value "Active" and id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customers?updatedAt[before]=2025-12-31T23:59:59Z&updatedAt[strictly_before]=2025-12-31T23:59:59Z&updatedAt[after]=2020-01-01T00:00:00Z&updatedAt[strictly_after]=2020-01-01T00:00:00Z"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "view.next" should contain "01JKX8XGHVDZ46MWYMZT94YER4"

  Scenario: Retrieve customers collection using ULID filter operator "lt" (less than)
  This filter should return only customers whose ULIDs are lesser than the given value
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customers?ulid[lt]=01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "totalItems" should be equal to the number 3
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[2].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER3"
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"
    And the JSON node "view.@id" should contain "ulid%5Blt%5D=01JKX8XGHVDZ46MWYMZT94YER4"

  Scenario: Retrieve customers collection using ULID filter operator "lte" (less than or equal)
  This operator should include the customer whose ULID exactly matches or lesser than the given value
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customers?ulid[lte]=01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[2].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER3"
    And the JSON node "member[3].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "totalItems" should be equal to the number 4
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"
    And the JSON node "view.@id" should contain "ulid%5Blte%5D=01JKX8XGHVDZ46MWYMZT94YER4"

  Scenario: Retrieve customers collection using ULID filter operator "gt" (greater than)
  This filter should return only customers whose ULIDs are greater than the given value
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customers?ulid[gt]=01JKX8XGHVDZ46MWYMZT94YER1"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER3"
    And the JSON node "member[2].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "totalItems" should be equal to the number 3
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"
    And the JSON node "view.@id" should contain "ulid%5Bgt%5D=01JKX8XGHVDZ46MWYMZT94YER1"

  Scenario: Retrieve customers collection using ULID filter operator "gte" (greater than or equal)
  In this scenario, the filter returns the customer with the given ULID plus all with higher values
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customers?ulid[gte]=01JKX8XGHVDZ46MWYMZT94YER1"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER1"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[2].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER3"
    And the JSON node "member[3].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "totalItems" should be equal to the number 4
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"
    And the JSON node "view.@id" should contain "ulid%5Bgte%5D=01JKX8XGHVDZ46MWYMZT94YER1"

  Scenario: Retrieve customers collection using ULID filter operator "between"
  This filter checks if the ULID values fall between two given boundaries (inclusive)
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER1"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER2"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER3"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET request to "/api/customers?ulid[between]=01JKX8XGHVDZ46MWYMZT94YER2..01JKX8XGHVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should exist
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER2"
    And the JSON node "member[1].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER3"
    And the JSON node "member[2].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "totalItems" should be equal to the number 3
    And the JSON node "@id" should be equal to "/api/customers"
    And the JSON node "@type" should be equal to "Collection"
    And the JSON node "view.@id" should contain "/customers?ulid%5Bbetween%5D=01JKX8XGHVDZ46MWYMZT94YER2..01JKX8XGHVDZ46MWYMZT94YER4"

  Scenario: Retrieve customers collection with updatedAt[before] filter and verify JSON nodes
    # A customer is created and its updatedAt (set to now) will be before a future date (now + 1 year).
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?updatedAt[before]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member" should have 1 element
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.@type" should contain "PartialCollectionView"

  Scenario: Retrieve customers collection with updatedAt[strictly_before] filter and verify JSON nodes
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?updatedAt[strictly_before]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member" should have 1 element
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.@type" should contain "PartialCollectionView"

  Scenario: Retrieve customers collection with updatedAt[after] filter and verify JSON nodes
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?updatedAt[after]=!%date(Y-m-d\TH:i:s\Z),date_interval(-P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member" should have 1 element
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.@type" should contain "PartialCollectionView"

  Scenario: Retrieve customers collection with updatedAt[strictly_after] filter and verify JSON nodes
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?updatedAt[strictly_after]=!%date(Y-m-d\TH:i:s\Z),date_interval(-P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member" should have 1 element
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.@type" should contain "PartialCollectionView"

  Scenario: Retrieve zero customers with updatedAt[before] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET data request to "/api/customers?updatedAt[before]=!%date(Y-m-d\TH:i:s\Z),date_interval(-P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

  Scenario: Retrieve zero customers with updatedAt[strictly_before] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET data request to "/api/customers?updatedAt[strictly_before]=!%date(Y-m-d\TH:i:s\Z),date_interval(-P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

  Scenario: Retrieve zero customers with updatedAt[after] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET data request to "/api/customers?updatedAt[after]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

  Scenario: Retrieve zero customers with updatedAt[strictly_after] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER5"
    When I send a GET data request to "/api/customers?updatedAt[strictly_after]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

  Scenario: Retrieve customers collection with createdAt date filters and verify JSON nodes
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[before]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member" should have 1 elements
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.@type" should contain "PartialCollectionView"

  Scenario: Retrieve customers collection with createdAt[strictly_before] filter and verify JSON nodes
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[strictly_before]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member" should have 1 element
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.@type" should contain "PartialCollectionView"

  Scenario: Retrieve customers collection with createdAt[after] filter and verify JSON nodes
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[after]=!%date(Y-m-d\TH:i:s\Z),date_interval(-P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 1
    And the JSON node "member" should have 1 element
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.@type" should contain "PartialCollectionView"

  Scenario: Retrieve customers collection with createdAt[strictly_after] filter and verify JSON nodes
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[strictly_after]=!%date(Y-m-d\TH:i:s\Z),date_interval(-P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "member" should have 1 element
    And the JSON node "member[0].@id" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "view.@type" should contain "PartialCollectionView"

  Scenario: Retrieve zero customers with createdAt[before] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[before]=!%date(Y-m-d\TH:i:s\Z),date_interval(-P1Y)!%"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the response should be valid according to the operation id "api_customers_get_collection"
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

  Scenario: Retrieve zero customers with createdAt[strictly_before] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[strictly_before]=!%date(Y-m-d\TH:i:s\Z),date_interval(-P1Y)!%"
    Then the response status code should be equal to 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be in JSON
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

  Scenario: Retrieve zero customers with createdAt[after] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[after]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

  Scenario: Retrieve zero customers with createdAt[strictly_after] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[strictly_after]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

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
    And the JSON node "type" should contain "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "status" should contain "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "confirmed" should be true
    Then delete customer with email "postcustomer@example.com"

  Scenario: Create a customer resource with additional unrecognized property which should be ignored
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
      "extraField": "Should be ignored"
    }
    """
    Then the response status code should be equal to 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "email" should contain "extra@example.com"
    And the JSON node "phone" should contain "0123456789"
    And the JSON node "initials" should contain "Extra Field"
    And the JSON node "leadSource" should contain "Google"
    And the JSON node "type" should contain "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "status" should contain "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "confirmed" should be true
    And the JSON node "extraField" should not exist
    Then delete customer with email "extra@example.com"

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
    And the JSON node "type" should be equal to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "status" should be equal to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
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
    And the JSON node "type" should be equal to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "status" should be equal to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
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
    And the JSON node "type" should be equal to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "status" should be equal to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
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
    And the JSON node "type" should be equal to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "status" should be equal to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "confirmed" should be false

  Scenario: Replace a customer resource while including an extra field that should be ignored
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
      "irrelevantField": "should be ignored"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "email" should be equal to "updatedextra@example.com"
    And the JSON node "phone" should be equal to "0987654321"
    And the JSON node "initials" should be equal to "Updated Extra"
    And the JSON node "leadSource" should be equal to "Bing"
    And the JSON node "type" should be equal to "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "status" should be equal to "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "confirmed" should be false

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
    And the JSON node "type" should contain "01JKX8XGHVDZ46MWYMZT94YER4"
    And the JSON node "status" should contain "01JKX8XGHVDZ46MWYMZT94YER4"

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

  Scenario: Update customer resource ignoring unknown properties via PATCH and verify JSON response
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "unknownField": "should be ignored"
    }
    """
    Then the response status code should be equal to 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "unknownField" should not exist

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

# ----- POST /api/customers – Create Resource (Negative Tests) -----

  Scenario: Fail to create a customer resource with duplicate email
    Given create customer with email "duplicate@example.com"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a POST request to "/api/customers" with body:
    """
    {
      "email": "duplicate@example.com",
      "phone": "0123456789",
      "initials": "Duplicate User",
      "leadSource": "Referral",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "email: This email address is already registered"

  Scenario: Create a customer resource with an empty initials field
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a POST request to "/api/customers" with body:
    """
    {
      "email": "extra@example.com",
      "phone": "0123456789",
      "initials": "  ",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "initials: Initials can not consist only of spaces"

  Scenario: Fail to create a customer resource with missing required field (email) and check error message
    Given create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
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
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "email: This value should not be blank"

  Scenario: Fail to create a customer resource with invalid email format and check error message
    When I send a POST request to "/api/customers" with body:
    """
    {
      "email": "invalid-email",
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
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "email: This value is not a valid email address."

  Scenario: Fail to create a customer resource with too long initials and check error message
    When I send a POST request to "/api/customers" with body:
    """
    {
      "email": "customer@example.com",
      "phone": "0123456789",
      "initials": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "initials: This value is too long. It should have 255 characters or less."

  Scenario: Fail to create a customer resource with non-boolean confirmed and check error message
    When I send a POST request to "/api/customers" with body:
    """
    {
      "email": "customer@example.com",
      "phone": "0123456789",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": "yes"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "The input data is misformatted"

  Scenario: Fail to create a customer resource with too long phone number and check error message
    When I send a POST request to "/api/customers" with body:
    """
    {
      "email": "customer@example.com",
      "phone": "1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "This value is too long. It should have 255 characters or less"

  Scenario: Fail to create a customer resource with not valid email address
    When I send a POST request to "/api/customers" with body:
    """
    {
      "email": "212213",
      "phone": "+324312232",
      "initials": "Name Surname",
      "leadSource": "Google",
      "type": "/api/customer_types/valid-type-id",
      "status": "/api/customer_statuses/valid-status-id",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain "email: This value is not a valid email address"

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
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_post"
    And the JSON node "detail" should contain 'No route matches "invalid-iri"'

  Scenario: Fail to replace a customer resource with duplicate email
    Given create customer with email "existing@example.com"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "existing@example.com",
      "phone": "0987654321",
      "initials": "Updated Duplicate",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "email: This email address is already registered"

  Scenario: Replace a customer resource with an empty initials field
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "extra@example.com",
      "phone": "0123456789",
      "initials": "  ",
      "leadSource": "Google",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": true
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "initials: Initials can not consist only of spaces"

  Scenario: Fail to replace a customer resource with missing required field (phone) and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
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
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "phone: This value should not be blank"

  Scenario: Fail to replace a customer resource with invalid email format and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
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
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "email: This value is not a valid email address"

  Scenario: Fail to replace a customer resource with non-boolean confirmed and check error message
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
      "confirmed": "non-bulean"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "The input data is misformatted"

  Scenario: Fail to replace a customer resource with invalid type and status references and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
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
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain 'No route matches "invalid-iri"'

  Scenario: Fail to replace a customer resource with too long phone number and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create status with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And create type with id "01JKX8XGHVDZ46MWYMZT94YER4"
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
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "This value is too long. It should have 255 characters or less."

  Scenario: Fail to update customer resource with initials exceeding maximum length via PUT
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PUT request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "updated@example.com",
      "phone": "0987654321",
      "initials": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
      "leadSource": "Bing",
      "type": "/api/customer_types/01JKX8XGHVDZ46MWYMZT94YER4",
      "status": "/api/customer_statuses/01JKX8XGHVDZ46MWYMZT94YER4",
      "confirmed": false
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "detail" should contain "initials: This value is too long. It should have 255 characters or less."

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
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"
    And the JSON node "type" should contain "/errors/404"

  Scenario: Fail to replace a customer resource with invalid ulid format and check error message
    When I send a PUT request to "/api/customers/invalid-ulid-format" with body:
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
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_put"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

  Scenario: Partially update a customer resource with an empty initials field
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "initials": "  "
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "initials: Initials can not consist only of spaces"

  Scenario: Fail to update customer resource with malformed JSON payload via PATCH
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    { "email": "malformed@example.com",
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Syntax error"

  Scenario: Fail to patch a customer resource with invalid type and status references and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "type": "invalid-iri",
      "status": "invalid-iri"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain 'No route matches "invalid-iri"'

  Scenario: Fail to patch a customer resource with duplicate email
    Given create customer with email "existing@example.com"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    And create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "existing@example.com"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "email: This email address is already registered"

  Scenario: Fail to update customer resource with initials exceeding maximum length via PATCH
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "initials": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "initials: This value is too long. It should have 255 characters or less."

  Scenario: Fail to update customer resource with phone exceeding maximum length via PATCH
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "phone": "+37903203203203200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320320320320032032049379032032032032003203204937903200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320320320320032032049379032032032032003203204937903203203203200320320493790320032032049"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "phone: This value is too long. It should have 255 characters or less."

  Scenario: Fail to update customer resource with invalid email format via PATCH and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "email": "invalid-email-format"
    }
    """
    Then the response status code should be equal to 422
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "email: This value is not a valid email address."

  Scenario: Fail to update customer resource with non-boolean confirmed via PATCH and check error message
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/01JKX8XGHVDZ46MWYMZT94YER4" with body:
    """
    {
      "confirmed": "not-boolean"
    }
    """
    Then the response status code should be equal to 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "detail" should contain "input data is misformatted"

  Scenario: Fail to replace a customer resource with invalid ulid format and check error message (PATCH case)
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/api/customers/invalid-ulid-format" with body:
    """
    {
      "confirmed": false
    }
    """
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_patch"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

# ----- DELETE /api/customers/{ulid} – Delete Resource (Negative Tests) -----

  Scenario: Fail to delete a customer resource for a non-existent ulid and check error message
    When I send a DELETE request to "/api/customers/01JKX8XGXVDZ46MWYMZT94YER4"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_delete"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"

  Scenario: Fail to delete a customer resource with invalid ulid format and check error message
    When I send a DELETE request to "/api/customers/invalid-ulid-format"
    Then the response status code should be equal to 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the response should be valid according to the operation id "api_customers_ulid_delete"
    And the JSON node "title" should contain "An error occurred"
    And the JSON node "detail" should contain "Not Found"