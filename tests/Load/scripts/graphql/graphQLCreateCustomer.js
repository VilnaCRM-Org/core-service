import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';

const scenarioName = 'graphQLCreateCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertCustomersUtils = new InsertCustomersUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  const types = insertCustomersUtils.insertCustomerTypes();
  const statuses = insertCustomersUtils.insertCustomerStatuses();
  return { types, statuses };
}

export default function createCustomer(data) {
  const customer = utils.generateCustomer(data.types, data.statuses);
  const mutationName = 'createCustomer';

  const mutation = `
     mutation {
        ${mutationName}(
            input: {
                email: "${customer.email}"
                initials: "${customer.initials}"
                phone: "${customer.phone}"
                leadSource: "${customer.leadSource}"
                confirmed: ${customer.confirmed}
                type: "${customer.type}"
                status: "${customer.status}"
            }
        ) {
            customer {
                email
            }
        }
     }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'created customer returned', res => {
    const body = JSON.parse(res.body);
    // Check for GraphQL errors first
    if (body.errors) {
      console.error('GraphQL errors:', JSON.stringify(body.errors));
      return false;
    }
    // Check if data exists
    if (!body.data || !body.data[mutationName]) {
      console.error('Missing data in response:', JSON.stringify(body));
      return false;
    }
    return body.data[mutationName].customer.email === customer.email;
  });
}
