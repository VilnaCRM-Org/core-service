import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertCustomerTypesUtils from '../utils/insertCustomerTypesUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLDeleteCustomerType';

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
  // Use atomic counter to select each type exactly once
  const type = data.types[counter.up()];
  utils.checkCustomerIsDefined(type);

  const mutation = `
    mutation {
      deleteCustomerType(
        input: {
          id: "${type['@id']}"
        }
      ) {
        customerType {
          id
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(
    response,
    'customer type deleted',
    res => {
      if (res.status !== 200) {
        console.error(`GraphQL delete failed with status ${res.status}: ${res.body}`);
        return false;
      }
      const body = JSON.parse(res.body);
      if (body.errors) {
        console.error(`GraphQL errors: ${JSON.stringify(body.errors)}`);
        return false;
      }
      return body.data && body.data.deleteCustomerType && body.data.deleteCustomerType.customerType;
    }
  );
}

export function teardown(data) {
  console.log(`Deleted ${data.types.length} customer types during GraphQL load test`);
}
