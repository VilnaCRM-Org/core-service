Welcome to the **Performance and Optimization** GitHub page, which is dedicated to showcasing our comprehensive approach to enhancing the efficiency and speed of our Core Service application. Our goal is to share insights, methodologies, and results from rigorous testing and optimization processes to help developers achieve peak performance in their own applications.

## Testing Environment

We run performance tests locally using the repository Docker Compose setup (not AWS). The benchmark numbers below were collected on the following workstation; results will vary depending on CPU load, Docker resource limits, and host OS scheduling.

### Host Machine Specifications:

- **CPU:** AMD Ryzen 5 7535HS (6 cores / 12 threads, up to 4.6 GHz)
- **Memory:** 32 GB RAM (30 GiB usable)
- **Storage:** 1 TB NVMe (SAMSUNG MZAL81T0HDLB-00BLL)
- **Root filesystem:** ext4, 553 GB total
- **Operating System:** Ubuntu 24.04.2 LTS (kernel 6.14.0-36-generic)

### Container / Tooling:

- **Docker Engine:** 27.5.1
- **Docker Compose (plugin):** v2.32.4
- **Load Testing Tools:** Grafana k6 1.4.2 (via the repo K6 Docker image)

### Application Under Test:

- **Core Service Version:** 0.8.0
- **PHP Version:** 8.3
- **Symfony Version:** 7.2
- **Database:** MongoDB 6.0
- **Cache:** Redis (docker-compose service `redis`, image `redis:8.0.0-alpine`)

## Benchmarks

Here you will find the results of load tests for each Core Service endpoint, with a graph, that shows how execution parameters were changing over time for different load scenarios. Also, the metric for Spike testing will be provided, alongside a table, that will show the most important of them.

