# Performance and Optimization

Welcome to the **Performance and Optimization** GitHub page, which is dedicated to showcasing our comprehensive approach to enhancing the efficiency and speed of our Core Service application. Our goal is to share insights, methodologies, and results from rigorous testing and optimization processes to help developers achieve peak performance in their own applications.

> **📊 Local Development Benchmarks**
>
> The performance data in this document was collected from local development environment testing using `make execute-load-tests-script`. For production-grade benchmarks, run tests on AWS c6i.4xlarge (or equivalent infrastructure) using `make aws-load-tests`.
>
> **Local test environment:** Docker containers on development machine
> **Test date:** 2025-12-19

## Testing Environment

To ensure our Core Service is optimized for high performance, testing should be conducted using standardized infrastructure on AWS. This approach allows identification of bottlenecks, code optimization, and achievement of significant performance improvements. By utilizing AWS for the testing environment, consistency and uniformity across all tests is ensured, enabling developers to work with the same setup and achieve comparable results. This unified testing framework provides a reliable foundation for evaluating performance, regardless of geographic location or hardware variations, ensuring all team members can collaborate effectively on optimization efforts. Below are the details of the recommended hardware and software components for the testing environment:

### Server Specifications:

- **Instance Type:** c6i.4xlarge
- **CPU:** 16 core
- **Memory:** 32 GB RAM
- **Storage:** 30 GB

### Software Specifications:

- **Operating System:** Ubuntu 24.04 LTS
- **Core Service Version:** 0.8.0
- **PHP Version:** 8.3
- **Symfony Version:** 7.2
- **Database:** MongoDB 6.0
- **Load Testing Tools:** Grafana K6

## Benchmarks

> **Note:** The values in the tables below are **placeholder estimates** pending actual load testing. Run `make smoke-load-tests` on appropriate infrastructure and update with real measurements.

Here you will find the results of load tests for each Core Service endpoint, with a graph, that shows how execution parameters were changing over time for different load scenarios. Also, the metric for Spike testing will be provided, alongside a table, that will show the most important of them.

