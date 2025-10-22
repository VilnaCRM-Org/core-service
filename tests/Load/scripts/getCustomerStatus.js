import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'getCustomerStatus';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customer statuses created by PrepareCustomers script
  const response = http.get(`${utils.getBaseUrl()}/customer_statuses?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customer statuses for get customer status load test.');
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

export default function getCustomerStatus(data) {
  // Use counter to select different status for each iteration
  const status = data.statuses[counter.up() % data.totalStatuses];

  const response = http.get(`http://localhost:80${status['@id']}`);

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Statuses will be cleaned up by CleanupCustomers script
  console.log(`Tested ${data.totalStatuses} customer statuses during get operations`);
}