Each endpoint was tested for smoke, average, stress, and spike load scenarios. You can learn more about them [here](https://grafana.com/docs/k6/latest/testing-guides/test-types/).
Also, you can find HTML files with load test reports [here](https://github.com/VilnaCRM-Org/core-service/tree/main/tests/Load/results)

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
- [Get Customer Collection](#Get-Customer-Collection-Test)
- [Create Customer](#Create-Customer-Test)
- [Update Customer](#Update-Customer-Test)
- [Replace Customer](#Replace-Customer-Test)
- [Delete Customer](#Delete-Customer-Test)
- [Get Customer Type](#Get-Customer-Type-Test)
- [Get Customer Type Collection](#Get-Customer-Type-Collection-Test)
- [Create Customer Type](#Create-Customer-Type-Test)
- [Update Customer Type](#Update-Customer-Type-Test)
- [Delete Customer Type](#Delete-Customer-Type-Test)
- [Get Customer Status](#Get-Customer-Status-Test)
- [Get Customer Status Collection](#Get-Customer-Status-Collection-Test)
- [Create Customer Status](#Create-Customer-Status-Test)
- [Update Customer Status](#Update-Customer-Status-Test)
- [Delete Customer Status](#Delete-Customer-Status-Test)

### GraphQL

- [Get Customer](#GraphQL-Get-Customer-Test)
- [Get Customer Collection](#GraphQL-Get-Customer-Collection-Test)
- [Create Customer](#GraphQL-Create-Customer-Test)
- [Update Customer](#GraphQL-Update-Customer-Test)
- [Delete Customer](#GraphQL-Delete-Customer-Test)
- [Get Customer Type](#GraphQL-Get-Customer-Type-Test)
- [Get Customer Type Collection](#GraphQL-Get-Customer-Type-Collection-Test)
- [Create Customer Type](#GraphQL-Create-Customer-Type-Test)
- [Update Customer Type](#GraphQL-Update-Customer-Type-Test)
- [Delete Customer Type](#GraphQL-Delete-Customer-Type-Test)
- [Get Customer Status](#GraphQL-Get-Customer-Status-Test)
- [Get Customer Status Collection](#GraphQL-Get-Customer-Status-Collection-Test)
- [Create Customer Status](#GraphQL-Create-Customer-Status-Test)
- [Update Customer Status](#GraphQL-Update-Customer-Status-Test)
- [Delete Customer Status](#GraphQL-Delete-Customer-Status-Test)

### Health Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 10         | 10.0     | 5            | -    | 10s     | -    | 32ms  |
| average   | 25         | 20.3     | 25           | 3s   | 10s     | 3s   | 22ms  |
| stress    | 150        | 121.8    | 150          | 3s   | 10s     | 3s   | 36ms  |
| spike     | 200        | 99.9     | 200          | 5s   | -       | 5s   | 366ms |

> Real RPS is calculated as `request_count / scenario_stage_duration` based on `all-health.summary.json` (to avoid diluting results by the configured delay between scenarios).

[Go back to navigation](#REST-API)

### Get Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 10         | 10.0     | 5            | -    | 10s     | -    | 75ms  |
| average   | 25         | 20.8     | 20           | 2s   | 8s      | 2s   | 41ms  |
| stress    | 75         | 60.9     | 60           | 3s   | 10s     | 3s   | 248ms |
| spike     | 150        | 74.8     | 120          | 3s   | -       | 3s   | 1070ms |

[Go back to navigation](#REST-API)

### Get Customer Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Customers retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ------------------------------------- | ----- |
| smoke     | 8          | 8.1      | 4            | -    | 10s     | -    | 10                                    | 134ms |
| average   | 20         | 16.6     | 15           | 2s   | 8s      | 2s   | 10                                    | 163ms |
| stress    | 60         | 48.2     | 45           | 3s   | 10s     | 3s   | 10                                    | 838ms |
| spike     | 120        | 59.8     | 90           | 3s   | -       | 3s   | 10                                    | 1357ms |

[Go back to navigation](#REST-API)

### Create Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3            | -    | 10s     | -    | 66ms  |
| average   | 15         | 12.4     | 15           | 2s   | 8s      | 2s   | 52ms  |
| stress    | 50         | 40.6     | 50           | 3s   | 10s     | 3s   | 79ms  |
| spike     | 100        | 49.8     | 100          | 3s   | -       | 3s   | 172ms |

[Go back to navigation](#REST-API)

### Update Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 28ms  |

[Go back to navigation](#REST-API)

### Replace Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 30ms  |

[Go back to navigation](#REST-API)

### Delete Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 47       | 400           | 10s           | 10s           | 23ms  |

[Go back to navigation](#REST-API)

### Get Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 21ms  |

[Go back to navigation](#REST-API)

### Get Customer Type Collection Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Types retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | --------------------------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 10                                | 29ms  |

[Go back to navigation](#REST-API)

### Create Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 30       | 200           | 10s           | 10s           | 52ms  |

[Go back to navigation](#REST-API)

### Update Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 28       | 200           | 10s           | 10s           | 31ms  |

[Go back to navigation](#REST-API)

### Delete Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 50       | 400           | 10s           | 10s           | 20ms  |

[Go back to navigation](#REST-API)

### Get Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 21ms  |

[Go back to navigation](#REST-API)

### Get Customer Status Collection Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Statuses retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ------------------------------------ | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 10                                   | 24ms  |

[Go back to navigation](#REST-API)

### Create Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 30       | 200           | 10s           | 10s           | 30ms  |

[Go back to navigation](#REST-API)

### Update Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 28       | 200           | 10s           | 10s           | 32ms  |

[Go back to navigation](#REST-API)

### Delete Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 50       | 400           | 10s           | 10s           | 20ms  |

[Go back to navigation](#REST-API)

### GraphQL Get Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 27ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Collection Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Customers retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ------------------------------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 10                                    | 29ms  |

[Go back to navigation](#GraphQL)

### GraphQL Create Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 24       | 200           | 10s           | 10s           | 40ms  |
[Go back to navigation](#GraphQL)

### GraphQL Update Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 39ms  |

[Go back to navigation](#GraphQL)

### GraphQL Delete Customer Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 47       | 400           | 10s           | 10s           | 26ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 28ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Type Collection Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Types retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | --------------------------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 10                                | 31ms  |

[Go back to navigation](#GraphQL)

### GraphQL Create Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 30       | 200           | 10s           | 10s           | 35ms  |

[Go back to navigation](#GraphQL)

### GraphQL Update Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 28       | 200           | 10s           | 10s           | 35ms  |

[Go back to navigation](#GraphQL)

### GraphQL Delete Customer Type Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 50       | 400           | 10s           | 10s           | 20ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 28ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Status Collection Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Statuses retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ------------------------------------ | ----- |
| 400        | 65       | 400           | 10s           | 10s           | 10                                   | 30ms  |

[Go back to navigation](#GraphQL)

### GraphQL Create Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 30       | 200           | 10s           | 10s           | 32ms  |

[Go back to navigation](#GraphQL)

### GraphQL Update Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 28       | 200           | 10s           | 10s           | 39ms  |

[Go back to navigation](#GraphQL)

### GraphQL Delete Customer Status Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 50       | 400           | 10s           | 10s           | 20ms  |

[Go back to navigation](#GraphQL)

Learn more about [Testing Documentation](testing.md).
