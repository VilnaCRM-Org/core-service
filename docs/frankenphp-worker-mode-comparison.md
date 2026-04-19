# FrankenPHP Worker Mode Runtime Comparison

Local fixed-VU k6 benchmark. Every endpoint was warmed first, then measured with the same settings in every runtime mode: 10 VUs for 30s after a 5s warmup.

## Scope

This report compares php-fpm, FrankenPHP without worker mode, and FrankenPHP with worker mode for the full committed endpoint inventory used by the benchmark harness.

## Headline Results

FrankenPHP worker mode had the highest throughput on 32/33 benchmarked endpoints and the lowest average latency on 32/33.

All three runtime modes maintained a >=99% check rate on 30/33 endpoints. FrankenPHP worker mode dropped below that threshold on 3/33 endpoints.

The biggest worker-mode throughput wins over php-fpm were:

- GET /api/customer_types/{id}: 5.4x more RPS, average latency down 81.9%.
- POST /api/customer_types: 5.3x more RPS, average latency down 81.7%.
- PATCH /api/customer_types/{id}: 4.8x more RPS, average latency down 79.5%.
- GET /api/customers/{id}: 4.8x more RPS, average latency down 79.3%.
- DELETE /api/customer_statuses/{id}: 4.7x more RPS, average latency down 79.6%.

The biggest worker-mode throughput wins over FrankenPHP without worker mode were:

- GET /api/customers/{id}: 16.8x more RPS, average latency down 94.2%.
- /api/docs: 7.9x more RPS, average latency down 87.5%.
- GET /api/customers: 6.6x more RPS, average latency down 84.9%.
- /api/health: 4.7x more RPS, average latency down 78.7%.
- PUT /api/customers/{id}: 4.3x more RPS, average latency down 78.3%.

## REST API

Cell format: `RPS / avg ms / p95 ms / p99 ms`.

| Endpoint                           |                          php-fpm |               FrankenPHP no worker |                FrankenPHP worker | Notes                                        |
| ---------------------------------- | -------------------------------: | ---------------------------------: | -------------------------------: | -------------------------------------------- |
| /api/docs                          |   196.77 / 50.56 / 76.48 / 98.45 |   64.50 / 154.71 / 412.21 / 648.21 |   511.97 / 19.28 / 44.93 / 71.23 |                                              |
| /api/health                        |   200.07 / 49.77 / 64.62 / 81.47 |   51.67 / 193.48 / 546.03 / 826.33 | 241.53 / 41.26 / 137.50 / 316.39 |                                              |
| GET /api/customers/{id}            | 109.47 / 91.08 / 117.91 / 135.52 |  30.97 / 324.30 / 774.47 / 1093.88 |   520.20 / 18.89 / 34.41 / 51.03 |                                              |
| GET /api/customers                 | 61.70 / 162.02 / 203.99 / 239.21 | 19.23 / 522.28 / 1109.56 / 1391.45 | 126.33 / 78.91 / 133.88 / 183.69 |                                              |
| POST /api/customers                | 90.93 / 109.60 / 154.23 / 206.49 |   37.77 / 265.29 / 502.93 / 640.45 | 119.23 / 83.43 / 204.80 / 291.68 |                                              |
| PATCH /api/customers/{id}          | 81.27 / 122.65 / 171.03 / 228.57 |   35.80 / 280.05 / 514.18 / 642.73 | 89.47 / 111.27 / 270.40 / 384.20 |                                              |
| PUT /api/customers/{id}            | 83.00 / 120.05 / 165.24 / 215.48 |   56.10 / 177.55 / 345.82 / 472.55 | 241.90 / 38.60 / 168.51 / 290.74 | FrankenPHP with worker mode check rate 71.2% |
| DELETE /api/customers/{id}         | 125.57 / 78.36 / 101.20 / 126.18 |    173.83 / 56.17 / 88.39 / 114.79 |   519.47 / 18.30 / 36.69 / 55.63 | FrankenPHP with worker mode check rate 96.9% |
| GET /api/customer_types/{id}       |   174.27 / 57.13 / 71.69 / 83.82 |    221.27 / 44.94 / 76.49 / 103.19 |   945.47 / 10.35 / 19.45 / 28.18 |                                              |
| GET /api/customer_types            | 98.77 / 101.12 / 128.81 / 152.70 |   116.17 / 85.86 / 146.03 / 187.84 |  198.23 / 50.20 / 93.39 / 125.55 |                                              |
| POST /api/customer_types           |  135.70 / 73.36 / 93.47 / 113.92 |    183.53 / 54.15 / 88.00 / 118.02 |   724.47 / 13.44 / 25.30 / 36.73 |                                              |
| PATCH /api/customer_types/{id}     | 123.47 / 80.66 / 106.14 / 129.21 |    169.83 / 58.52 / 93.90 / 123.02 |   591.27 / 16.51 / 31.58 / 49.17 |                                              |
| DELETE /api/customer_types/{id}    |   165.47 / 59.90 / 79.04 / 99.89 |    210.67 / 46.81 / 82.60 / 110.65 |   696.80 / 13.76 / 30.59 / 57.38 | FrankenPHP with worker mode check rate 95.6% |
| GET /api/customer_statuses/{id}    |   162.67 / 61.20 / 79.47 / 96.03 |    202.00 / 49.20 / 87.69 / 116.38 |   736.70 / 13.31 / 27.35 / 41.25 |                                              |
| GET /api/customer_statuses         | 96.03 / 103.97 / 138.02 / 166.64 |   108.60 / 91.84 / 162.93 / 211.89 | 166.73 / 59.72 / 118.25 / 172.39 |                                              |
| POST /api/customer_statuses        |  135.73 / 73.33 / 94.30 / 113.49 |   168.63 / 58.94 / 100.31 / 127.09 |   569.67 / 17.17 / 34.05 / 51.67 |                                              |
| PATCH /api/customer_statuses/{id}  |  126.77 / 78.55 / 99.41 / 114.71 |   158.47 / 62.73 / 103.65 / 137.63 |   502.10 / 19.51 / 38.57 / 57.15 |                                              |
| DELETE /api/customer_statuses/{id} |  165.80 / 59.76 / 82.66 / 104.40 |    217.07 / 45.54 / 76.33 / 100.32 |   787.50 / 12.17 / 25.44 / 40.80 |                                              |

