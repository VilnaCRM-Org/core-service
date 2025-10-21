import http from 'k6/http';
import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLDeleteCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

function calculateTotalNeeded() {
  const config = utils.getConfig().endpoints[scenarioName];
  let total = 0;

  if (utils.getCLIVariable('run_smoke') !== 'false') {
    total += config.smoke.rps * config.smoke.duration;
  }
  if (utils.getCLIVariable('run_average') !== 'false') {
    const avg = config.average;
    total += avg.rps * (avg.duration.rise + avg.duration.plateau + avg.duration.fall);
  }
  if (utils.getCLIVariable('run_stress') !== 'false') {
    const stress = config.stress;
    total += stress.rps * (stress.duration.rise + stress.duration.plateau + stress.duration.fall);
  }
  if (utils.getCLIVariable('run_spike') !== 'false') {
    const spike = config.spike;
    total += (spike.rps * (spike.duration.rise + spike.duration.fall)) / 2;
  }

  return Math.ceil(total * 1.1);
}

export function setup() {
  const totalNeeded = calculateTotalNeeded();
  console.log(`Creating ${totalNeeded} customers for GraphQL deletion test`);

  // Create customers to delete
  const customerTypeData = { value: `GraphQLDeleteType_${Date.now()}` };
  const typeResponse = utils.createCustomerType(customerTypeData);

  const customerStatusData = { value: `GraphQLDeleteStatus_${Date.now()}` };
  const statusResponse = utils.createCustomerStatus(customerStatusData);

  const customersToDelete = [];

  if (typeResponse.status === 201 && statusResponse.status === 201) {
    const type = JSON.parse(typeResponse.body);
    const status = JSON.parse(statusResponse.body);

    // Create exact number of customers needed
    for (let i = 0; i < totalNeeded; i++) {
      const customerData = {
        initials: `GraphQL Delete Customer ${i}`,
        email: `graphql_delete_${i}_${Date.now()}@example.com`,
        phone: `+1-555-666${i}`,
        leadSource: 'GraphQL Load Test',
        confirmed: true,
        type: type['@id'],
        status: status['@id'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      };

      const response = utils.createCustomer(customerData);

      if (response.status === 201) {
        const customer = JSON.parse(response.body);
        customersToDelete.push(customer);
      }
    }

    return {
      customers: customersToDelete,
      typeIri: type['@id'],
      statusIri: status['@id'],
    };
  }

  return { customers: [] };
}

export default function deleteCustomer(data) {
  const customer = data.customers[counter.up() % data.customers.length];
  utils.checkCustomerIsDefined(customer);

  const customerId = customer['@id'];

  const mutation = `
    mutation {
      deleteCustomer(
        input: {
          id: "${customerId}"
        }
      ) {
        customer {
          id
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'customer deleted', res => {
    const body = JSON.parse(res.body);
    return body.data && body.data.deleteCustomer && body.data.deleteCustomer.customer;
  });
}

export function teardown(data) {
  // Clean up type and status (customers should be deleted during test)
  if (data) {
    // Clean up type and status
    if (data.typeIri) {
      try {
        http.del(`http://localhost:80${data.typeIri}`);
      } catch (e) {
        // Ignore cleanup errors
      }
    }

    if (data.statusIri) {
      try {
        http.del(`http://localhost:80${data.statusIri}`);
      } catch (e) {
        // Ignore cleanup errors
      }
    }

    console.log(
      `Deleted ${data.customers ? data.customers.length : 0} customers during GraphQL load test`
    );
  }
}
