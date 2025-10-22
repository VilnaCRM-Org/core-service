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

  *requestGenerator(numberOfCustomers, types, statuses) {
    for (let i = 0; i < numberOfCustomers; i++) {
      const customerData = this.utils.generateCustomer(types, statuses);
      const payload = JSON.stringify(customerData);

      const request = {
        method: 'POST',
        url: this.utils.getBaseHttpUrl(),
        body: payload,
        params: this.utils.getJsonHeader(),
      };

      yield request;
    }
  }

  prepareRequestBatch(numberOfCustomers, batchSize, types, statuses) {
    const generator = this.requestGenerator(numberOfCustomers, types, statuses);
    const requestBatch = [];

    for (let i = 0; i < numberOfCustomers; i++) {
      const { value, done } = generator.next();
      if (done) break;
      requestBatch.push(value);
    }

    return requestBatch;
  }

  insertCustomers(numberOfCustomers, types, statuses) {
    const batchSize = Math.min(this.config.batchSize, numberOfCustomers);
    const customers = [];

    // Process customers in batches to avoid overwhelming the API
    for (let offset = 0; offset < numberOfCustomers; offset += batchSize) {
      const currentBatchSize = Math.min(batchSize, numberOfCustomers - offset);
      const requestBatch = this.prepareRequestBatch(currentBatchSize, batchSize, types, statuses);

      const responses = http.batch(requestBatch);

      responses.forEach(response => {
        if (response.status === 201) {
          try {
            const customer = JSON.parse(response.body);
            // Extract ID from @id IRI (e.g., /api/customers/01K85E6755EFKTKPFMK6WHF99V)
            if (customer['@id']) {
              customer.id = customer['@id'].split('/').pop();
            }
            customers.push(customer);
          } catch (error) {
            console.error(`Failed to parse customer response: ${error.message}`);
          }
        } else {
          console.error(`Failed to create customer. Status: ${response.status}, Body: ${response.body}`);
        }
      });

      // Log progress
      console.log(`Created ${customers.length}/${numberOfCustomers} customers`);
    }

    if (customers.length === 0) {
      throw new Error(
        'Failed to create any customers. Check API endpoint and ensure the service is running.'
      );
    }

    return customers;
  }

  insertCustomerTypes() {
    // Try to fetch existing types first
    const getResponse = this.utils.getCustomerTypes();
    if (getResponse.status === 200) {
      const body = JSON.parse(getResponse.body);
      // API Platform returns hydra:member array
      const existingTypes = body['hydra:member'] || body;
      if (existingTypes && existingTypes.length > 0) {
        return existingTypes;
      }
    }

    // If no existing types, create them
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
    // Try to fetch existing statuses first
    const getResponse = this.utils.getCustomerStatuses();
    if (getResponse.status === 200) {
      const body = JSON.parse(getResponse.body);
      // API Platform returns hydra:member array
      const existingStatuses = body['hydra:member'] || body;
      if (existingStatuses && existingStatuses.length > 0) {
        return existingStatuses;
      }
    }

    // If no existing statuses, create them
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
    return this.insertCustomers(this.countTotalRequest(), types, statuses);
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
