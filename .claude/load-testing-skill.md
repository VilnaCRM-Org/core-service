# Load Testing Skill

## Overview
This skill provides comprehensive guidance for creating and managing load tests using K6 with a focus on REST API endpoints. It follows the established patterns from the VilnaCRM ecosystem and ensures professional, maintainable, and effective load testing.

## Core Principles

### 1. Individual Endpoint Testing
- Create separate test scripts for each REST endpoint
- Follow the pattern: `createResource.js`, `getResource.js`, `updateResource.js`, `deleteResource.js`
- Avoid composite/random operation scripts for better debugging and clarity

### 2. Deterministic Testing
- **NEVER use random operations** in load tests
- Use predictable, iteration-based patterns (`__ITER % N`)
- Ensure reproducible results for reliable performance analysis

### 3. Proper Resource Management
- Implement `setup()` function to create test dependencies
- Implement `teardown()` function to clean up test data
- Use proper IRI handling to avoid URL construction issues

## Script Structure Template

```javascript
import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'operationResource'; // e.g., 'createCustomer'

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create required dependencies (types, statuses, etc.)
  const dependencyData = { value: `TestDep_${Date.now()}` };
  const response = utils.createDependency(dependencyData);
  
  if (response.status === 201) {
    return { dependency: JSON.parse(response.body) };
  }
  
  return { dependency: null };
}

export default function operationResource(data) {
  // Main test logic here
  const resourceData = generateResourceData(data);
  const response = utils.createResource(resourceData);
  
  utils.checkResponse(response, 'is status 201', res => res.status === 201);
}

export function teardown(data) {
  // Clean up created test data
  if (data.dependency) {
    http.del(`${utils.getBaseHttpUrl()}${data.dependency['@id']}`);
  }
}

function generateResourceData(data) {
  // Generate realistic test data
  const resourceData = {
    name: `TestResource_${randomString(8)}`,
    // ... other fields
  };
  
  // Add dependencies if available
  if (data && data.dependency) {
    resourceData.dependency = data.dependency['@id'];
  }
  
  return resourceData;
}
```

## Configuration Pattern

Each script must have corresponding configuration in `config.json.dist`:

```json
"scriptName": {
    "setupTimeoutInMinutes": 30,
    "teardownTimeoutInMinutes": 30,
    "smoke": {
        "threshold": 500,
        "rps": 5,
        "vus": 3,
        "duration": 10
    },
    "average": {
        "threshold": 800,
        "rps": 15,
        "vus": 15,
        "duration": {
            "rise": 2,
            "plateau": 8,
            "fall": 2
        }
    },
    "stress": {
        "threshold": 1500,
        "rps": 50,
        "vus": 50,
        "duration": {
            "rise": 3,
            "plateau": 10,
            "fall": 3
        }
    },
    "spike": {
        "threshold": 3000,
        "rps": 100,
        "vus": 100,
        "duration": {
            "rise": 3,
            "fall": 3
        }
    }
}
```

## Load Test Types

### 1. Create Operations
- **Purpose**: Test resource creation endpoints
- **Pattern**: POST requests with valid payloads
- **Setup**: Create dependencies (types, statuses, etc.)
- **Validation**: Check for 201 status codes

### 2. Read Operations
- **Get Single**: Test individual resource retrieval
- **Get Collection**: Test listing with pagination and filters
- **Pattern**: GET requests with various query parameters
- **Setup**: Create test resources to retrieve

### 3. Update Operations
- **Partial Update**: PATCH with `application/merge-patch+json`
- **Full Replace**: PUT with `application/ld+json`
- **Setup**: Create resources to update
- **Validation**: Check for 200 status codes

### 4. Delete Operations
- **Purpose**: Test resource deletion
- **Pattern**: DELETE requests
- **Setup**: Create resources to delete
- **Validation**: Check for 204 status codes

## Utils Class Extensions

When adding new endpoints, extend the Utils class:

```javascript
// In utils/utils.js
createResource(resourceData) {
  const payload = JSON.stringify(resourceData);
  return http.post(`${this.baseHttpUrl}/resources`, payload, this.getJsonHeader());
}

getJsonHeader() {
  return {
    headers: {
      'Content-Type': 'application/ld+json',
    },
  };
}

getMergePatchHeader() {
  return {
    headers: {
      'Content-Type': 'application/merge-patch+json',
    },
  };
}
```

## IRI Handling Best Practices

### Problem: Double API Paths
Avoid: `http://localhost/api/api/resources/123`

### Solution: Consistent IRI Management
```javascript
// When storing created resource IDs
if (response.status === 201) {
  const resource = JSON.parse(response.body);
  // Store just the path part, not full IRI
  const resourceId = resource['@id'].replace('/api', '');
  createdResources.push(resourceId);
}

// When using for HTTP requests
const response = http.get(`${baseUrl}${resourceId}`);
```

