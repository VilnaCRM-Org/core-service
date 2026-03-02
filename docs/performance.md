# Performance and Optimization

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

- [Health](#health-test)
- [Get Customer](#get-customer-test)
- [Get Customer Collection](#get-customer-collection-test)
- [Create Customer](#create-customer-test)
- [Update Customer](#update-customer-test)
- [Replace Customer](#replace-customer-test)
- [Delete Customer](#delete-customer-test)
- [Get Customer Type](#get-customer-type-test)
- [Get Customer Type Collection](#get-customer-type-collection-test)
- [Create Customer Type](#create-customer-type-test)
- [Update Customer Type](#update-customer-type-test)
- [Delete Customer Type](#delete-customer-type-test)
- [Get Customer Status](#get-customer-status-test)
- [Get Customer Status Collection](#get-customer-status-collection-test)
- [Create Customer Status](#create-customer-status-test)
- [Update Customer Status](#update-customer-status-test)
- [Delete Customer Status](#delete-customer-status-test)

### GraphQL

- [Get Customer](#graphql-get-customer-test)
- [Get Customer Collection](#graphql-get-customer-collection-test)
- [Create Customer](#graphql-create-customer-test)
- [Update Customer](#graphql-update-customer-test)
- [Delete Customer](#graphql-delete-customer-test)
- [Get Customer Type](#graphql-get-customer-type-test)
- [Get Customer Type Collection](#graphql-get-customer-type-collection-test)
- [Create Customer Type](#graphql-create-customer-type-test)
- [Update Customer Type](#graphql-update-customer-type-test)
- [Delete Customer Type](#graphql-delete-customer-type-test)
- [Get Customer Status](#graphql-get-customer-status-test)
- [Get Customer Status Collection](#graphql-get-customer-status-collection-test)
- [Create Customer Status](#graphql-create-customer-status-test)
- [Update Customer Status](#graphql-update-customer-status-test)
- [Delete Customer Status](#graphql-delete-customer-status-test)

### Health Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 10         | 10.0     | 5             | -    | 10s     | -    | 32ms  |
| average   | 25         | 20.3     | 25            | 3s   | 10s     | 3s   | 22ms  |
| stress    | 150        | 121.8    | 150           | 3s   | 10s     | 3s   | 36ms  |
| spike     | 200        | 99.9     | 200           | 5s   | -       | 5s   | 366ms |

<img width="1774" height="393" alt="image" src="https://github.com/user-attachments/assets/adf8f76e-2519-41c5-947d-250769472256" />
<img width="1767" height="682" alt="image" src="https://github.com/user-attachments/assets/6be6fbb1-484a-439a-b2c6-45bb4437ba5f" />

> Real RPS is calculated as `request_count / scenario_stage_duration` based on `all-health.summary.json` (to avoid diluting results by the configured delay between scenarios).

[Go back to navigation](#rest-api)

### Get Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 10         | 10.0     | 5             | -    | 10s     | -    | 78ms  |
| average   | 25         | 20.8     | 20            | 2s   | 8s      | 2s   | 67ms  |
| stress    | 75         | 60.9     | 60            | 3s   | 10s     | 3s   | 80ms  |
| spike     | 150        | 74.8     | 120           | 3s   | -       | 3s   | 138ms |

<img width="1777" height="342" alt="image" src="https://github.com/user-attachments/assets/0e20cda5-bde2-4e0b-a724-c628c9294568" />
<img width="1760" height="678" alt="image" src="https://github.com/user-attachments/assets/d781d870-28dc-427f-8b5d-364abb71535f" />

[Go back to navigation](#rest-api)

### Get Customer Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Customers retrieved with each request | P(99)  |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ------------------------------------- | ------ |
| smoke     | 8          | 8.1      | 4             | -    | 10s     | -    | 10                                    | 134ms  |
| average   | 20         | 16.6     | 15            | 2s   | 8s      | 2s   | 10                                    | 163ms  |
| stress    | 60         | 48.2     | 45            | 3s   | 10s     | 3s   | 10                                    | 838ms  |
| spike     | 120        | 59.8     | 90            | 3s   | -       | 3s   | 10                                    | 1357ms |

<img width="1772" height="389" alt="image" src="https://github.com/user-attachments/assets/c513dc83-f070-41ec-9e75-8a32081656ff" />
<img width="1772" height="722" alt="image" src="https://github.com/user-attachments/assets/ae4df3bc-3f33-4cab-8fdc-5c7de788f665" />

[Go back to navigation](#rest-api)

### Create Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3             | -    | 10s     | -    | 66ms  |
| average   | 15         | 12.4     | 15            | 2s   | 8s      | 2s   | 52ms  |
| stress    | 50         | 40.6     | 50            | 3s   | 10s     | 3s   | 79ms  |
| spike     | 100        | 49.8     | 100           | 3s   | -       | 3s   | 172ms |

<img width="1772" height="388" alt="image" src="https://github.com/user-attachments/assets/95ec14e4-1f27-4b18-b9b6-5203a49f3197" />
<img width="1772" height="694" alt="image" src="https://github.com/user-attachments/assets/6bd38f3d-ad21-4f0e-9253-37292a957727" />

[Go back to navigation](#rest-api)

### Update Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.1      | 2             | -    | 10s     | -    | 127ms |
| average   | 12         | 9.9      | 10            | 2s   | 8s      | 2s   | 54ms  |
| stress    | 40         | 32.4     | 30            | 3s   | 10s     | 3s   | 61ms  |
| spike     | 80         | 39.8     | 60            | 3s   | -       | 3s   | 442ms |

<img width="1772" height="364" alt="image" src="https://github.com/user-attachments/assets/05b5ddbf-4929-4f71-86dc-b6329df2c3e2" />
<img width="1772" height="678" alt="image" src="https://github.com/user-attachments/assets/334cf54a-23b5-4e88-8094-0bc513df5069" />

[Go back to navigation](#rest-api)

### Replace Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.1      | 2             | -    | 10s     | -    | 86ms  |
| average   | 12         | 9.9      | 10            | 2s   | 8s      | 2s   | 77ms  |
| stress    | 40         | 32.4     | 30            | 3s   | 10s     | 3s   | 92ms  |
| spike     | 80         | 39.8     | 60            | 3s   | -       | 3s   | 123ms |

<img width="1772" height="369" alt="image" src="https://github.com/user-attachments/assets/39d91cba-ebf8-4165-aeab-50ebafaf30cf" />
<img width="1772" height="690" alt="image" src="https://github.com/user-attachments/assets/3d633f4c-695b-4fcc-a77e-b7a18d65bea9" />

[Go back to navigation](#rest-api)

### Delete Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2             | -    | 10s     | -    | 112ms |
| average   | 8          | 6.6      | 6             | 2s   | 8s      | 2s   | 49ms  |
| stress    | 25         | 20.3     | 20            | 3s   | 10s     | 3s   | 65ms  |
| spike     | 50         | 24.8     | 40            | 3s   | -       | 3s   | 38ms  |

<img width="1772" height="366" alt="image" src="https://github.com/user-attachments/assets/dbd27ba0-48de-4184-a965-9b64b46d8895" />
<img width="1772" height="688" alt="image" src="https://github.com/user-attachments/assets/9ad689a4-7065-4c2b-81c5-da4c7503c3d8" />

[Go back to navigation](#rest-api)

### Get Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.0      | 3             | -    | 10s     | -    | 43ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 34ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 76ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 81ms  |

<img width="1772" height="362" alt="image" src="https://github.com/user-attachments/assets/bb051a87-53c7-42d9-98d9-4ccb07f6b65b" />
<img width="1772" height="672" alt="image" src="https://github.com/user-attachments/assets/953dc708-576d-4165-94d2-b243bafca377" />

[Go back to navigation](#rest-api)

### Get Customer Type Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Types retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | --------------------------------- | ----- |
| smoke     | 5          | 5.1      | 3             | -    | 10s     | -    | 10                                | 88ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 10                                | 42ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 10                                | 95ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 10                                | 38ms  |

<img width="1772" height="357" alt="image" src="https://github.com/user-attachments/assets/70ce62a2-e73d-47ff-afdb-f273da5e19f9" />
<img width="1772" height="694" alt="image" src="https://github.com/user-attachments/assets/6d535cca-4ba2-4169-b0c1-5a459457aad1" />

[Go back to navigation](#rest-api)

### Create Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.0      | 2             | -    | 10s     | -    | 38ms  |
| average   | 8          | 6.4      | 8             | 2s   | 6s      | 2s   | 37ms  |
| stress    | 25         | 20.8     | 25            | 2s   | 8s      | 2s   | 101ms |
| spike     | 50         | 25.0     | 50            | 2s   | -       | 2s   | 37ms  |

<img width="1772" height="369" alt="image" src="https://github.com/user-attachments/assets/a6dd9d9a-8b4d-4dc8-9284-58df774e823e" />
<img width="1772" height="695" alt="image" src="https://github.com/user-attachments/assets/ea41fe78-78e6-498b-8702-89b0c9efcf84" />

[Go back to navigation](#rest-api)

### Update Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.0      | 3             | -    | 10s     | -    | 64ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 36ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 83ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 38ms  |

<img width="1772" height="369" alt="image" src="https://github.com/user-attachments/assets/58e7d21c-8ace-4962-95e9-402e07369666" />
<img width="1772" height="695" alt="image" src="https://github.com/user-attachments/assets/383845ad-5f16-448f-b305-aa97cadd9d8f" />

[Go back to navigation](#rest-api)

### Delete Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3             | -    | 10s     | -    | 40ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 27ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 40ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 62ms  |

<img width="1772" height="353" alt="image" src="https://github.com/user-attachments/assets/c0e431c8-2e54-4b55-81ff-2df8005995b5" />
<img width="1772" height="693" alt="image" src="https://github.com/user-attachments/assets/2add34c1-ed75-4ac6-ac94-ad08bbc05bc5" />

[Go back to navigation](#rest-api)

### Get Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3             | -    | 10s     | -    | 53ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 27ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 56ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 26ms  |

<img width="1772" height="367" alt="image" src="https://github.com/user-attachments/assets/abc08c88-fb16-43d5-9d2c-6ad7f00de565" />
<img width="1772" height="707" alt="image" src="https://github.com/user-attachments/assets/fc22cfa9-611c-43c5-922b-992167c62862" />

[Go back to navigation](#rest-api)

### Get Customer Status Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Statuses retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ------------------------------------ | ----- |
| smoke     | 5          | 5.1      | 3             | -    | 10s     | -    | 10                                   | 43ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 10                                   | 42ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 10                                   | 114ms |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 10                                   | 77ms  |

<img width="1772" height="382" alt="image" src="https://github.com/user-attachments/assets/39e3f9c0-15c9-4ccd-bd96-0a7c443d4f8b" />
<img width="1772" height="668" alt="image" src="https://github.com/user-attachments/assets/184a8fa0-8a6d-4470-a1e2-9f22f4cc9206" />

[Go back to navigation](#rest-api)

### Create Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2             | -    | 10s     | -    | 42ms  |
| average   | 8          | 6.4      | 8             | 2s   | 6s      | 2s   | 42ms  |
| stress    | 25         | 20.8     | 25            | 2s   | 8s      | 2s   | 42ms  |
| spike     | 50         | 24.8     | 50            | 2s   | -       | 2s   | 34ms  |

<img width="1772" height="365" alt="image" src="https://github.com/user-attachments/assets/67ec86d4-8749-40ef-b751-998229f87dd9" />
<img width="1772" height="683" alt="image" src="https://github.com/user-attachments/assets/d47347bb-b5f5-4b07-8a5a-8a59b27e09db" />

[Go back to navigation](#rest-api)

### Update Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3             | -    | 10s     | -    | 39ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 50ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 107ms |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 233ms |

<img width="1772" height="382" alt="image" src="https://github.com/user-attachments/assets/f67a523d-1765-4442-84dd-aef14d556f3e" />
<img width="1772" height="692" alt="image" src="https://github.com/user-attachments/assets/023fb29f-55c3-4b47-9be0-97a7118ceeb5" />

[Go back to navigation](#rest-api)

### Delete Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3             | -    | 10s     | -    | 44ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 32ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 49ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 34ms  |

<img width="1772" height="354" alt="image" src="https://github.com/user-attachments/assets/2bf43ebf-c81b-4362-b18e-74e00920cf4a" />
<img width="1772" height="703" alt="image" src="https://github.com/user-attachments/assets/05a14148-7cc7-429c-9d5e-4fcfd4d066d2" />

[Go back to navigation](#rest-api)

### GraphQL Get Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 10         | 10.1     | 5             | -    | 10s     | -    | 92ms  |
| average   | 25         | 20.8     | 20            | 2s   | 8s      | 2s   | 47ms  |
| stress    | 75         | 60.9     | 60            | 3s   | 10s     | 3s   | 349ms |
| spike     | 150        | 74.8     | 120           | 3s   | -       | 3s   | 589ms |

<img width="1772" height="356" alt="image" src="https://github.com/user-attachments/assets/943754f8-2631-42cc-bd6e-8b2df72b5a67" />
<img width="1772" height="710" alt="image" src="https://github.com/user-attachments/assets/2613268d-8ab8-4f3d-8604-d04e506ff778" />
[Go back to navigation](#graphql)

### GraphQL Get Customer Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Customers retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ------------------------------------- | ----- |
| smoke     | 8          | 8.1      | 4             | -    | 10s     | -    | 10                                    | 95ms  |
| average   | 20         | 16.6     | 15            | 2s   | 8s      | 2s   | 10                                    | 198ms |
| stress    | 60         | 45.6     | 45            | 3s   | 10s     | 3s   | 10                                    | 908ms |
| spike     | 120        | 46.5     | 90            | 3s   | -       | 3s   | 10                                    | 2.07s |

<img width="1772" height="372" alt="image" src="https://github.com/user-attachments/assets/afaaeade-59cd-409e-94a7-e873caafc56f" />
<img width="1772" height="716" alt="image" src="https://github.com/user-attachments/assets/cc43cc63-901c-40cb-940f-64094feb7038" />
[Go back to navigation](#graphql)

### GraphQL Create Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.0      | 3             | -    | 10s     | -    | 101ms |
| average   | 15         | 12.4     | 15            | 2s   | 8s      | 2s   | 60ms  |
| stress    | 50         | 40.6     | 50            | 3s   | 10s     | 3s   | 82ms  |
| spike     | 100        | 49.8     | 100           | 3s   | -       | 3s   | 223ms |

<img width="1772" height="357" alt="image" src="https://github.com/user-attachments/assets/11bf6abe-b62d-4425-b566-1134505f7276" />
<img width="1772" height="698" alt="image" src="https://github.com/user-attachments/assets/0cfd9e9c-c086-434b-8bf4-ab5fe3cb0075" />

[Go back to navigation](#graphql)

### GraphQL Update Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.0      | 2             | -    | 10s     | -    | 51ms  |
| average   | 12         | 10.0     | 10            | 2s   | 8s      | 2s   | 48ms  |
| stress    | 40         | 32.5     | 30            | 3s   | 10s     | 3s   | 48ms  |
| spike     | 80         | 39.8     | 60            | 3s   | -       | 3s   | 97ms  |

<img width="1772" height="374" alt="image" src="https://github.com/user-attachments/assets/6adf8655-2dbf-4f10-a069-a5ccafff4219" />
<img width="1772" height="697" alt="image" src="https://github.com/user-attachments/assets/abec98a4-29d0-4057-b15e-594288bac94f" />

[Go back to navigation](#graphql)

### GraphQL Delete Customer Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2             | -    | 10s     | -    | 67ms  |
| average   | 8          | 6.7      | 6             | 2s   | 8s      | 2s   | 41ms  |
| stress    | 25         | 20.3     | 20            | 3s   | 10s     | 3s   | 73ms  |
| spike     | 50         | 25.0     | 40            | 3s   | -       | 3s   | 72ms  |

<img width="1772" height="370" alt="image" src="https://github.com/user-attachments/assets/710f46f0-d41c-4014-957e-36ded88904ca" />
<img width="1772" height="693" alt="image" src="https://github.com/user-attachments/assets/fb0b2258-1f38-41df-8493-a86d5cb657d8" />

[Go back to navigation](#graphql)

### GraphQL Get Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.0      | 3             | -    | 10s     | -    | 51ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 38ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 34ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 39ms  |

<img width="1772" height="373" alt="image" src="https://github.com/user-attachments/assets/b88f735a-cca7-4e62-b2bd-619a5d5d6c71" />
<img width="1772" height="688" alt="image" src="https://github.com/user-attachments/assets/f318bf65-69f8-4b98-955a-2c8c45f5e633" />

[Go back to navigation](#graphql)

### GraphQL Get Customer Type Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Types retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | --------------------------------- | ----- |
| smoke     | 5          | 5.1      | 3             | -    | 10s     | -    | 10                                | 70ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 10                                | 63ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 10                                | 89ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 10                                | 109ms |

<img width="1772" height="380" alt="image" src="https://github.com/user-attachments/assets/2cacf9bd-683c-475b-a199-e63ed1da4505" />
<img width="1772" height="715" alt="image" src="https://github.com/user-attachments/assets/6436fdd0-5a5c-458f-8ce8-c4f06ad84740" />

[Go back to navigation](#graphql)

### GraphQL Create Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2             | -    | 10s     | -    | 51ms  |
| average   | 8          | 6.4      | 8             | 2s   | 6s      | 2s   | 48ms  |
| stress    | 25         | 20.8     | 25            | 2s   | 8s      | 2s   | 39ms  |
| spike     | 50         | 24.8     | 50            | 2s   | -       | 2s   | 49ms  |

<img width="1734" height="406" alt="image" src="https://github.com/user-attachments/assets/cfd04284-3a7d-4380-9037-68f508739700" />
<img width="1734" height="673" alt="image" src="https://github.com/user-attachments/assets/087c1125-8e5b-40aa-bdb5-a2b0d520979e" />

[Go back to navigation](#graphql)

### GraphQL Update Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.1      | 2             | -    | 10s     | -    | 42ms  |
| average   | 12         | 10.0     | 10            | 2s   | 8s      | 2s   | 47ms  |
| stress    | 40         | 32.4     | 30            | 3s   | 10s     | 3s   | 63ms  |
| spike     | 80         | 39.8     | 60            | 3s   | -       | 3s   | 151ms |

<img width="1734" height="377" alt="image" src="https://github.com/user-attachments/assets/e255e88d-d9a0-4635-9ed0-e06b7f493ae1" />
<img width="1776" height="705" alt="image" src="https://github.com/user-attachments/assets/5b5abccb-6efd-40cc-b7d3-2bc46823f0da" />

[Go back to navigation](#graphql)

### GraphQL Delete Customer Type Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2             | -    | 10s     | -    | 40ms  |
| average   | 8          | 6.6      | 6             | 2s   | 8s      | 2s   | 43ms  |
| stress    | 25         | 20.3     | 20            | 3s   | 10s     | 3s   | 39ms  |
| spike     | 50         | 24.8     | 40            | 3s   | -       | 3s   | 44ms  |

<img width="1776" height="365" alt="image" src="https://github.com/user-attachments/assets/e138e580-d2da-4af3-8d36-e688b714e9b6" />
<img width="1777" height="677" alt="image" src="https://github.com/user-attachments/assets/af5d4f76-5932-488f-b3d6-f82a4e7ee366" />

[Go back to navigation](#graphql)

### GraphQL Get Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 5          | 5.1      | 3             | -    | 10s     | -    | 45ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 41ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 74ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 37ms  |

<img width="1760" height="371" alt="image" src="https://github.com/user-attachments/assets/3dca3f0c-f287-4028-a061-8ad4de2ec7fe" />
<img width="1760" height="694" alt="image" src="https://github.com/user-attachments/assets/c2f1acc5-3d38-45bd-a3c7-736b686e475f" />

[Go back to navigation](#graphql)

### GraphQL Get Customer Status Collection Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | Statuses retrieved with each request | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ------------------------------------ | ----- |
| smoke     | 5          | 5.0      | 3             | -    | 10s     | -    | 10                                   | 61ms  |
| average   | 15         | 11.9     | 12            | 2s   | 6s      | 2s   | 10                                   | 56ms  |
| stress    | 40         | 33.3     | 35            | 2s   | 8s      | 2s   | 10                                   | 99ms  |
| spike     | 80         | 39.8     | 70            | 2s   | -       | 2s   | 10                                   | 122ms |

<img width="1760" height="368" alt="image" src="https://github.com/user-attachments/assets/c3c52003-ebfc-459f-a63a-cdf0fc34b682" />
<img width="1760" height="692" alt="image" src="https://github.com/user-attachments/assets/50ed85f2-cb50-4406-962e-6413286fbc5a" />

[Go back to navigation](#graphql)

### GraphQL Create Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.0      | 2             | -    | 10s     | -    | 42ms  |
| average   | 8          | 6.3      | 8             | 2s   | 6s      | 2s   | 42ms  |
| stress    | 25         | 20.8     | 25            | 2s   | 8s      | 2s   | 52ms  |
| spike     | 50         | 24.8     | 50            | 2s   | -       | 2s   | 55ms  |

<img width="1760" height="376" alt="image" src="https://github.com/user-attachments/assets/9b18ab77-85b5-4f69-a52a-453779058e7a" />
<img width="1760" height="675" alt="image" src="https://github.com/user-attachments/assets/72888f33-db12-4408-ba0e-7f373b78ef07" />

[Go back to navigation](#graphql)

### GraphQL Update Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 4          | 4.1      | 2             | -    | 10s     | -    | 52ms  |
| average   | 12         | 10.0     | 10            | 2s   | 8s      | 2s   | 41ms  |
| stress    | 40         | 32.4     | 30            | 3s   | 10s     | 3s   | 54ms  |
| spike     | 80         | 39.8     | 60            | 3s   | -       | 3s   | 45ms  |

<img width="1760" height="371" alt="image" src="https://github.com/user-attachments/assets/cff06479-4e2b-4413-82a6-e338be5bf71f" />
<img width="1760" height="662" alt="image" src="https://github.com/user-attachments/assets/0c28f887-bad2-4af3-910f-f696a9e4c2c4" />

[Go back to navigation](#graphql)

### GraphQL Delete Customer Status Test

| Test type | Target RPS | Real RPS | Virtual Users | Rise | Plateau | Fall | P(99) |
| --------- | ---------- | -------- | ------------- | ---- | ------- | ---- | ----- |
| smoke     | 3          | 3.1      | 2             | -    | 10s     | -    | 43ms  |
| average   | 8          | 6.7      | 6             | 2s   | 8s      | 2s   | 45ms  |
| stress    | 25         | 20.3     | 20            | 3s   | 10s     | 3s   | 54ms  |
| spike     | 50         | 24.8     | 40            | 3s   | -       | 3s   | 45ms  |

<img width="1760" height="384" alt="image" src="https://github.com/user-attachments/assets/9ca77073-633e-45a7-ad59-cc64be2dc2c8" />
<img width="1760" height="685" alt="image" src="https://github.com/user-attachments/assets/bb9dc63a-101c-4c9d-9a0a-2674a7122bf7" />

[Go back to navigation](#graphql)

Learn more about [Testing Documentation](testing.md).
