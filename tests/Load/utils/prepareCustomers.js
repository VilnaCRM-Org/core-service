import InsertCustomersUtils from './insertCustomersUtils.js';
import Utils from './utils.js';
import file from 'k6/x/file';

const utils = new Utils();
const filepath =
  utils.getConfig()['customersFileLocation'] + utils.getConfig()['customersFileName'];
const scenarioName = utils.getCLIVariable('scenarioName');
const insertCustomersUtils = new InsertCustomersUtils(utils, scenarioName);

export function setup() {
  console.log(`Preparing customers for scenario: ${scenarioName}`);
  console.log(`Calculating required customers...`);

  const totalNeeded = insertCustomersUtils.countTotalRequest();
  console.log(`Total customers needed: ${totalNeeded}`);

  const customers = insertCustomersUtils.prepareCustomers();
  console.log(`✓ Created ${customers.length} customers`);

  try {
    file.writeString(filepath, JSON.stringify(customers));
    console.log(`✓ Saved customers to ${filepath}`);
  } catch (error) {
    console.error(`✗ Error occurred while writing customers to ${filepath}`);
    throw error;
  }
}

export const options = {
  setupTimeout: utils.getConfig().endpoints[scenarioName].setupTimeoutInMinutes + 'm',
  stages: [{ duration: '1s', target: 1 }],
  insecureSkipTLSVerify: true,
  batchPerHost: utils.getConfig().batchSize,
};

export default function func(data) {}
