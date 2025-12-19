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

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.1      | 2            | -    | 10s     | -    | 127ms |
| average   | 12         | 9.9      | 10           | 2s   | 8s      | 2s   | 54ms  |
| stress    | 40         | 32.4     | 30           | 3s   | 10s     | 3s   | 61ms  |
| spike     | 80         | 39.8     | 60           | 3s   | -       | 3s   | 442ms |

[Go back to navigation](#REST-API)

### Replace Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.1      | 2            | -    | 10s     | -    | 86ms  |
| average   | 12         | 9.9      | 10           | 2s   | 8s      | 2s   | 77ms  |
| stress    | 40         | 32.4     | 30           | 3s   | 10s     | 3s   | 92ms  |
| spike     | 80         | 39.8     | 60           | 3s   | -       | 3s   | 123ms |

[Go back to navigation](#REST-API)

### Delete Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2            | -    | 10s     | -    | 112ms |
| average   | 8          | 6.6      | 6            | 2s   | 8s      | 2s   | 49ms  |
| stress    | 25         | 20.3     | 20           | 3s   | 10s     | 3s   | 65ms  |
| spike     | 50         | 24.8     | 40           | 3s   | -       | 3s   | 38ms  |

[Go back to navigation](#REST-API)

### Get Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.0      | 3            | -    | 10s     | -    | 43ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 34ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 76ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 81ms  |

[Go back to navigation](#REST-API)

### Get Customer Type Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Types retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | --------------------------------- | ----- |
| smoke     | 5          | 5.1      | 3            | -    | 10s     | -    | 10                                | 88ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 10                                | 42ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 10                                | 95ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 10                                | 38ms  |

[Go back to navigation](#REST-API)

### Create Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.0      | 2            | -    | 10s     | -    | 38ms  |
| average   | 8          | 6.4      | 8            | 2s   | 6s      | 2s   | 37ms  |
| stress    | 25         | 20.8     | 25           | 2s   | 8s      | 2s   | 101ms |
| spike     | 50         | 25.0     | 50           | 2s   | -       | 2s   | 37ms  |

[Go back to navigation](#REST-API)

### Update Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.0      | 3            | -    | 10s     | -    | 64ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 36ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 83ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 38ms  |

[Go back to navigation](#REST-API)

### Delete Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3            | -    | 10s     | -    | 40ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 27ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 40ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 62ms  |

[Go back to navigation](#REST-API)

### Get Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3            | -    | 10s     | -    | 53ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 27ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 56ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 26ms  |

[Go back to navigation](#REST-API)

### Get Customer Status Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Statuses retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ------------------------------------ | ----- |
| smoke     | 5          | 5.1      | 3            | -    | 10s     | -    | 10                                   | 43ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 10                                   | 42ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 10                                   | 114ms |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 10                                   | 77ms  |

[Go back to navigation](#REST-API)

### Create Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2            | -    | 10s     | -    | 42ms  |
| average   | 8          | 6.4      | 8            | 2s   | 6s      | 2s   | 42ms  |
| stress    | 25         | 20.8     | 25           | 2s   | 8s      | 2s   | 42ms  |
| spike     | 50         | 24.8     | 50           | 2s   | -       | 2s   | 34ms  |

[Go back to navigation](#REST-API)

### Update Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3            | -    | 10s     | -    | 39ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 50ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 107ms |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 233ms |

[Go back to navigation](#REST-API)

### Delete Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3            | -    | 10s     | -    | 44ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 32ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 49ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 34ms  |

[Go back to navigation](#REST-API)

### GraphQL Get Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 10         | 10.1     | 5            | -    | 10s     | -    | 92ms  |
| average   | 25         | 20.8     | 20           | 2s   | 8s      | 2s   | 47ms  |
| stress    | 75         | 60.9     | 60           | 3s   | 10s     | 3s   | 349ms |
| spike     | 150        | 74.8     | 120          | 3s   | -       | 3s   | 589ms |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Customers retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ------------------------------------- | ----- |
| smoke     | 8          | 8.1      | 4            | -    | 10s     | -    | 10                                    | 95ms  |
| average   | 20         | 16.6     | 15           | 2s   | 8s      | 2s   | 10                                    | 198ms |
| stress    | 60         | 45.6     | 45           | 3s   | 10s     | 3s   | 10                                    | 908ms |
| spike     | 120        | 46.5     | 90           | 3s   | -       | 3s   | 10                                    | 2.07s |

[Go back to navigation](#GraphQL)

### GraphQL Create Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.0      | 3            | -    | 10s     | -    | 101ms |
| average   | 15         | 12.4     | 15           | 2s   | 8s      | 2s   | 60ms  |
| stress    | 50         | 40.6     | 50           | 3s   | 10s     | 3s   | 82ms  |
| spike     | 100        | 49.8     | 100          | 3s   | -       | 3s   | 223ms |
[Go back to navigation](#GraphQL)

### GraphQL Update Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.0      | 2            | -    | 10s     | -    | 51ms  |
| average   | 12         | 10.0     | 10           | 2s   | 8s      | 2s   | 48ms  |
| stress    | 40         | 32.5     | 30           | 3s   | 10s     | 3s   | 48ms  |
| spike     | 80         | 39.8     | 60           | 3s   | -       | 3s   | 97ms  |

[Go back to navigation](#GraphQL)

### GraphQL Delete Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2            | -    | 10s     | -    | 67ms  |
| average   | 8          | 6.7      | 6            | 2s   | 8s      | 2s   | 41ms  |
| stress    | 25         | 20.3     | 20           | 3s   | 10s     | 3s   | 73ms  |
| spike     | 50         | 25.0     | 40           | 3s   | -       | 3s   | 72ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.0      | 3            | -    | 10s     | -    | 51ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 38ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 34ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 39ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Type Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Types retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | --------------------------------- | ----- |
| smoke     | 5          | 5.1      | 3            | -    | 10s     | -    | 10                                | 70ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 10                                | 63ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 10                                | 89ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 10                                | 109ms |

[Go back to navigation](#GraphQL)

### GraphQL Create Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2            | -    | 10s     | -    | 51ms  |
| average   | 8          | 6.4      | 8            | 2s   | 6s      | 2s   | 48ms  |
| stress    | 25         | 20.8     | 25           | 2s   | 8s      | 2s   | 39ms  |
| spike     | 50         | 24.8     | 50           | 2s   | -       | 2s   | 49ms  |

[Go back to navigation](#GraphQL)

### GraphQL Update Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.1      | 2            | -    | 10s     | -    | 42ms  |
| average   | 12         | 10.0     | 10           | 2s   | 8s      | 2s   | 47ms  |
| stress    | 40         | 32.4     | 30           | 3s   | 10s     | 3s   | 63ms  |
| spike     | 80         | 39.8     | 60           | 3s   | -       | 3s   | 151ms |

[Go back to navigation](#GraphQL)

### GraphQL Delete Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2            | -    | 10s     | -    | 40ms  |
| average   | 8          | 6.6      | 6            | 2s   | 8s      | 2s   | 43ms  |
| stress    | 25         | 20.3     | 20           | 3s   | 10s     | 3s   | 39ms  |
| spike     | 50         | 24.8     | 40           | 3s   | -       | 3s   | 44ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3            | -    | 10s     | -    | 45ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 41ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 74ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 37ms  |

[Go back to navigation](#GraphQL)

### GraphQL Get Customer Status Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Statuses retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ------------------------------------ | ----- |
| smoke     | 5          | 5.0      | 3            | -    | 10s     | -    | 10                                   | 61ms  |
| average   | 15         | 11.9     | 12           | 2s   | 6s      | 2s   | 10                                   | 56ms  |
| stress    | 40         | 33.3     | 35           | 2s   | 8s      | 2s   | 10                                   | 99ms  |
| spike     | 80         | 39.8     | 70           | 2s   | -       | 2s   | 10                                   | 122ms |

[Go back to navigation](#GraphQL)

### GraphQL Create Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.0      | 2            | -    | 10s     | -    | 42ms  |
| average   | 8          | 6.3      | 8            | 2s   | 6s      | 2s   | 42ms  |
| stress    | 25         | 20.8     | 25           | 2s   | 8s      | 2s   | 52ms  |
| spike     | 50         | 24.8     | 50           | 2s   | -       | 2s   | 55ms  |

[Go back to navigation](#GraphQL)

### GraphQL Update Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.1      | 2            | -    | 10s     | -    | 52ms  |
| average   | 12         | 10.0     | 10           | 2s   | 8s      | 2s   | 41ms  |
| stress    | 40         | 32.4     | 30           | 3s   | 10s     | 3s   | 54ms  |
| spike     | 80         | 39.8     | 60           | 3s   | -       | 3s   | 45ms  |

[Go back to navigation](#GraphQL)

### GraphQL Delete Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------ | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2            | -    | 10s     | -    | 43ms  |
| average   | 8          | 6.7      | 6            | 2s   | 8s      | 2s   | 45ms  |
| stress    | 25         | 20.3     | 20           | 3s   | 10s     | 3s   | 54ms  |
| spike     | 50         | 24.8     | 40           | 3s   | -       | 3s   | 45ms  |

[Go back to navigation](#GraphQL)

Learn more about [Testing Documentation](testing.md).
