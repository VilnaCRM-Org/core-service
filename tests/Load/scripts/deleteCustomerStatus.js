import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertCustomerStatusesUtils from '../utils/insertCustomerStatusesUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'deleteCustomerStatus';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertStatusesUtils = new InsertCustomerStatusesUtils(utils, scenarioName);

const statuses = insertStatusesUtils.loadInsertedStatuses();

export function setup() {
  return {
    statuses: statuses,
  };
}

export const options = scenarioUtils.getOptions();

export default function deleteCustomerStatus(data) {
  const status = data.statuses[counter.up() % data.statuses.length];
  utils.checkCustomerIsDefined(status);

  const { '@id': id } = status;

  const response = http.del(`${utils.getBaseDomain()}${id}`);

  utils.checkResponse(response, 'is status 204', res => res.status === 204);
}

export function teardown(data) {
  console.log(`Deleted customer statuses during load test from pool of ${data.statuses.length}`);
}
