import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'apiDocs';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function apiDocs() {
  const response = http.get(`${utils.getBaseDomain()}/api/docs`);
  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}
