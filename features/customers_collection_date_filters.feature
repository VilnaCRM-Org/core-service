Feature: Customers Collection and Resource Endpoints with Detailed JSON Validations
  In order to ensure full compliance of the Core Service API responses
  As an API consumer
  I want to perform detailed validations for date filters

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"


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
    And the JSON node "view.@id" should contain "/api/customers?ulid%5Bbetween%5D=01JKX8XGHVDZ46MWYMZT94YER2..01JKX8XGHVDZ46MWYMZT94YER4"

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
    And the JSON node "member" should have 1 element
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
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

  Scenario: Retrieve zero customers with createdAt[after] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[after]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    Then the response status code should be equal to 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be in JSON
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements

  Scenario: Retrieve zero customers with createdAt[strictly_after] filter
    Given create customer with id "01JKX8XGHVDZ46MWYMZT94YER4"
    When I send a GET data request to "/api/customers?createdAt[strictly_after]=!%date(Y-m-d\TH:i:s\Z),date_interval(P1Y)!%"
    Then the response status code should be equal to 200
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the response should be in JSON
    And the JSON node "totalItems" should be equal to the number 0
    And the JSON node "member" should have 0 elements
