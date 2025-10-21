# Load Testing Skill

## Overview
This skill provides comprehensive guidance for creating and managing load tests using K6 for both REST and GraphQL APIs. It follows established patterns from the VilnaCRM ecosystem and ensures professional, maintainable, and effective load testing.

## Core Principles

### 1. Individual Endpoint Testing
- Create separate test scripts for each endpoint (REST) or operation (GraphQL)
- Follow the pattern: `createResource.js`, `getResource.js`, `updateResource.js`, `deleteResource.js`
- For GraphQL: `graphQLCreateResource.js`, `graphQLGetResource.js`, etc.
- Avoid composite/random operation scripts for better debugging and clarity

### 2. Deterministic Testing
- **NEVER use random operations** in load tests
- Use predictable, iteration-based patterns (`__ITER % N`)
- Ensure reproducible results for reliable performance analysis

### 3. Proper Resource Management
- Implement `setup()` function to create test dependencies
- Implement `teardown()` function to clean up test data
- Use proper IRI handling for REST APIs
- Use proper ID handling for GraphQL queries/mutations

### 4. Automatic Integration
- All test scripts are automatically discovered from `tests/Load/scripts/`
- No separate commands needed - GraphQL and REST tests run together
- Use existing Makefile commands: `make smoke-load-tests`, `make average-load-tests`, etc.

---

## REST API Load Tests

### Script Structure Template

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

### REST Load Test Types

#### 1. Create Operations
- **Purpose**: Test resource creation endpoints
- **Pattern**: POST requests with valid payloads
- **Setup**: Create dependencies (types, statuses, etc.)
- **Validation**: Check for 201 status codes

#### 2. Read Operations
- **Get Single**: Test individual resource retrieval
- **Get Collection**: Test listing with pagination and filters
- **Pattern**: GET requests with various query parameters
- **Setup**: Create test resources to retrieve

#### 3. Update Operations
- **Partial Update**: PATCH with `application/merge-patch+json`
- **Full Replace**: PUT with `application/ld+json`
- **Setup**: Create resources to update
- **Validation**: Check for 200 status codes

#### 4. Delete Operations
- **Purpose**: Test resource deletion
- **Pattern**: DELETE requests
- **Setup**: Create resources to delete
- **Validation**: Check for 204 status codes

---

## GraphQL Load Tests

### Script Structure Template

