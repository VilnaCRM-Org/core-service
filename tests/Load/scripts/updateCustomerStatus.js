import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';
import counter from 'k6/x/counter';

const scenarioName = 'updateCustomerStatus';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customer statuses created by PrepareCustomers script
  const response = http.get(`${utils.getBaseHttpUrl()}/customer_statuses?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customer statuses for update customer status load test.');
  }

  const data = JSON.parse(response.body);
  const statuses = data.member || [];

  if (statuses.length === 0) {
    throw new Error('No customer statuses found. Please run PrepareCustomers script first.');
  }

  return {
    statuses: statuses,
    totalStatuses: statuses.length,
  };
}

export default function updateCustomerStatus(data) {
  if (data.statuses.length === 0) {
    console.warn('No customer statuses available for update');
    return;
  }

  // Use counter to select different status for each iteration
  const status = data.statuses[counter.up() % data.totalStatuses];

  const statusNames = ['Active', 'Inactive', 'Lead', 'Prospect', 'Converted', 'Churned', 'Pending'];
  const updateData = {
    value: `${statusNames[Math.floor(Math.random() * statusNames.length)]}_Updated_${randomString(6)}`,
  };

  const response = http.patch(`http://localhost:80${status['@id']}`, JSON.stringify(updateData), {
    headers: { 'Content-Type': 'application/merge-patch+json' },
  });

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Statuses will be cleaned up by CleanupCustomers script
  console.log(`Tested ${data.totalStatuses} customer statuses during update operations`);
}
