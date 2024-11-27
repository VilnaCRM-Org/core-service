Feature: Database Negative

Scenario: Checking the health when the database is unavailable
Given negative the database is not available
When GET negative request is sent to "/api/health"
Then negative the response status code should be 500
And negative the response body should contain "No suitable servers found"