## Data Generation Guidelines

### 1. Realistic Data
```javascript
function generateCustomerData() {
  const domains = ['example.com', 'test.org', 'demo.net'];
  const name = `Customer_${randomString(8)}`;
  
  return {
    initials: name,
    email: `${name.toLowerCase()}@${randomItem(domains)}`,
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
    // ... other realistic fields
  };
}
```

### 2. Avoid Hardcoded Values
- Use timestamp-based unique identifiers
- Generate random but valid data
- Ensure email uniqueness with timestamps

## Testing Patterns

### 1. Smoke Tests (Minimal Load)
- **VUs**: 2-5
- **Duration**: 10-15 seconds
- **Purpose**: Basic functionality verification

### 2. Average Tests (Normal Load)
- **VUs**: 10-20
- **Duration**: 2-3 minutes with ramp-up/down
- **Purpose**: Normal traffic simulation

### 3. Stress Tests (High Load)
- **VUs**: 30-80
- **Duration**: 5-15 minutes with ramp-up/down
- **Purpose**: Find breaking points

### 4. Spike Tests (Extreme Load)
- **VUs**: 100-200
- **Duration**: Short bursts (1-3 minutes)
- **Purpose**: Test resilience under sudden load

## Common Pitfalls to Avoid

### ❌ Don't Do This
```javascript
// Random operations - unpredictable results
const operation = Math.random();
if (operation < 0.3) {
  createResource();
} else if (operation < 0.6) {
  updateResource();
}

// Hardcoded test data
const customer = {
  email: 'test@example.com', // Will cause conflicts
  name: 'Test Customer'
};

// Missing cleanup
export default function test() {
  createResource();
  // No teardown - leaves test data
}
```

### ✅ Do This Instead
```javascript
// Deterministic operations
const operationIndex = __ITER % 3;
switch (operationIndex) {
  case 0: createResource(); break;
  case 1: updateResource(); break;
  case 2: deleteResource(); break;
}

// Dynamic test data
const customer = {
  email: `test_${Date.now()}_${randomString(6)}@example.com`,
  name: `TestCustomer_${randomString(8)}`
};

// Proper cleanup
export function teardown(data) {
  if (data.createdResources) {
    data.createdResources.forEach(id => {
      http.del(`${utils.getBaseHttpUrl()}${id}`);
    });
  }
}
```

## Integration with Existing Infrastructure

### Automatic Discovery
- Scripts in `tests/Load/scripts/` are automatically discovered
- Use `./tests/Load/get-load-test-scenarios.sh` to list available scripts

### Running Tests
```bash
# All load tests
make load-tests

# Specific load levels
make smoke-load-tests
make average-load-tests
make stress-load-tests
make spike-load-tests

# Individual script
make execute-load-tests-script scenario=createCustomer
```

### Configuration Management
- Main config: `tests/Load/config.json`
- Fallback: `tests/Load/config.json.dist`
- Environment variables: `API_HOST`, `API_PORT`

## Checklist for New Load Tests

### Before Creating
- [ ] Identify the specific endpoint to test
- [ ] Determine required dependencies (types, statuses, etc.)
- [ ] Plan realistic test data generation
- [ ] Choose appropriate load test parameters

### During Creation
- [ ] Follow the script structure template
- [ ] Implement proper setup/teardown functions
- [ ] Use deterministic operations (no random)
- [ ] Handle IRI paths correctly
- [ ] Add configuration to `config.json.dist`
- [ ] Extend Utils class if needed

### After Creation
- [ ] Test with smoke load first
- [ ] Verify 100% success rate in controlled environment
- [ ] Check that cleanup works properly
- [ ] Document any special requirements
- [ ] Add to CI/CD pipeline if applicable

## Performance Expectations

### Success Criteria
- **Smoke Tests**: 100% success rate
- **Average Tests**: >99% success rate
- **Stress Tests**: >95% success rate
- **Response Times**: <500ms for most operations

### Monitoring Points
- HTTP status codes (201, 200, 204 for success)
- Response times (avg, p95, p99)
- Error rates
- Throughput (requests per second)
- Resource utilization

## Troubleshooting Common Issues

### High Error Rates
1. Check API server capacity
2. Verify test data dependencies
3. Review IRI path construction
4. Check for resource conflicts

### Slow Response Times
1. Analyze database query performance
2. Check for N+1 query problems
3. Review API endpoint optimization
4. Consider caching strategies

### Setup/Teardown Failures
1. Verify dependency creation order
2. Check cleanup logic
3. Review timeout settings
4. Ensure proper error handling

This skill ensures consistent, professional, and effective load testing across all VilnaCRM projects.
