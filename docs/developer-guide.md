# Developer Guide

Welcome to the developer guide for the Core Service. This guide aims to provide you with all the necessary information to get started with development, including an overview of the code structure.

## Table of Contents

- [Code Structure](#code-structure)
  - [Shared](#shared)
  - [Core/Customer](#corecustomer)
  - [Internal/HealthCheck](#internalhealthcheck)
- [Deptrac](#deptrac)

---

## Code Structure

The Core Service repository is structured to support a modern PHP microservice architecture, utilizing Hexagonal Architecture and DDD principles.

There are 3 bounded contexts in Core Service:

### Shared

The Shared context provides foundational support across the Core Service application. It includes utilities and infrastructure components common to other contexts, ensuring consistency and reducing duplication.

- **Application:** This layer mainly consists of classes, responsible for handling cross-cutting concerns across the application, such as Validators and Transformers. Also, it has an `OpenApi/` folder, which is responsible for building OpenAPI docs for the Core Service, facilitating API discoverability and usability by generating detailed documentation for various API endpoints, request bodies, and response structures.

```bash
Shared/Application
├── Extractor
├── GraphQL
├── OpenApi
│   ├── Applier
│   ├── Builder
│   ├── Cleaner
│   ├── Factory
│   │   ├── Endpoint
│   │   │   ├── Customer
│   │   │   ├── CustomerStatus
│   │   │   └── CustomerType
│   │   ├── Request
│   │   │   ├── Customer
│   │   │   ├── CustomerStatus
│   │   │   └── CustomerType
│   │   ├── Response
│   │   │   ├── Customer
│   │   │   ├── CustomerStatus
│   │   │   └── CustomerType
│   │   └── UriParameter
│   ├── Mapper
│   ├── Processor
│   ├── Resolver
│   ├── Serializer
│   ├── Transformer
│   └── ValueObject
├── Transformer
└── Validator
    └── Guard
```

- **Domain:** This layer mainly consists of interfaces for classes in the Infrastructure layer, and abstract classes to be inherited in other bounded contexts. Also, it has entities, that can not be encapsulated in a specific bounded context.

```bash
Shared/Domain
├── Aggregate
├── Bus
│   ├── Command
│   └── Event
├── Entity
├── Factory
└── ValueObject
```

- **Infrastructure:** This layer mainly consists of services used to support the whole application infrastructure, such as Message Buses and utils for them. Also, some additional tools can be used for configuration, such as Filter strategies for API Platform.

```bash
Shared/Infrastructure
├── Bus
│   ├── Command
│   └── Event
├── Command
├── Controller
├── DoctrineType
├── Factory
├── Filter
├── Transformer
└── Validator
```

### Core/Customer

The Customer context encapsulates all functionality related to customer management within the service. It is comprehensive, covering aspects from customer creation to updates and deletion, as well as customer type and status management.

- **Application:** This layer consists of classes, responsible for handling requests, such as HTTP Request Processors and GraphQL Mutation resolvers, and classes, that encapsulate behavior, such as Command Handlers.

```bash
Core/Customer/Application
├── Builder
├── Command
├── CommandHandler
├── DTO
├── Factory
├── MutationInput
├── Processor
├── Resolver
├── Transformer
└── Transformers
```

- **Domain:** This layer consists of Entities, Value Objects, Domain Exceptions, and Factory interfaces, which represent everything related to business logic in the Customer bounded context.

```bash
Core/Customer/Domain
├── Builder
├── Entity
├── Exception
├── Factory
├── Repository
└── ValueObject
```

- **Infrastructure:** This layer consists of various Repositories for Entities from the Domain layer, implemented using MongoDB.

```bash
Core/Customer/Infrastructure
├── Cache
└── Repository
```

### Internal/HealthCheck

This bounded context handles health monitoring functionality, checking database connectivity, cache availability, and message broker status.

- **Application:** Contains the health check controller and event subscribers for different health checks.

```bash
Internal/HealthCheck/Application
├── Controller
└── EventSub
```

- **Domain:** Contains health check events, value objects, and factory interfaces.

```bash
Internal/HealthCheck/Domain
├── Event
├── Factory
└── ValueObject
```

- **Infrastructure:** Contains factory implementations.

```bash
Internal/HealthCheck/Infrastructure
└── Factory
```

### Deptrac

Deptrac is an architecture static analysis tool designed for PHP projects. It helps maintain the clean architecture of applications by ensuring that layers in the application adhere to predefined rules, preventing unwanted dependencies between them.

In the context of the Core Service, Deptrac is configured to enforce architectural constraints across different parts of the application, ensuring a clean separation of concerns and adherence to the project's architectural design.

[Here](https://github.com/VilnaCRM-Org/core-service/blob/main/deptrac.yaml) you can find our Deptrac config, which will help you to see our code structure comprehensively.

Locally, you can run `make deptrac` command to see the result of the Deptrac execution, and [here](https://github.com/VilnaCRM-Org/core-service/actions) you can find results of last execution in GitHub CI.

Learn more about [Deptrac](https://qossmic.github.io/deptrac/), and how to [configure it](https://qossmic.github.io/deptrac/#configuration).

Learn more about [Operational Documentation](operational.md).
