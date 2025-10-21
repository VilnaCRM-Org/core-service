import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertCustomerTypesUtils from '../utils/insertCustomerTypesUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'deleteCustomerType';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertTypesUtils = new InsertCustomerTypesUtils(utils, scenarioName);

const types = insertTypesUtils.loadInsertedTypes();

export function setup() {
  return {
    types: types,
  };
}

export const options = scenarioUtils.getOptions();

export default function deleteCustomerType(data) {
  const type = data.types[counter.up() % data.types.length];
  utils.checkCustomerIsDefined(type);

  const { '@id': id } = type;

  const response = http.del(`http://localhost:80${id}`);

  utils.checkResponse(response, 'is status 204', res => res.status === 204);
}

export function teardown(data) {
  console.log(`Deleted customer types during load test from pool of ${data.types.length}`);
}
