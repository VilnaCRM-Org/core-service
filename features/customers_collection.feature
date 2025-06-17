Feature: Customers Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses
  As an API consumer
  I want to perform detailed validations for filtering

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    And the customers database is empty

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
    And the JSON node "view.previous" should contain "/api/customers?order%5Bulid%5D=desc&ulid%5Bgt%5D=01JKX8XGHVDZ46MWYMZT94YER5"

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
      | member[1].email | jane.doe@example.com |

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
