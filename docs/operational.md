# Operational Security

This document outlines the operational security practices adopted by our team to ensure the integrity and confidentiality of the data managed by the Core Service. Our commitment to security involves a comprehensive approach, addressing potential vulnerabilities and protecting our customer's information.

## Security Practices

### Sensitive Data Handling

#### Customer Data

Customer data in the Core Service is handled with care to ensure privacy and security. All customer information (initials, email, phone, lead source) is validated and stored securely in MongoDB.

Data validation is performed using the [Symfony Validator Component](https://symfony.com/doc/current/validation.html), which ensures that all input data meets the required criteria before being processed.

You can find our validation configuration in the `src/Core/Customer/Application/DTO/` directory.

#### ULID Identifiers

The Core Service uses ULIDs (Universally Unique Lexicographically Sortable Identifiers) for entity identification. ULIDs are:

- **Non-Sequential:** ULIDs are designed to be non-sequential while maintaining sortability, making it harder to predict or enumerate resource identifiers.
- **Timestamp Component:** The timestamp component of ULIDs helps with database indexing and sorting without exposing sequential IDs.

You can find our ULID factory implementation in the `src/Shared/Domain/Factory/` directory.

#### Database Security

MongoDB is configured with proper authentication and access controls:

- **Authentication:** All database connections require authentication.
- **Environment Variables:** Database credentials are stored in environment variables and never committed to version control.
- **Connection Encryption:** Supports encrypted connections for production environments.

You can find our MongoDB configuration in the `config/packages/doctrine.yaml` file.

### Data Validation

#### Input Validation

All API inputs are validated at multiple levels:

- **API Level:** API Platform validates incoming requests against OpenAPI schemas.
- **Application Level:** Symfony Validator constraints on DTOs.
- **Domain Level:** Value objects with validation in constructors.

#### Custom Validators

The service includes custom validators:

- **UniqueEmail:** Ensures email uniqueness across customers.
- **Initials:** Validates customer initials format.
- **MutationInputValidator:** Validates GraphQL mutation inputs.

### Health Monitoring

The `/api/health` endpoint monitors system health:

- **Database Connectivity:** Verifies MongoDB connection.
- **Cache Availability:** Checks Redis connectivity (if configured).
- **Message Broker:** Validates broker connection for asynchronous messaging.

Health checks use the event subscriber pattern with dedicated subscribers for each component.

Learn more about [Security Documentation](security.md).
