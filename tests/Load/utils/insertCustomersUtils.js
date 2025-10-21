import http from 'k6/http';

export default class InsertCustomersUtils {
  constructor(utils, scenarioName) {
    this.utils = utils;
    this.config = utils.getConfig();
    this.scenarioName = scenarioName;
    this.additionalCustomersRatio = 1.1;
    this.smokeConfig = this.config.endpoints[scenarioName].smoke;
    this.averageConfig = this.config.endpoints[scenarioName].average;
    this.stressConfig = this.config.endpoints[scenarioName].stress;
    this.spikeConfig = this.config.endpoints[scenarioName].spike;
  }

  loadInsertedCustomers() {
    return JSON.parse(open(`../${this.utils.getConfig()['customersFileName']}`));
  }

  *customersGenerator(numberOfCustomers, types, statuses) {
    for (let i = 0; i < numberOfCustomers; i++) {
      const customer = this.utils.generateCustomer(types, statuses);
      yield customer;
    }
  }

  prepareCustomerBatch(batchSize, types, statuses) {
    const generator = this.customersGenerator(batchSize, types, statuses);
    const batch = [];

    for (let requestIndex = 0; requestIndex < batchSize; requestIndex++) {
      const customer = generator.next().value;
      batch.push(customer);
    }

    return batch;
  }

  *requestGenerator(numberOfRequest, batchSize, types, statuses) {
    for (let i = 0; i < numberOfRequest; i++) {
      const batch = this.prepareCustomerBatch(batchSize, types, statuses);

      const payload = JSON.stringify({
        customers: batch,
      });

      const request = {
        method: 'POST',
        url: `${this.utils.getBaseHttpUrl()}/customers/batch`,
        body: payload,
        params: this.utils.getJsonHeader(),
      };

      yield request;
    }
  }

  prepareRequestBatch(numberOfCustomers, batchSize, types, statuses) {
    const numberOfRequests = Math.ceil(numberOfCustomers / batchSize);
    const generator = this.requestGenerator(numberOfRequests, batchSize, types, statuses);
    const requestBatch = [];

    for (let requestIndex = 0; requestIndex < numberOfRequests; requestIndex++) {
      const { value, done } = generator.next();
      if (done) break;
      requestBatch.push(value);
    }

    return requestBatch;
  }

  insertCustomers(numberOfCustomers, types, statuses) {
    const batchSize = Math.min(this.config.batchSize || 50, numberOfCustomers);
    const customers = [];

    const requestBatch = this.prepareRequestBatch(numberOfCustomers, batchSize, types, statuses);

    try {
      const responses = http.batch(requestBatch);
      responses.forEach(response => {
        if (response.status === 201) {
          const responseData = JSON.parse(response.body);
          // Handle both single customer and array responses
          const customerData = Array.isArray(responseData) ? responseData : [responseData];
          customerData.forEach(customer => {
            customers.push(customer);
          });
        }
      });
    } catch (error) {
      throw new Error(
        'Error occurred during customer insertion, try to lower batchSize in a config file'
      );
    }

    return customers;
  }

  insertCustomerTypes() {
    const types = [
      { value: 'Premium' },
      { value: 'Standard' },
      { value: 'Enterprise' },
      { value: 'Basic' },
      { value: 'VIP' },
    ];

    const createdTypes = [];
    for (const typeData of types) {
      const response = this.utils.createCustomerType(typeData);
      if (response.status === 201) {
        createdTypes.push(JSON.parse(response.body));
      }
    }

    return createdTypes;
  }

  insertCustomerStatuses() {
    const statuses = [
      { value: 'Active' },
      { value: 'Inactive' },
      { value: 'Pending' },
      { value: 'Suspended' },
      { value: 'Archived' },
    ];

    const createdStatuses = [];
    for (const statusData of statuses) {
      const response = this.utils.createCustomerStatus(statusData);
      if (response.status === 201) {
        createdStatuses.push(JSON.parse(response.body));
      }
    }

    return createdStatuses;
  }

  countRequestForRampingRate(startRps, targetRps, duration) {
    const acceleration = (targetRps - startRps) / duration;
    return Math.round(startRps * duration + (acceleration * duration * duration) / 2);
  }

  prepareCustomers() {
    const types = this.insertCustomerTypes();
    const statuses = this.insertCustomerStatuses();
    const totalNeeded = this.countTotalRequest();

    return this.insertCustomers(totalNeeded, types, statuses);
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

    return Math.round(totalRequests * this.additionalCustomersRatio);
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
