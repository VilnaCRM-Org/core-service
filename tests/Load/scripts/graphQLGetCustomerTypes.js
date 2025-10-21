import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLGetCustomerTypes';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customer types created by PrepareCustomers script
  const response = http.get(`${utils.getBaseHttpUrl()}/customer_types?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customer types for GraphQL get customer types load test.');
  }

  const data = JSON.parse(response.body);
  const types = data.member || [];

  if (types.length === 0) {
    throw new Error('No customer types found. Please run PrepareCustomers script first.');
  }

  return {
    types: types.map(t => t['@id']),
    totalTypes: types.length
  };
}

export default function getCustomerTypes(data) {
  const query = `
    query {
      customerTypes(first: 10) {
        edges {
          node {
            id
            value
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

  utils.checkResponse(
    response,
    'customerTypes query returned',
    res => {
      if (res.status !== 200) {
        console.error(`GraphQL request failed with status ${res.status}: ${res.body}`);
        return false;
      }
      const body = JSON.parse(res.body);
      if (body.errors) {
        console.error(`GraphQL errors: ${JSON.stringify(body.errors)}`);
        return false;
      }
      return body.data && body.data.customerTypes && body.data.customerTypes.edges && body.data.customerTypes.edges.length > 0;
    }
  );
}

export function teardown(data) {
  // Types will be cleaned up by CleanupCustomers script
  console.log(`Tested customer types via GraphQL (${data.totalTypes} types available)`);
}