```javascript
import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'graphQLOperationResource'; // e.g., 'graphQLCreateCustomer'

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create dependencies using REST API (faster for setup)
  const typeData = { value: `GraphQLTestType_${Date.now()}` };
  const typeResponse = utils.createCustomerType(typeData);

  if (typeResponse.status === 201) {
    const type = JSON.parse(typeResponse.body);
    return {
      typeIri: type['@id'],
      createdResources: []
    };
  }

  return { createdResources: [] };
}

export default function graphQLOperationResource(data) {
  if (!data || !data.typeIri) {
    return;
  }

  const mutation = `
    mutation {
      createResource(
        input: {
          name: "TestResource_${randomString(8)}"
          type: "${data.typeIri}"
        }
      ) {
        resource {
          id
          name
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getJsonHeader()
  );

  utils.checkResponse(
    response,
    'resource created',
    res => {
      const body = JSON.parse(res.body);
      if (body.data && body.data.createResource && body.data.createResource.resource) {
        // Track for cleanup
        data.createdResources.push(body.data.createResource.resource.id);
        return true;
      }
      return false;
    }
  );
}

export function teardown(data) {
  // Clean up via REST API (faster for cleanup)
  if (data.createdResources) {
    data.createdResources.forEach(resourceId => {
      http.del(`${utils.getBaseHttpUrl()}${resourceId}`);
    });
  }

  if (data.typeIri) {
    http.del(`${utils.getBaseHttpUrl()}${data.typeIri}`);
  }
}
```

### GraphQL Load Test Types

#### 1. Query Operations (Read)

**Get Single Resource:**
```javascript
const query = `
  query {
    resource(id: "/api/resources/${data.resourceId}") {
      id
      name
      field1
      field2
      relatedResource {
        id
        name
      }
    }
  }`;
```

**Get Collection with Pagination:**
```javascript
const query = `
  query {
    resources(first: 10) {
      edges {
        node {
          id
          name
        }
      }
      pageInfo {
        hasNextPage
        hasPreviousPage
        startCursor
        endCursor
      }
      totalCount
    }
  }`;
```

#### 2. Mutation Operations (Write)

**Create Resource:**
```javascript
const mutation = `
  mutation {
    createResource(
      input: {
        name: "${name}"
        field1: "${value1}"
        field2: ${value2}
        clientMutationId: "test"
      }
    ) {
      resource {
        id
        name
      }
    }
  }`;
```

**Update Resource:**
```javascript
const mutation = `
  mutation {
    updateResource(
      input: {
        id: "${resourceId}"
        name: "${newName}"
        clientMutationId: "test"
      }
    ) {
      resource {
        id
        name
      }
    }
  }`;
```

**Delete Resource:**
```javascript
const mutation = `
  mutation {
    deleteResource(
      input: {
        id: "${resourceId}"
        clientMutationId: "test"
      }
    ) {
      resource {
        id
      }
    }
  }`;
```

### GraphQL-Specific Best Practices

#### 1. ID Format
- Use full IRI format: `/api/resources/01234`
- Extract IRI from REST API creation: `resource['@id']`
- Extract ID from IRI: `iri.split('/').pop()`

#### 2. Response Validation
```javascript
utils.checkResponse(
  response,
  'query returned data',
  res => {
    const body = JSON.parse(res.body);
    // Check for data, not errors
    return body.data && body.data.resource && !body.errors;
  }
);
```

#### 3. Setup/Teardown Strategy
- **Setup**: Use REST API for faster dependency creation
- **Test**: Use GraphQL for the actual load test
- **Teardown**: Use REST API for faster cleanup

#### 4. Naming Convention
- Prefix all GraphQL tests with `graphQL`
- Follow REST naming: `graphQLGetCustomer`, `graphQLCreateCustomer`
- This ensures automatic discovery and clear differentiation

---

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

### Configuration Guidelines

- **threshold**: Maximum acceptable response time (ms)
- **rps**: Target requests per second
- **vus**: Virtual users (concurrent connections)
- **duration**: Test duration in seconds or object with rise/plateau/fall

---

## Utils Class Extensions

### Adding REST Endpoints

```javascript
// In tests/Load/utils/utils.js
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

### Adding GraphQL Support

```javascript
// In constructor
constructor() {
  const config = this.getConfig();
  const host = config.apiHost;
  const port = config.apiPort;

  this.baseUrl = `http://${host}:${port}/api`;
  this.baseHttpUrl = this.baseUrl;
  this.baseGraphQLUrl = this.baseUrl + '/graphql'; // Add this
}

// Add getter
getBaseGraphQLUrl() {
  return this.baseGraphQLUrl;
}
```

---

## IRI and ID Handling

### REST API: IRI Handling
```javascript
// When storing created resource IDs
if (response.status === 201) {
  const resource = JSON.parse(response.body);
  // Store the full IRI path
  createdResources.push(resource['@id']);
}

// When using for HTTP requests
http.del(`${utils.getBaseHttpUrl()}${resourceIri}`);
```

### GraphQL: ID Extraction
```javascript
// Extract ID from IRI for GraphQL queries
const resourceId = resource['@id'].split('/').pop();

// Use full IRI in GraphQL queries
const query = `
  query {
    resource(id: "${resource['@id']}") {
      id
    }
  }`;
