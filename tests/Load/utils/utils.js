import { check } from 'k6';
import http from 'k6/http';

export default class Utils {
  constructor() {
    const host = this.getConfig().apiHost;
    const port = this.getConfig().apiPort;

    this.baseDomain = `http://${host}:${port}`;
    this.baseUrl = `${this.baseDomain}/api`;
    this.baseHttpUrl = this.baseUrl + '/customers';
    this.baseGraphQLUrl = this.baseUrl + '/graphql';
    this.graphQLIdPrefix = '/api/customers/';
  }

  getConfig() {
    try {
      return JSON.parse(open('../config.json'));
    } catch (error) {
      try {
        return JSON.parse(open('../config.json.dist'));
      } catch (error) {
        console.log('Error occurred while trying to open config');
      }
    }
  }

  getBaseDomain() {
    return this.baseDomain;
  }

  getBaseUrl() {
    return this.baseUrl;
  }

  getBaseHttpUrl() {
    return this.baseHttpUrl;
  }

  getBaseGraphQLUrl() {
    return this.baseGraphQLUrl;
  }

  getGraphQLIdPrefix() {
    return this.graphQLIdPrefix;
  }

  getJsonHeader() {
    return {
      headers: {
        'Content-Type': 'application/ld+json',
      },
    };
  }

  getGraphQLHeader() {
    return {
      headers: {
        'Content-Type': 'application/json',
      },
    };
  }

  getMergePatchHeader() {
    return {
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    };
  }

  getRandomNumber(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  }

  getCLIVariable(variable) {
    return `${__ENV[variable]}`;
  }

  checkCustomerIsDefined(customer) {
    check(customer, { 'customer is defined': c => c !== undefined });
  }

  generateCustomer(types, statuses) {
    // Generate unique email using multiple entropy sources
    const email = this.generateUniqueEmail();

    const firstNames = ['John', 'Jane', 'Mike', 'Sarah', 'David', 'Lisa', 'Robert', 'Emily'];
    const lastNames = [
      'Smith',
      'Johnson',
      'Williams',
      'Brown',
      'Jones',
      'Garcia',
      'Miller',
      'Davis',
    ];
    const firstName = firstNames[Math.floor(Math.random() * firstNames.length)];
    const lastName = lastNames[Math.floor(Math.random() * lastNames.length)];
    const initials = `${firstName} ${lastName}`;

    const leadSources = ['Website', 'Referral', 'Social Media', 'Email Campaign'];
    const leadSource = leadSources[Math.floor(Math.random() * leadSources.length)];

    const customerData = {
      initials,
      email,
      phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
      leadSource,
      confirmed: Math.random() > 0.5,
    };

    // Add type and status if available
    if (types && types.length > 0) {
      const randomType = types[Math.floor(Math.random() * types.length)];
      customerData.type = randomType['@id'];
    }

    if (statuses && statuses.length > 0) {
      const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
      customerData.status = randomStatus['@id'];
    }

    return customerData;
  }

  generateUniqueEmail() {
    // Get K6 runtime information for uniqueness
    const vuId = typeof __VU !== 'undefined' ? __VU : 1;
    const iteration = typeof __ITER !== 'undefined' ? __ITER : 0;

    // High-precision timestamp components
    const timestamp = Date.now();
    const microseconds =
      typeof performance !== 'undefined' && performance.now
        ? performance.now().toString().replace('.', '').substring(0, 8)
        : Math.random().toString().replace('.', '').substring(0, 8);

    // Additional entropy sources
    const randomString1 = Math.random().toString(36).substring(2, 10); // 8 chars
    const randomString2 = Math.random().toString(36).substring(2, 8); // 6 chars
    const processEntropy =
      typeof process !== 'undefined' && process.hrtime
        ? process.hrtime()[1].toString().substring(0, 6)
        : Math.floor(Math.random() * 1000000)
            .toString()
            .padStart(6, '0');

    // Domain selection
    const domains = ['example.com', 'test.org', 'demo.net'];
    const domain = domains[Math.floor(Math.random() * domains.length)];

    // Construct unique email with multiple entropy sources
    const uniqueId = `${vuId}_${iteration}_${timestamp}_${microseconds}_${randomString1}_${randomString2}_${processEntropy}`;

    return `customer_${uniqueId}@${domain}`;
  }

  checkResponse(response, checkName, checkFunction) {
    check(response, { [checkName]: res => checkFunction(res) });
  }

  createCustomer(customerData) {
    const payload = JSON.stringify(customerData);
    return http.post(this.getBaseHttpUrl(), payload, this.getJsonHeader());
  }

  createCustomerType(typeData) {
    const payload = JSON.stringify(typeData);
    return http.post(`${this.baseUrl}/customer_types`, payload, this.getJsonHeader());
  }

  createCustomerStatus(statusData) {
    const payload = JSON.stringify(statusData);
    return http.post(`${this.baseUrl}/customer_statuses`, payload, this.getJsonHeader());
  }

  getCustomerTypes() {
    return http.get(`${this.baseUrl}/customer_types`, this.getJsonHeader());
  }

  getCustomerStatuses() {
    return http.get(`${this.baseUrl}/customer_statuses`, this.getJsonHeader());
  }

  executeGraphQL(query) {
    const payload = JSON.stringify(query);
    return http.post(this.getBaseGraphQLUrl(), payload, this.getGraphQLHeader());
  }

  createDependency(dependencyData) {
    // Generic method to create any dependency resource
    // Can be used for types, statuses, or other dependencies
    const payload = JSON.stringify(dependencyData);
    return http.post(this.getBaseHttpUrl(), payload, this.getJsonHeader());
  }
}
