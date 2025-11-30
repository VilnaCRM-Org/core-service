# Load Testing Documentation

This directory contains comprehensive load tests for the Core Service REST API using K6.

## Overview

The load tests cover all major REST API endpoints:

- **Health Check**: `/health`
- **Customers API**: `/api/customers` (GET, POST, PATCH, PUT, DELETE)
- **Customer Types API**: `/api/customer_types` (GET, POST, PATCH, PUT, DELETE)
- **Customer Statuses API**: `/api/customer_statuses` (GET, POST, PATCH, PUT, DELETE)

## Test Scripts

### Individual Endpoint Tests

- `health.js` - Health check endpoint testing
- `customers.js` - Customer CRUD operations with realistic data
- `customer-types.js` - Customer type management operations
- `customer-statuses.js` - Customer status management operations

### Comprehensive Tests

- `api-comprehensive.js` - Mixed scenario testing all endpoints with realistic traffic distribution

## Running Load Tests

### Quick Start

```bash
# Run all API load tests with all load levels
make load-tests

# Run specific load test levels
make smoke-load-tests    # Minimal load
make average-load-tests  # Average load
make stress-load-tests   # High load
make spike-load-tests    # Spike load

# Run specific scenario with all load levels
make execute-load-tests-script scenario=customers

# Run specific scenario with specific load type
./tests/Load/execute-load-test.sh customers true false false false smoke-
./tests/Load/execute-load-test.sh api-comprehensive false true false false average-
```

### Available Test Types

Load test parameters are configured in `config.json.dist`:

- **Smoke**: Basic functionality verification with minimal load
- **Average**: Normal traffic simulation with moderate load
- **Stress**: High load testing to find breaking points
- **Spike**: Extreme load spikes to test resilience

## Test Features

### Realistic Data Generation

- Random customer data with valid emails, phones, and lead sources
- Proper relationships between customers, types, and statuses
- Weighted operation distribution (more reads than writes)

### Comprehensive Coverage

- **Create operations**: POST with valid payloads
- **Read operations**: GET with various filters and pagination
- **Update operations**: PATCH for partial updates
- **Replace operations**: PUT for full replacement
- **Delete operations**: DELETE with proper cleanup

### Smart Resource Management

- Setup/teardown for test data
- Memory-efficient resource tracking
- Automatic cleanup of created resources

## Traffic Distribution

The comprehensive test simulates realistic API usage:

- 10% Health checks (monitoring)
- 50% Customer operations (main business logic)
- 20% Customer type operations
- 20% Customer status operations

Within each category:

- 30-40% Create operations
- 20% List operations
- 15% Get specific resource
- 10-15% Update operations
- 5-10% Delete operations

## Configuration

Load test configuration is managed through:

- `config.json` - Main configuration file
- `config.json.dist` - Default configuration template
- Environment variables (API_HOST, API_PORT, ENVIRONMENT)

## Results

Test results are saved in the `results/` directory as JSON files with timestamps:

```
results/
├── customers-smoke-20231021-143022.json
├── api-comprehensive-stress-20231021-143155.json
└── ...
```

## AWS Load Testing

For large-scale load testing on AWS infrastructure:

```bash
make aws-load-tests         # Deploy and run on AWS
make aws-load-tests-cleanup # Clean up AWS resources
```

## Best Practices

1. **Start with smoke tests** to verify basic functionality
2. **Use comprehensive tests** for realistic traffic simulation
3. **Monitor application metrics** during load tests
4. **Scale gradually** from smoke → average → stress → spike
5. **Clean up test data** after testing (automatic in scripts)

## Troubleshooting

### Common Issues

- **Connection refused**: Ensure the API server is running
- **Authentication errors**: Check if endpoints require authentication
- **Memory issues**: Reduce VUs or duration for resource-constrained environments

### Debug Mode

Add `--verbose` flag to K6 commands for detailed output:

```bash
docker run --rm --network host -v "$(pwd)":/app -w /app k6 run --verbose scripts/customers.js
```

## Extending Tests

To add new endpoint tests:

1. Create a new `.js` file in `scripts/` directory
2. Follow the existing pattern with setup/teardown
3. Use the ScenarioUtils and Utils classes
4. Add realistic data generation
5. Include proper error checking with utils.checkResponse()

The test discovery is automatic - new scripts will be included in load test runs.
