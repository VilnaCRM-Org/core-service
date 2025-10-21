import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'deleteCustomerType';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create test customer types specifically for deletion
  // Create enough for smoke test: 5 rps * 10s = 50 iterations
  const types = [];
  
  for (let i = 0; i < 60; i++) {
    const typeData = {
      value: `DeleteTestType_${i}_${Date.now()}`
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
  if (data.types.length === 0) {
    console.warn('No customer types available for deletion');
    return;
  }

  // Use counter to select different type for each iteration
  const typeIndex = counter.up() % data.totalTypes;
  const type = data.types[typeIndex];

  if (!type) {
    console.warn(`Customer type at index ${typeIndex} not found`);
    return;
  }

  const response = http.del(`http://localhost:80${type['@id']}`);

  utils.checkResponse(response, 'is status 204', res => res.status === 204);
}

export function teardown(data) {
  console.log(`Deleted customer types during load test from pool of ${data.totalTypes}`);
}
