import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertCustomerStatusesUtils from '../utils/insertCustomerStatusesUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLDeleteCustomerStatus';

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

  const mutation = `
    mutation {
      deleteCustomerStatus(
        input: {
          id: "${status['@id']}"
        }
      ) {
        customerStatus {
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
    'customer status deleted',
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
      return body.data && body.data.deleteCustomerStatus && body.data.deleteCustomerStatus.customerStatus;
    }
  );
}

export function teardown(data) {
  console.log(`Deleted customer statuses during GraphQL load test from pool of ${data.statuses.length}`);
}
