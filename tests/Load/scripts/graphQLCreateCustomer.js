import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';
import counter from 'k6/x/counter';

const scenarioName = 'graphQLCreateCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customer types and statuses from PrepareCustomers
  const typesResponse = http.get(`${utils.getBaseHttpUrl()}/customer_types?itemsPerPage=100`);
  const statusesResponse = http.get(`${utils.getBaseHttpUrl()}/customer_statuses?itemsPerPage=100`);

  if (typesResponse.status !== 200 || statusesResponse.status !== 200) {
    throw new Error('Failed to fetch types/statuses for GraphQL create customer load test.');
  }

  const typesData = JSON.parse(typesResponse.body);
  const statusesData = JSON.parse(statusesResponse.body);
  const types = typesData.member || [];
  const statuses = statusesData.member || [];

  if (types.length === 0 || statuses.length === 0) {
    throw new Error('No types or statuses found. Please run PrepareCustomers script first.');
  }

  return {
    types: types,
    statuses: statuses,
    totalTypes: types.length,
    totalStatuses: statuses.length,
  };
}

export default function createCustomer(data) {
  // Use counter to rotate through types and statuses
  const type = data.types[counter.up() % data.totalTypes];
  const status = data.statuses[counter.up() % data.totalStatuses];

  const randomStr = randomString(8);
  const name = `GraphQL_Customer_${randomStr}`;
  const email = `${name.toLowerCase()}@example.com`;
  const phone = `+1-555-${Math.floor(Math.random() * 9000) + 1000}`;

  const mutation = `
    mutation {
      createCustomer(
        input: {
          initials: "${name}"
          email: "${email}"
          phone: "${phone}"
          leadSource: "GraphQL Load Test"
          type: "${type['@id']}"
          status: "${status['@id']}"
          confirmed: true
        }
      ) {
        customer {
          id
          initials
          email
          phone
          leadSource
          confirmed
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'customer created', res => {
    if (res.status !== 200) {
      console.error(`GraphQL create failed with status ${res.status}: ${res.body}`);
      return false;
    }
    const body = JSON.parse(res.body);
    if (body.errors) {
      console.error(`GraphQL errors: ${JSON.stringify(body.errors)}`);
      return false;
    }
    if (body.data && body.data.createCustomer && body.data.createCustomer.customer) {
      return body.data.createCustomer.customer.email === email;
    }
    return false;
  });
}

export function teardown(data) {
  // Customers, types, and statuses will be cleaned up by CleanupCustomers script
  console.log(
    `Created customers during GraphQL load test using ${data.totalTypes} types and ${data.totalStatuses} statuses`
  );
}
