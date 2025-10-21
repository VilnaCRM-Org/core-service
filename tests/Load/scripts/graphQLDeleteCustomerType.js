import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'graphQLDeleteCustomerType';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create test customer types specifically for deletion
  // Create enough for smoke test: 5 rps * 10s = 50 iterations
  const types = [];
  
  for (let i = 0; i < 60; i++) {
    const typeData = {
      value: `GraphQLDeleteTestType_${i}_${Date.now()}`
    };
    
    const response = utils.createCustomerType(typeData);
    
    if (response.status === 201) {
      const type = JSON.parse(response.body);
      types.push(type);
    }
  }
  
  return { 
    types: types,
    totalTypes: types.length 
  };
}

export default function deleteCustomerType(data) {
  // Use counter to select different type for each iteration
  const typeIndex = counter.up() % data.totalTypes;
  const type = data.types[typeIndex];

  if (!type) {
    console.warn(`Customer type at index ${typeIndex} not found`);
    return;
  }

  const mutation = `
    mutation {
      deleteCustomerType(
        input: {
          id: "${type['@id']}"
        }
      ) {
        customerType {
          id
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(
    response,
    'customer type deleted',
    res => {
      if (res.status !== 200) {
        console.error(`GraphQL delete failed with status ${res.status}: ${res.body}`);
        return false;
      }
      const body = JSON.parse(res.body);
      if (body.errors) {
        console.error(`GraphQL errors: ${JSON.stringify(body.errors)}`);
        return false;
      }
      return body.data && body.data.deleteCustomerType && body.data.deleteCustomerType.customerType;
    }
  );
}

export function teardown(data) {
  console.log(`Deleted customer types during GraphQL load test from pool of ${data.totalTypes}`);
}
