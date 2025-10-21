import InsertCustomerStatusesUtils from './insertCustomerStatusesUtils.js';
import Utils from './utils.js';
import file from 'k6/x/file';

const utils = new Utils();
const filepath =
  utils.getConfig()['customerStatusesFileLocation'] + utils.getConfig()['customerStatusesFileName'];
const scenarioName = utils.getCLIVariable('scenarioName');
const insertStatusesUtils = new InsertCustomerStatusesUtils(utils, scenarioName);

export function setup() {
  console.log(`Preparing customer statuses for scenario: ${scenarioName}`);
  console.log(`Calculating required statuses...`);

  const totalNeeded = insertStatusesUtils.countTotalRequest();
  console.log(`Total statuses needed: ${totalNeeded}`);

  const statuses = insertStatusesUtils.prepareStatuses();
  console.log(`✓ Created ${statuses.length} customer statuses`);

  try {
    file.writeString(filepath, JSON.stringify(statuses));
    console.log(`✓ Saved statuses to ${filepath}`);
  } catch (error) {
    console.error(`✗ Error occurred while writing statuses to ${filepath}`);
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
