import http from 'k6/http';

export default class InsertCustomerTypesUtils {
  constructor(utils, scenarioName) {
    this.utils = utils;
    this.config = utils.getConfig();
    this.scenarioName = scenarioName;
    this.additionalTypesRatio = 1.1;
    this.smokeConfig = this.config.endpoints[scenarioName].smoke;
    this.averageConfig = this.config.endpoints[scenarioName].average;
    this.stressConfig = this.config.endpoints[scenarioName].stress;
    this.spikeConfig = this.config.endpoints[scenarioName].spike;
  }

  loadInsertedTypes() {
    return JSON.parse(open(`../${this.utils.getConfig()['customerTypesFileName']}`));
  }

  createCustomerTypes(numberOfTypes) {
    const types = [];

    for (let i = 0; i < numberOfTypes; i++) {
      const typeData = {
        value: `DeleteTestType_${i}_${Date.now()}`,
      };

      const response = this.utils.createCustomerType(typeData);

      if (response.status === 201) {
        const type = JSON.parse(response.body);
        types.push(type);
      }
    }

    return types;
  }

  countRequestForRampingRate(startRps, targetRps, duration) {
    const acceleration = (targetRps - startRps) / duration;
    return Math.round(startRps * duration + (acceleration * duration * duration) / 2);
  }

  prepareTypes() {
    return this.createCustomerTypes(this.countTotalRequest());
  }

  countTotalRequest() {
    const requestsMap = {
      run_smoke: this.countSmokeRequest.bind(this),
      run_average: this.countAverageRequest.bind(this),
      run_stress: this.countStressRequest.bind(this),
      run_spike: this.countSpikeRequest.bind(this),
    };

    let totalRequests = 0;

    for (const key in requestsMap) {
      if (this.utils.getCLIVariable(key) !== 'false') {
        totalRequests += requestsMap[key]();
      }
    }

    return Math.round(totalRequests * this.additionalTypesRatio);
  }

  countSmokeRequest() {
    return this.smokeConfig.rps * this.smokeConfig.duration;
  }

  countAverageRequest() {
    return this.countDefaultRequests(this.averageConfig);
  }

  countStressRequest() {
    return this.countDefaultRequests(this.stressConfig);
  }

  countDefaultRequests(config) {
    const riseRequests = this.countRequestForRampingRate(0, config.rps, config.duration.rise);
    const plateauRequests = config.rps * config.duration.plateau;
    const fallRequests = this.countRequestForRampingRate(config.rps, 0, config.duration.fall);

    return riseRequests + plateauRequests + fallRequests;
  }

  countSpikeRequest() {
    const spikeRiseRequests = this.countRequestForRampingRate(
      0,
      this.spikeConfig.rps,
      this.spikeConfig.duration.rise
    );

    const spikeFallRequests = this.countRequestForRampingRate(
      this.spikeConfig.rps,
      0,
      this.spikeConfig.duration.fall
    );

    return spikeRiseRequests + spikeFallRequests;
  }
}
