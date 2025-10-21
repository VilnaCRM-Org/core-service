import InsertCustomerTypesUtils from './insertCustomerTypesUtils.js';
import Utils from './utils.js';
import file from 'k6/x/file';

const utils = new Utils();
const filepath = utils.getConfig()['customerTypesFileLocation'] + utils.getConfig()['customerTypesFileName'];
const scenarioName = utils.getCLIVariable('scenarioName');
const insertTypesUtils = new InsertCustomerTypesUtils(utils, scenarioName);

export function setup() {
  console.log(`Preparing customer types for scenario: ${scenarioName}`);
  console.log(`Calculating required types...`);

  const totalNeeded = insertTypesUtils.countTotalRequest();
  console.log(`Total types needed: ${totalNeeded}`);

  const types = insertTypesUtils.prepareTypes();
  console.log(`✓ Created ${types.length} customer types`);

  try {
    file.writeString(filepath, JSON.stringify(types));
    console.log(`✓ Saved types to ${filepath}`);
  } catch (error) {
    console.error(`✗ Error occurred while writing types to ${filepath}`);
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
