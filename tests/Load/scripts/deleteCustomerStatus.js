import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'deleteCustomerStatus';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create test customer statuses specifically for deletion
  // Create enough for smoke test: 5 rps * 10s = 50 iterations
  const statuses = [];
  
  for (let i = 0; i < 60; i++) {
    const statusData = {
      value: `DeleteTestStatus_${i}_${Date.now()}`
    };
    
    const response = utils.createCustomerStatus(statusData);
    
    if (response.status === 201) {
      const status = JSON.parse(response.body);
      statuses.push(status);
    }
  }
  
  return { 
    statuses: statuses,
    totalStatuses: statuses.length 
  };
}

export default function deleteCustomerStatus(data) {
  if (data.statuses.length === 0) {
    console.warn('No customer statuses available for deletion');
    return;
  }

  // Use counter to select different status for each iteration
  const statusIndex = counter.up() % data.totalStatuses;
  const status = data.statuses[statusIndex];

  if (!status) {
    console.warn(`Customer status at index ${statusIndex} not found`);
    return;
  }

  const response = http.del(`http://localhost:80${status['@id']}`);

  utils.checkResponse(response, 'is status 204', res => res.status === 204);
}

export function teardown(data) {
  console.log(`Deleted customer statuses during load test from pool of ${data.totalStatuses}`);
}