## GraphQL

Cell format: `RPS / avg ms / p95 ms / p99 ms`.

| Endpoint                     |                          php-fpm |             FrankenPHP no worker |                  FrankenPHP worker | Notes                             |
| ---------------------------- | -------------------------------: | -------------------------------: | ---------------------------------: | --------------------------------- |
| GraphQL getCustomer          | 95.90 / 103.96 / 129.10 / 149.79 | 113.70 / 87.58 / 140.30 / 179.77 |   201.03 / 49.19 / 104.05 / 135.85 |                                   |
| GraphQL getCustomers         | 32.53 / 307.85 / 398.35 / 454.97 | 33.33 / 300.47 / 470.67 / 603.62 |   34.87 / 286.79 / 505.79 / 678.09 |                                   |
| GraphQL createCustomer       | 52.53 / 190.32 / 258.42 / 304.11 | 56.33 / 177.29 / 309.33 / 443.35 |   65.80 / 151.45 / 328.93 / 461.16 |                                   |
| GraphQL updateCustomer       | 50.20 / 199.26 / 285.16 / 345.13 | 51.23 / 194.66 / 361.19 / 477.17 |   69.80 / 142.84 / 310.55 / 414.26 |                                   |
| GraphQL deleteCustomer       | 89.17 / 110.09 / 136.36 / 161.14 | 108.37 / 90.85 / 147.32 / 186.39 |   182.43 / 53.44 / 100.92 / 136.39 |                                   |
| GraphQL getCustomerType      | 98.33 / 101.36 / 126.77 / 145.74 | 111.93 / 89.01 / 153.73 / 194.92 |    245.60 / 40.32 / 76.67 / 109.62 |                                   |
| GraphQL getCustomerTypes     | 39.37 / 254.69 / 320.22 / 362.89 | 37.50 / 267.30 / 422.37 / 497.56 | 20.70 / 487.48 / 1062.95 / 1386.55 | Worker slower than both baselines |
| GraphQL createCustomerType   | 98.23 / 101.51 / 127.81 / 149.13 | 110.43 / 90.15 / 152.98 / 200.68 |   196.53 / 50.40 / 114.33 / 183.00 |                                   |
| GraphQL updateCustomerType   | 93.57 / 106.67 / 138.37 / 163.14 | 104.83 / 94.99 / 156.58 / 210.63 |     223.73 / 44.32 / 77.80 / 99.17 |                                   |
| GraphQL deleteCustomerType   | 91.93 / 108.22 / 148.73 / 175.64 | 115.07 / 86.20 / 138.52 / 190.03 |     269.17 / 36.57 / 66.64 / 92.90 |                                   |
| GraphQL getCustomerStatus    | 90.63 / 110.00 / 151.53 / 205.69 | 112.37 / 88.63 / 152.50 / 204.70 |     349.07 / 28.31 / 46.47 / 62.64 |                                   |
| GraphQL getCustomerStatuses  | 36.90 / 271.63 / 363.27 / 515.47 | 37.83 / 264.70 / 429.48 / 544.55 |   47.87 / 208.82 / 312.52 / 367.30 |                                   |
| GraphQL createCustomerStatus | 93.87 / 106.20 / 140.68 / 178.93 | 112.27 / 88.71 / 150.53 / 198.17 |     295.40 / 33.43 / 56.86 / 77.34 |                                   |
| GraphQL updateCustomerStatus | 86.23 / 115.64 / 156.05 / 187.62 | 102.90 / 96.78 / 162.38 / 220.15 |     261.97 / 37.78 / 60.04 / 80.13 |                                   |
| GraphQL deleteCustomerStatus | 78.60 / 126.32 / 169.43 / 200.20 | 109.03 / 90.92 / 156.91 / 198.55 |     294.03 / 33.49 / 54.99 / 77.60 |                                   |

## Notes

- The benchmark runner uses benchmark-tagged k6 submetrics so setup and teardown requests do not distort the reported request rate or latency for the measured endpoint body.
- Destructive delete scenarios size their fixtures from a warmup pass before the measured run so the 30-second benchmark does not run out of entities partway through.
- Numbers are local-machine results and will move with CPU limits, Docker scheduling, and background host load.
