import http from 'k6/http';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'getCustomers';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertCustomersUtils = new InsertCustomersUtils(utils, scenarioName);
const customersToGetInOneRequest =
  utils.getConfig().endpoints[scenarioName].customersToGetInOneRequest;

const customers = insertCustomersUtils.loadInsertedCustomers();

export function setup() {
  return {
    customers: customers,
  };
}

export const options = scenarioUtils.getOptions();

export default function getCustomers() {
  let page = utils.getRandomNumber(1, 5);

  const response = http.get(
    `${utils.getBaseHttpUrl()}?page=${page}&itemsPerPage=${customersToGetInOneRequest}`,
    utils.getJsonHeader()
  );

  if (response.status === 500) {
    console.error(`Still 500 error on page ${page}: ${response.body.substring(0, 300)}`);
  }

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}
