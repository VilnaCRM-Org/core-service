import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'getCustomerStatuses';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create some test customer statuses for listing
  const statuses = [];

  for (let i = 0; i < 3; i++) {
    const statusData = {
      value: `ListTestStatus_${i}_${Date.now()}`,
    };

    const response = utils.createCustomerStatus(statusData);

    if (response.status === 201) {
      const status = JSON.parse(response.body);
      statuses.push(status['@id']);
    }
  }

  return { statusIds: statuses };
}

export default function getCustomerStatuses() {
  // Test different pagination and filtering options
  const filters = ['', '?page=1', '?itemsPerPage=10', '?order[ulid]=desc', '?order[value]=asc'];

  const filter = filters[Math.floor(Math.random() * filters.length)];
  const response = http.get(`${utils.getBaseUrl()}/customer_statuses${filter}`);

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Clean up test customer statuses
  if (data.statusIds) {
    data.statusIds.forEach(statusId => {
      http.del(`${utils.getBaseDomain()}${statusId}`);
    });
  }
}
