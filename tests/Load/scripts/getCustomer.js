import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'getCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

let testCustomerId = null;

export function setup() {
  // Create a test customer for getting
  const customerData = {
    initials: 'GetTest Customer',
    email: `gettest_${Date.now()}@example.com`,
    phone: '+1-555-0001',
    leadSource: 'Load Test',
    confirmed: true,
  };

  const response = utils.createCustomer(customerData);

  if (response.status === 201) {
    const customer = JSON.parse(response.body);
    return { customerId: customer['@id'] };
  }

  return { customerId: null };
}

export default function getCustomer(data) {
  if (!data.customerId) {
    console.log('No customer ID available for testing');
    return;
  }

  const response = http.get(`${utils.getBaseHttpUrl()}${data.customerId}`);

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Clean up the test customer
  if (data.customerId) {
    http.del(`${utils.getBaseHttpUrl()}${data.customerId}`);
  }
}
