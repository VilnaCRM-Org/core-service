# Advanced Configuration

Welcome to the Advanced Configuration Guide for the Core Service. This guide is designed to help you customize and optimize your setup beyond the basic installation steps.

## Environment Variables

### Configuration

The Core Service utilizes environment variables for configuration to ensure that sensitive information is not hard-coded into the application. Here are the environment variables you can configure:

- `APP_ENV`: Specifies the environment in which the application is running.
- `APP_SECRET`: A secret key used for cryptographic purposes, such as generating CSRF tokens or signing cookies.
- `DB_USER`: The username for the MongoDB database.
- `DB_PASSWORD`: The password for the MongoDB database.
- `DB_PORT`: The port on which the MongoDB database is running.
- `DB_VERSION`: The MongoDB version.
- `DB_URL`: The URL for connecting to the MongoDB database, including the username, password, host, and port.
- `EMAIL_QUEUE_NAME`: The name of the queue for sending emails.
- `AWS_SQS_VERSION`: The AWS SQS API version.
- `AWS_SQS_REGION`: The AWS region for SQS.
- `AWS_SQS_ENDPOINT_BASE`: The base endpoint for AWS SQS (use `localstack` for local development).
- `AWS_SQS_KEY`: The AWS access key.
- `AWS_SQS_SECRET`: The AWS secret key.
- `LOCALSTACK_PORT`: The port on which Localstack is running.
- `MESSENGER_TRANSPORT_DSN`: The DSN (Data Source Name) for the messenger transport, configured to use Amazon SQS via Localstack for sending emails.
- `STRUCTURIZR_PORT`: The port on which Structurizr is running (for architecture diagrams).
- `CADDY_MERCURE_JWT_SECRET`: The JWT secret for Caddy Mercure integration.
- `SERVER_NAME`: The server name for the application.

Learn more about [Symfony Environment Variables](https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-env-files)

### Managing different environments

You can use `.env.test` and `.env.prod` to override variables for other environments.

- **`.env.test`**: Contains environment variables for the testing environment. Use this file to set configurations that should only apply when running tests, such as database connections, API endpoints, and service credentials that are different from your production settings.

- **`.env.prod`**: Holds environment variables for the production environment. This file should include configurations for your live application, such as database URLs, third-party API keys, and any other variables that your application needs to run in production.

#### Best Practices

1. Never commit your `.env.prod` file to version control. This file will likely contain sensitive information that should not be exposed publicly.

2. While your `.env.prod` file should not be committed to version control, your `.env.test` file can be if it does not contain sensitive information. This helps maintain consistency across testing environments.

## Configuring Load Tests

The Core Service includes a comprehensive suite for load testing its endpoints. The configuration for these tests is defined in a JSON file (`tests/Load/config.json.dist`). Below is a guide on how to configure general settings and specific endpoint settings for load testing.

### General Settings

First of all, there are settings common for each testing script. Here is their breakdown:

- `apiHost`: Specifies the hostname for the API to make requests to.
- `apiPort`: Specifies the port for the API to be added to a host.
- `batchSize`: Specifies the batch size, used for inserted entities before script execution.
- `delayBetweenScenarios`: Specifies the delay (in seconds) between scenarios execution.
- `resultsDirectory`: Specifies the directory for storing load test results.
- `customersFileName`: Specifies the name of a `.json` file which contains the data of inserted customers.
- `customersFileLocation`: Specifies the location of a `.json` file with customers, relative to a `/tests/Load` folder.
- `customerTypesFileName`: Specifies the name of a `.json` file for customer types.
- `customerTypesFileLocation`: Specifies the location of the customer types file.
- `customerStatusesFileName`: Specifies the name of a `.json` file for customer statuses.
- `customerStatusesFileLocation`: Specifies the location of the customer statuses file.

### Endpoint Settings

Each endpoint testing config has some common settings. Here is their breakdown:

- `setupTimeoutInMinutes`: Specifies the time (in minutes) for setting up the load testing environment for each script before it will be executed.
- `teardownTimeoutInMinutes`: Specifies the time (in minutes) for finishing the load test script after execution.

- `smoke`: Configuration for smoke testing.
  - `threshold`: Specifies the threshold for response time (in milliseconds).
  - `rps`: Specifies the requests per second (RPS) for the smoke test.
  - `vus`: Specifies the virtual users (VUs) for the smoke test.
  - `duration`: Specifies the duration of the smoke test (in seconds).

- `average`: Configuration for average load testing.
  - `threshold`: Specifies the threshold for response time (in milliseconds).
  - `rps`: Specifies the requests per second (RPS) for average load testing.
  - `vus`: Specifies the virtual users (VUs) for average load testing.
  - `duration`: Specifies the duration of each phase of the load test:
    - `rise`: The duration of the ramp-up phase (in seconds).
    - `plateau`: The duration of the plateau phase (in seconds).
    - `fall`: The duration of the ramp-down phase (in seconds).

- `stress`: Configuration for stress testing.
  - `threshold`: Specifies the threshold for response time (in milliseconds).
  - `rps`: Specifies the requests per second (RPS) for stress testing.
  - `vus`: Specifies the virtual users (VUs) for stress testing.
  - `duration`: Specifies the duration of each phase of the load test:
    - `rise`: The duration of the ramp-up phase (in seconds).
    - `plateau`: The duration of the plateau phase (in seconds).
    - `fall`: The duration of the ramp-down phase (in seconds).

- `spike`: Configuration for spike testing.
  - `threshold`: Specifies the threshold for response time (in milliseconds).
  - `rps`: Specifies the requests per second (RPS) for spike testing.
  - `vus`: Specifies the virtual users (VUs) for spike testing.
  - `duration`: Specifies the duration of each phase of the spike test:
    - `rise`: The duration of the spike ramp-up phase (in seconds).
    - `fall`: The duration of the spike ramp-down phase (in seconds).

Learn more about [Load testing with K6](https://grafana.com/docs/k6/latest/javascript-api/k6/)

### Collection testing settings

Testing of endpoints that return collection requires additional settings, such as a number of items to get with each request:

- `customersToGetInOneRequest`: Amount of customers to retrieve with each request.

### Available Load Test Endpoints

The Core Service has load tests configured for the following endpoints:

#### REST API

- `health` - Health check endpoint
- `createCustomer`, `getCustomer`, `getCustomers`, `updateCustomer`, `replaceCustomer`, `deleteCustomer`
- `createCustomerType`, `getCustomerType`, `getCustomerTypes`, `updateCustomerType`, `deleteCustomerType`
- `createCustomerStatus`, `getCustomerStatus`, `getCustomerStatuses`, `updateCustomerStatus`, `deleteCustomerStatus`

#### GraphQL

- `graphQLGetCustomer`, `graphQLGetCustomers`, `graphQLCreateCustomer`, `graphQLUpdateCustomer`, `graphQLDeleteCustomer`
- `graphQLGetCustomerType`, `graphQLGetCustomerTypes`, `graphQLCreateCustomerType`, `graphQLUpdateCustomerType`, `graphQLDeleteCustomerType`
- `graphQLGetCustomerStatus`, `graphQLGetCustomerStatuses`, `graphQLCreateCustomerStatus`, `graphQLUpdateCustomerStatus`, `graphQLDeleteCustomerStatus`

Learn more about [Community and Support](community-and-support.md).