```

---

## Data Generation Guidelines

### 1. Realistic Data
```javascript
function generateCustomerData() {
  const domains = ['example.com', 'test.org', 'demo.net'];
  const leadSources = ['Website', 'Referral', 'Social Media'];
  const name = `Customer_${randomString(8)}`;

  return {
    initials: name,
    email: `${name.toLowerCase()}@${domains[Math.floor(Math.random() * domains.length)]}`,
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
    leadSource: leadSources[Math.floor(Math.random() * leadSources.length)],
    confirmed: Math.random() > 0.5,
  };
}
```

### 2. Timestamp-Based Uniqueness
```javascript
// Ensure unique values with timestamps
const uniqueValue = `TestValue_${Date.now()}_${randomString(6)}`;
const email = `test_${Date.now()}@example.com`;
```

---

## Load Test Levels

### 1. Smoke Tests (Minimal Load)
- **VUs**: 2-5
- **Duration**: 10 seconds
- **Purpose**: Basic functionality verification
- **Success Rate**: 100%

### 2. Average Tests (Normal Load)
- **VUs**: 10-20
- **Duration**: 2-3 minutes with ramp-up/down
- **Purpose**: Normal traffic simulation
- **Success Rate**: >99%

### 3. Stress Tests (High Load)
- **VUs**: 30-80
- **Duration**: 5-15 minutes with ramp-up/down
- **Purpose**: Find breaking points
- **Success Rate**: >95%

### 4. Spike Tests (Extreme Load)
- **VUs**: 100-200
- **Duration**: Short bursts (1-3 minutes)
- **Purpose**: Test resilience under sudden load
- **Success Rate**: >90%

---

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

// Wrong GraphQL response parsing
const id = response.body.data.resource.id; // Will fail if errors exist
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

// Safe GraphQL response parsing
const body = JSON.parse(response.body);
if (body.data && body.data.resource && !body.errors) {
  const id = body.data.resource.id;
}
```

---

## Running Load Tests

### Automatic Discovery
All scripts in `tests/Load/scripts/` are automatically discovered. Both REST and GraphQL tests run together.

### Available Commands
```bash
# All load tests (REST + GraphQL)
make load-tests

# Specific load levels (REST + GraphQL)
make smoke-load-tests
make average-load-tests
make stress-load-tests
make spike-load-tests

# Individual script
make execute-load-tests-script scenario=createCustomer
make execute-load-tests-script scenario=graphQLCreateCustomer

# List all available scenarios
./tests/Load/get-load-test-scenarios.sh

# List only GraphQL scenarios
./tests/Load/get-load-test-scenarios.sh | grep -i graphql
```

### Configuration Management
- Main config: `tests/Load/config.json`
- Fallback: `tests/Load/config.json.dist`
- Environment variables: `API_HOST`, `API_PORT`

---

## Checklist for New Load Tests

### Before Creating
- [ ] Identify the specific endpoint/operation to test
- [ ] Determine if REST or GraphQL (or both)
- [ ] Identify required dependencies (types, statuses, etc.)
- [ ] Plan realistic test data generation
- [ ] Choose appropriate load test parameters

### During Creation
- [ ] Follow the appropriate script structure template (REST or GraphQL)
- [ ] Implement proper setup/teardown functions
- [ ] Use deterministic operations (no random)
- [ ] Handle IRI/ID paths correctly
- [ ] Add configuration to `config.json.dist`
- [ ] Extend Utils class if needed (new helper methods)
- [ ] Use proper naming: `graphQL` prefix for GraphQL tests

### After Creation
- [ ] Verify automatic discovery: `./tests/Load/get-load-test-scenarios.sh`
- [ ] Test with smoke load first
- [ ] Verify 100% success rate in controlled environment
- [ ] Check that cleanup works properly (no leftover data)
- [ ] Document any special requirements
- [ ] Confirm integration with existing commands

---

## Complete Example: Customer CRUD