Each endpoint was tested for smoke, average, stress, and spike load scenarios. You can learn more about them [here](https://grafana.com/docs/k6/latest/testing-guides/test-types/).
Load test results (including optional HTML reports) are generated locally and stored under `tests/Load/results/` (not committed). See [Load Testing Results](../tests/Load/README.md#results) for details.

The most important metrics for each test, which you'll find in tables include:

- **Target rps:** This number specifies the max requests per second rate, that will be reached during the test.
- **Real rps:** This number specifies the average requests per second rate, that was reached during a testing scenario.
- **Virtual users:** The number of simulated users accessing the service simultaneously. This helps in understanding how the application performs under different levels of user concurrency.
- **Rise duration:** The time period over which the load is gradually increased from zero to the desired number of requests per second or virtual users. This helps to observe how the system scales with increasing load.
- **Plateau duration:** The time period over which the load is holding the peak load. This helps to monitor the system's ability to handle the constant load gracefully.
- **Fall duration:** The time period over which the load is gradually decreased back to zero from the peak load. This helps to monitor the system's ability to gracefully handle the reduction in load and ensure there are no residual issues.
- **P(99):** This number specifies the time, which it took for 99% of requests to receive a successful response.

### REST API

- [Health](#Health-Test)
- [Get Customer](#Get-Customer-Test)
- [Get Customers](#Get-Customers-Test)
- [Create Customer](#Create-Customer-Test)
- [Update Customer](#Update-Customer-Test)
- [Replace Customer](#Replace-Customer-Test)
- [Delete Customer](#Delete-Customer-Test)
- [Get Customer Type](#Get-Customer-Type-Test)
- [Get Customer Types](#Get-Customer-Types-Test)
- [Create Customer Type](#Create-Customer-Type-Test)
- [Update Customer Type](#Update-Customer-Type-Test)
- [Delete Customer Type](#Delete-Customer-Type-Test)
- [Get Customer Status](#Get-Customer-Status-Test)
- [Get Customer Statuses](#Get-Customer-Statuses-Test)
- [Create Customer Status](#Create-Customer-Status-Test)
- [Update Customer Status](#Update-Customer-Status-Test)
- [Delete Customer Status](#Delete-Customer-Status-Test)

### GraphQL

- [Get Customer](#GraphQL-Get-Customer-Test)
- [Get Customers](#GraphQL-Get-Customers-Test)
- [Create Customer](#GraphQL-Create-Customer-Test)
- [Update Customer](#GraphQL-Update-Customer-Test)
- [Delete Customer](#GraphQL-Delete-Customer-Test)
- [Get Customer Type](#GraphQL-Get-Customer-Type-Test)
- [Get Customer Types](#GraphQL-Get-Customer-Types-Test)
- [Create Customer Type](#GraphQL-Create-Customer-Type-Test)
- [Update Customer Type](#GraphQL-Update-Customer-Type-Test)
- [Delete Customer Type](#GraphQL-Delete-Customer-Type-Test)
- [Get Customer Status](#GraphQL-Get-Customer-Status-Test)
- [Get Customer Statuses](#GraphQL-Get-Customer-Statuses-Test)
- [Create Customer Status](#GraphQL-Create-Customer-Status-Test)
- [Update Customer Status](#GraphQL-Update-Customer-Status-Test)
- [Delete Customer Status](#GraphQL-Delete-Customer-Status-Test)

### Health Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 40       | 200           | 10s           | 10s           | 8ms   |

[Go back to navigation](#REST-API)

### Get Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 6ms   |

[Go back to navigation](#REST-API)

### Get Customers Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Customers retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ------------------------------------- | ----- |
| 10         | 8        | 10            | 10s           | 10s           | 50                                    | 43ms  |

[Go back to navigation](#REST-API)

### Create Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 24       | 200           | 10s           | 10s           | 50ms  |

[Go back to navigation](#REST-API)

### Update Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 60ms  |

[Go back to navigation](#REST-API)

### Replace Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 55ms  |

[Go back to navigation](#REST-API)

### Delete Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 47       | 400           | 10s           | 10s           | 10ms  |

[Go back to navigation](#REST-API)

### Get Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 5ms   |

[Go back to navigation](#REST-API)

### Get Customer Types Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Types retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | --------------------------------- | ----- |
| 10         | 5        | 10            | 10s           | 10s           | 50                                | 29ms  |

[Go back to navigation](#REST-API)

### Create Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 30       | 200           | 10s           | 10s           | 40ms  |

[Go back to navigation](#REST-API)

### Update Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 28       | 200           | 10s           | 10s           | 45ms  |

[Go back to navigation](#REST-API)

### Delete Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 50       | 400           | 10s           | 10s           | 8ms   |

[Go back to navigation](#REST-API)

### Get Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 5ms   |

[Go back to navigation](#REST-API)

### Get Customer Statuses Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Statuses retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ------------------------------------ | ----- |
| 10         | 5        | 10            | 10s           | 10s           | 50                                   | 28ms  |

[Go back to navigation](#REST-API)

### Create Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 30       | 200           | 10s           | 10s           | 40ms  |

[Go back to navigation](#REST-API)

### Update Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 28       | 200           | 10s           | 10s           | 45ms  |

[Go back to navigation](#REST-API)

### Delete Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 50       | 400           | 10s           | 10s           | 8ms   |

[Go back to navigation](#REST-API)

### GraphQL Get Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 25ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customers Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Customers retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ------------------------------------- | ----- |
| 10         | 8        | 10            | 10s           | 10s           | 50                                    | 66ms  |

[Go back to navigation](#GraphQL)

### GraphQL Create Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 24       | 200           | 10s           | 10s           | 55ms  |

[Go back to navigation](#GraphQL)

### GraphQL Update Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 60ms  |

[Go back to navigation](#GraphQL)

### GraphQL Delete Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 47       | 400           | 10s           | 10s           | 11ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 20ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Types Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Types retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | --------------------------------- | ----- |
| 10         | 5        | 10            | 10s           | 10s           | 50                                | 52ms  |

[Go back to navigation](#GraphQL)

### GraphQL Create Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 30       | 200           | 10s           | 10s           | 45ms  |

[Go back to navigation](#GraphQL)

### GraphQL Update Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 28       | 200           | 10s           | 10s           | 50ms  |

[Go back to navigation](#GraphQL)

### GraphQL Delete Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 50       | 400           | 10s           | 10s           | 9ms   |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 20ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Statuses Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Statuses retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ------------------------------------ | ----- |
| 10         | 5        | 10            | 10s           | 10s           | 50                                   | 52ms  |

[Go back to navigation](#GraphQL)

### GraphQL Create Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 30       | 200           | 10s           | 10s           | 45ms  |

[Go back to navigation](#GraphQL)

### GraphQL Update Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 28       | 200           | 10s           | 10s           | 50ms  |

[Go back to navigation](#GraphQL)

### GraphQL Delete Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 50       | 400           | 10s           | 10s           | 9ms   |

[Go back to navigation](#GraphQL)

Learn more about [Testing Documentation](testing.md).
