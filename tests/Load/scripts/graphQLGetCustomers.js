import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLGetCustomers';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create a few test customers
  const customers = [];

  for (let i = 0; i < 3; i++) {
    const customerTypeData = { value: `GraphQLListType_${i}_${Date.now()}` };
    const typeResponse = utils.createCustomerType(customerTypeData);

    const customerStatusData = { value: `GraphQLListStatus_${i}_${Date.now()}` };
    const statusResponse = utils.createCustomerStatus(customerStatusData);

    if (typeResponse.status === 201 && statusResponse.status === 201) {
      const type = JSON.parse(typeResponse.body);
      const status = JSON.parse(statusResponse.body);

      const customerData = {
        initials: `GraphQL List Customer ${i}`,
        email: `graphql_list_${i}_${Date.now()}@example.com`,
        phone: `+1-555-900${i}`,
        leadSource: 'GraphQL Load Test',
        confirmed: i % 2 === 0,
        type: type['@id'],
        status: status['@id'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      };

      const response = utils.createCustomer(customerData);

      if (response.status === 201) {
        const customer = JSON.parse(response.body);
        customers.push({
          id: customer['@id'],
          typeId: type['@id'],
          statusId: status['@id'],
        });
      }
    }
  }

  return { customers };
}

export default function getCustomers(data) {
  const query = `
    query {
      customers(first: 10) {
        edges {
          node {
            id
            initials
            email
            phone
            leadSource
            confirmed
            createdAt
            updatedAt
          }
        }
        pageInfo {
          hasNextPage
          hasPreviousPage
          startCursor
          endCursor
        }
        totalCount
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: query }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'customers query returned', res => {
    const body = JSON.parse(res.body);
    return body.data && body.data.customers && body.data.customers.edges.length > 0;
  });
}

export function teardown(data) {
  // Clean up test customers
  if (data.customers) {
    data.customers.forEach(customer => {
      http.del(`${utils.getBaseHttpUrl()}${customer.id}`);
      http.del(`${utils.getBaseHttpUrl()}${customer.typeId}`);
      http.del(`${utils.getBaseHttpUrl()}${customer.statusId}`);
    });
  }
}
