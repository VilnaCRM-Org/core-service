import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'createCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create required customer type and status for testing
  const typeData = { value: `LoadTestType_${Date.now()}` };
  const statusData = { value: `LoadTestStatus_${Date.now()}` };
  
  const typeResponse = utils.createCustomerType(typeData);
  const statusResponse = utils.createCustomerStatus(statusData);
  
  let setupData = { customerType: null, customerStatus: null };
  
  if (typeResponse.status === 201) {
    setupData.customerType = JSON.parse(typeResponse.body);
  }
  
  if (statusResponse.status === 201) {
    setupData.customerStatus = JSON.parse(statusResponse.body);
  }
  
  return setupData;
}

export default function createCustomer(data) {
  const customerData = generateCustomerData(data);
  
  const response = utils.createCustomer(customerData);
  
  utils.checkResponse(response, 'is status 201', res => res.status === 201);
}

export function teardown(data) {
  // Clean up created test data
  if (data.customerType) {
    http.del(`${utils.getBaseHttpUrl()}${data.customerType['@id']}`);
  }
  
  if (data.customerStatus) {
    http.del(`${utils.getBaseHttpUrl()}${data.customerStatus['@id']}`);
  }
}

function generateCustomerData(data) {
  const domains = ['example.com', 'test.org', 'demo.net', 'sample.co'];
  const leadSources = ['Website', 'Referral', 'Social Media', 'Email Campaign'];
  const name = `Customer_${randomString(8)}`;
  const domain = domains[Math.floor(Math.random() * domains.length)];
  
  const customerData = {
    initials: name,
    email: `${name.toLowerCase()}@${domain}`,
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
    leadSource: leadSources[Math.floor(Math.random() * leadSources.length)],
    confirmed: Math.random() > 0.5
  };
  
  // Add type and status if available from setup
  if (data && data.customerType) {
    customerData.type = data.customerType['@id'];
  }
  
  if (data && data.customerStatus) {
    customerData.status = data.customerStatus['@id'];
  }
  
  return customerData;
}
