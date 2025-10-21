import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLGetCustomerStatuses';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customer statuses created by PrepareCustomers script
  const response = http.get(`${utils.getBaseHttpUrl()}/customer_statuses?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error(
      'Failed to fetch customer statuses for GraphQL get customer statuses load test.'
    );
  }

  const data = JSON.parse(response.body);
  const statuses = data.member || [];

  if (statuses.length === 0) {
    throw new Error('No customer statuses found. Please run PrepareCustomers script first.');
  }

  return {
    statuses: statuses.map(s => s['@id']),
    totalStatuses: statuses.length,
  };
}

export default function getCustomerStatuses(data) {
  const query = `
    query {
      customerStatuses(first: 10) {
        edges {
          node {
            id
            value
            ulid
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

  utils.checkResponse(response, 'customerStatuses query returned', res => {
    const body = JSON.parse(res.body);
    return body.data && body.data.customerStatuses && body.data.customerStatuses.edges.length > 0;
  });
}

export function teardown(data) {
  // Statuses will be cleaned up by CleanupCustomers script
  console.log(`Tested customer statuses via GraphQL (${data.totalStatuses} statuses available)`);
}
