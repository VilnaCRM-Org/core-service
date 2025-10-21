import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';
import counter from 'k6/x/counter';

const scenarioName = 'updateCustomerType';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customer types created by PrepareCustomers script
  const response = http.get(`${utils.getBaseHttpUrl()}/customer_types?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customer types for update customer type load test.');
  }

  const data = JSON.parse(response.body);
  const types = data.member || [];

  if (types.length === 0) {
    throw new Error('No customer types found. Please run PrepareCustomers script first.');
  }

  return {
    types: types,
    totalTypes: types.length,
  };
}

export default function updateCustomerType(data) {
  if (data.types.length === 0) {
    console.warn('No customer types available for update');
    return;
  }

  // Use counter to select different type for each iteration
  const type = data.types[counter.up() % data.totalTypes];

  const typeNames = [
    'Premium',
    'Standard',
    'VIP',
    'Enterprise',
    'Starter',
    'Professional',
    'Basic',
  ];
  const updateData = {
    value: `${typeNames[Math.floor(Math.random() * typeNames.length)]}_Updated_${randomString(6)}`,
  };

  const response = http.patch(`http://localhost:80${type['@id']}`, JSON.stringify(updateData), {
    headers: { 'Content-Type': 'application/merge-patch+json' },
  });

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Types will be cleaned up by CleanupCustomers script
  console.log(`Tested ${data.totalTypes} customer types during update operations`);
}
