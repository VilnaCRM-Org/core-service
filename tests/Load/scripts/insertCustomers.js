import InsertCustomersUtils from '../utils/insertCustomersUtils.js';
import Utils from '../utils/utils.js';
import file from 'k6/x/file';

const utils = new Utils();
const filepath = utils.getConfig()['customersFileLocation'] + utils.getConfig()['customersFileName'];
const scenarioName = utils.getCLIVariable('scenarioName');
const insertCustomersUtils = new InsertCustomersUtils(utils, scenarioName);

export function setup() {
  try {
    file.writeString(filepath, JSON.stringify(insertCustomersUtils.prepareCustomers()));
  } catch (error) {
    console.log(`Error occurred while writing customers to ${filepath}`);
  }
}

export const options = {
  setupTimeout: utils.getConfig().endpoints[scenarioName].setupTimeoutInMinutes + 'm',
  stages: [{ duration: '1s', target: 1 }],
  insecureSkipTLSVerify: true,
  batchPerHost: utils.getConfig().batchSize,
};

export default function insertCustomers(data) {}