### REST: Create Customer
```javascript
// File: tests/Load/scripts/createCustomer.js
import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'createCustomer';
const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  const typeData = { value: `CreateType_${Date.now()}` };
  const statusData = { value: `CreateStatus_${Date.now()}` };

  const typeResponse = utils.createCustomerType(typeData);
  const statusResponse = utils.createCustomerStatus(statusData);

  return {
    type: JSON.parse(typeResponse.body),
    status: JSON.parse(statusResponse.body),
    createdCustomers: []
  };
}

export default function createCustomer(data) {
  const name = `Customer_${randomString(8)}`;
  const customerData = {
    initials: name,
    email: `${name.toLowerCase()}@example.com`,
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
    leadSource: 'Load Test',
    type: data.type['@id'],
    status: data.status['@id'],
    confirmed: true,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  };

  const response = utils.createCustomer(customerData);

  utils.checkResponse(response, 'is status 201', res => {
    if (res.status === 201) {
      const customer = JSON.parse(res.body);
      data.createdCustomers.push(customer['@id']);
      return true;
    }
    return false;
  });
}

export function teardown(data) {
  data.createdCustomers.forEach(id => http.del(`${utils.getBaseHttpUrl()}${id}`));
  http.del(`${utils.getBaseHttpUrl()}${data.type['@id']}`);
  http.del(`${utils.getBaseHttpUrl()}${data.status['@id']}`);
}
```

### GraphQL: Create Customer
```javascript
// File: tests/Load/scripts/graphQLCreateCustomer.js
import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'graphQLCreateCustomer';
const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Use REST for faster setup
  const typeData = { value: `GraphQLType_${Date.now()}` };
  const statusData = { value: `GraphQLStatus_${Date.now()}` };

  const typeResponse = utils.createCustomerType(typeData);
  const statusResponse = utils.createCustomerStatus(statusData);

  return {
    typeIri: JSON.parse(typeResponse.body)['@id'],
    statusIri: JSON.parse(statusResponse.body)['@id'],
    createdCustomers: []
  };
}

export default function graphQLCreateCustomer(data) {
  const name = `GraphQL_Customer_${randomString(8)}`;
  const email = `${name.toLowerCase()}@example.com`;
  const now = new Date().toISOString();

  const mutation = `
    mutation {
      createCustomer(
        input: {
          initials: "${name}"
          email: "${email}"
          phone: "+1-555-${Math.floor(Math.random() * 9000) + 1000}"
          leadSource: "GraphQL Load Test"
          type: "${data.typeIri}"
          status: "${data.statusIri}"
          confirmed: true
          createdAt: "${now}"
          updatedAt: "${now}"
        }
      ) {
        customer {
          id
          initials
          email
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getJsonHeader()
  );

  utils.checkResponse(response, 'customer created', res => {
    const body = JSON.parse(res.body);
    if (body.data && body.data.createCustomer && body.data.createCustomer.customer) {
      data.createdCustomers.push(body.data.createCustomer.customer.id);
      return body.data.createCustomer.customer.email === email;
    }
    return false;
  });
}

export function teardown(data) {
  // Use REST for faster cleanup
  data.createdCustomers.forEach(id => http.del(`${utils.getBaseHttpUrl()}${id}`));
  http.del(`${utils.getBaseHttpUrl()}${data.typeIri}`);
  http.del(`${utils.getBaseHttpUrl()}${data.statusIri}`);
}
```

---

## Performance Monitoring

### Success Criteria
- **Smoke Tests**: 100% success rate
- **Average Tests**: >99% success rate
- **Stress Tests**: >95% success rate
- **Response Times**: <threshold configured per endpoint

### Key Metrics
- HTTP status codes (201, 200, 204 for success)
- Response times (avg, p95, p99)
- Error rates and types
- Throughput (requests per second)
- Resource utilization

---

## Troubleshooting

### High Error Rates
1. Check API server capacity and resources
2. Verify test data dependencies are created
3. Review IRI/ID path construction
4. Check for resource conflicts (unique constraints)
5. Validate GraphQL query syntax

### Slow Response Times
1. Analyze database query performance
2. Check for N+1 query problems
3. Review API endpoint optimization
4. Consider caching strategies
5. Check network latency

### Setup/Teardown Failures
1. Verify dependency creation order
2. Check cleanup logic and error handling
3. Review timeout settings in config
4. Ensure proper resource tracking
5. Check for orphaned test data

---

This skill ensures consistent, professional, and effective load testing for both REST and GraphQL APIs across all VilnaCRM projects.
