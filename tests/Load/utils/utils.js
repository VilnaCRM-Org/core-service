import { check } from 'k6';
import http from 'k6/http';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

export default class Utils {
  constructor() {
    const config = this.getConfig();
    const host = config.apiHost;
    const port = config.apiPort;

    this.baseUrl = `http://${host}:${port}/api`;
    this.baseHttpUrl = this.baseUrl;
    this.baseGraphQLUrl = this.baseUrl + '/graphql';
  }

  getConfig() {
    try {
      return JSON.parse(open('../config.json'));
    } catch (error) {
      try {
        return JSON.parse(open('../config.json.dist'));
      } catch (error) {
        console.error('Failed to load configuration from config.json and config.json.dist:', error);
      }
    }
  }

  getBaseHttpUrl() {
    return this.baseHttpUrl;
  }

  getBaseGraphQLUrl() {
    return this.baseGraphQLUrl;
  }

  getCLIVariable(variable) {
    return `${__ENV[variable]}`;
  }

  checkResponse(response, checkName, checkFunction) {
    check(response, { [checkName]: res => checkFunction(res) });
  }

  checkCustomerIsDefined(customer) {
    if (!customer) {
      throw new Error('Customer is undefined - pool exhausted');
    }
  }

  getJsonHeader() {
    return {
      headers: {
        'Content-Type': 'application/ld+json',
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

  getGraphQLHeader() {
    return {
      headers: {
        'Content-Type': 'application/json',
      },
    };
  }

  createCustomer(customerData) {
    const payload = JSON.stringify(customerData);
    return http.post(`${this.baseHttpUrl}/customers`, payload, this.getJsonHeader());
  }

  createCustomerType(typeData) {
    const payload = JSON.stringify(typeData);
    return http.post(`${this.baseHttpUrl}/customer_types`, payload, this.getJsonHeader());
  }

  createCustomerStatus(statusData) {
    const payload = JSON.stringify(statusData);
    return http.post(`${this.baseHttpUrl}/customer_statuses`, payload, this.getJsonHeader());
  }

  generateCustomer(types, statuses) {
    const domains = ['example.com', 'test.org', 'demo.net', 'sample.co'];
    const leadSources = ['Website', 'Referral', 'Social Media', 'Email Campaign'];
    const name = `Customer_${randomString(8)}`;
    const domain = domains[Math.floor(Math.random() * domains.length)];

    const customerData = {
      initials: name,
      email: `${name.toLowerCase()}@${domain}`,
      phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
      leadSource: leadSources[Math.floor(Math.random() * leadSources.length)],
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
}
