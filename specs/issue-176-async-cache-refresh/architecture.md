# Architecture: Abstract Async Endpoint Cache Refresh

## Current Architecture Fit

Issue #176 should introduce a reusable cache-refresh foundation, not a Customer-only worker design. Customer is the first adopter because it already has cached repository reads and domain-event cache invalidation subscribers, but the refresh command, queue, worker, metrics, and abstract orchestration must be reusable by future bounded contexts.

The current repository already has the right primitives:

- Any domain event can flow through `Shared/Infrastructure/Bus/Event/Async/DomainEventEnvelope`.
- `Shared/Infrastructure/Bus/Event/Async/DomainEventMessageHandler` already reads domain events from the async event transport and invokes tagged subscribers.
- CQRS command objects and handlers already live in `Application/Command` and `Application/CommandHandler`.
- `Shared/Application/Command` already exists, and deptrac collects `Application/Command` and `Application/CommandHandler`.
- Shared metrics already live in `Shared/Application/Observability/Metric`.
- Shared cache utilities already live in `Shared/Infrastructure/Cache`.
- Context-specific cache behavior already appears as repository decorators, collections, resolvers, factories, and event subscribers.

The implementation should add shared abstractions and thin context adapters. It should not add Customer-only queue contracts that future domains would need to copy.

## Directory Rules

This plan follows the repository rule: one directory contains one class type.

- Put the reusable refresh command in `src/Shared/Application/Command`.
- Put reusable refresh command handlers and abstract handler bases in `src/Shared/Application/CommandHandler`.
- Put reusable DTOs, resolver interfaces, event-subscriber bases, factories, and metrics in their matching class-type directories.
- Put reusable metrics under `src/Shared/Application/Observability/Metric`, matching the existing shared observability structure.
- Put reusable infrastructure collaborators in existing or deptrac-collected class-type directories such as `src/Shared/Infrastructure/{Cache,Collection,Resolver}`.
- Put bounded-context adapters in existing context directories such as `src/Core/Customer/Application/{CommandHandler,EventSubscriber,Factory}` and `src/Core/Customer/Infrastructure/{Collection,Repository,Resolver}`.
- Do not introduce new context directories named `Cache`, `ReadModel`, `Policy`, `Registry`, `Scheduler`, `Message`, or `MessageHandler`.
- Do not create a Customer `Infrastructure/Cache` directory. Cache refresh is part of the existing repository, collection, resolver, subscriber, factory, command, and handler surface.

## Architecture Diagram

```mermaid
flowchart LR
    subgraph Domain["Any bounded-context Domain layer"]
        DomainEvent["DomainEvent\nCustomerCreatedEvent\nFutureContextEvent"]
    end

    subgraph DomainEventWorker["Shared async domain-event path"]
        EventEnvelope["DomainEventEnvelope"]
        EventQueue["domain-events SQS transport\nLocalStack locally"]
        EventWorker["DomainEventMessageHandler\nreads any domain event"]
    end

    subgraph SharedRefresh["Shared cache-refresh foundation"]
        AbstractSubscriber["AbstractCacheInvalidationSubscriber"]
        AbstractFactory["AbstractCacheRefreshCommandFactory"]
        RefreshCommand["CacheRefreshCommand\nscalar reusable payload"]
        RefreshWorker["CacheRefreshCommandHandler\nsingle shared worker"]
        AbstractHandler["AbstractCacheRefreshCommandHandler"]
        PolicyDto["CacheRefreshPolicy DTO"]
        TargetDto["CacheRefreshTarget DTO"]
        ResultDto["CacheRefreshResult DTO"]
        HandlerResolver["CacheRefreshCommandHandlerResolver"]
        PolicyResolver["CacheRefreshPolicyResolver"]
        TargetResolver["CacheRefreshTargetResolverCollection"]
        KeyBuilder["CacheKeyBuilder"]
        Metrics["Shared cache-refresh metrics"]
    end

    subgraph CustomerAdapter["Customer first adopter"]
        CustomerSubscriber["Customer cache invalidation subscribers"]
        CustomerFactory["CustomerCacheRefreshCommandFactory"]
        CustomerHandler["CustomerCacheRefreshCommandHandler\nregistered adapter"]
        CustomerTargetResolver["CustomerCacheRefreshTargetResolver"]
        CustomerPolicyCollection["CustomerCachePolicyCollection"]
        CachedCustomerRepository["CachedCustomerRepository"]
        CustomerRepository["MongoCustomerRepository"]
    end

    subgraph FutureAdapter["Future bounded-context adopter"]
        FutureSubscriber["Future context subscriber"]
        FutureFactory["Future context command factory"]
        FutureHandler["Future context refresh handler"]
        FutureRepository["Future cached repository"]
    end

    subgraph RefreshTransport["Shared cache-refresh queue"]
        RefreshQueue["cache-refresh SQS transport\nLocalStack locally"]
        FailedQueue["failed-cache-refresh transport"]
        MessengerWorkers["Symfony Messenger workers"]
    end

    subgraph Runtime["Cache runtime"]
        CachePools["Redis tag-aware cache pools\ncontext + family scoped"]
    end

    subgraph Observability["Shared observability"]
        Emitter["BusinessMetricsEmitterInterface"]
    end

    WriteApi["Any write API\ncommand handlers"] --> DomainEvent
    DomainEvent --> EventEnvelope
    EventEnvelope --> EventQueue
    EventQueue --> EventWorker

    EventWorker --> CustomerSubscriber
    EventWorker --> FutureSubscriber

    CustomerSubscriber --> AbstractSubscriber
    FutureSubscriber --> AbstractSubscriber
    AbstractSubscriber --> CachePools
    AbstractSubscriber --> TargetResolver
    CustomerTargetResolver --> TargetResolver
    AbstractSubscriber --> AbstractFactory
    CustomerFactory --> AbstractFactory
    FutureFactory --> AbstractFactory
    AbstractFactory --> RefreshCommand
    RefreshCommand --> RefreshQueue

    RefreshQueue --> MessengerWorkers
    MessengerWorkers --> RefreshWorker
    RefreshWorker --> HandlerResolver
    HandlerResolver --> CustomerHandler
    HandlerResolver --> FutureHandler
    CustomerHandler --> AbstractHandler
    FutureHandler --> AbstractHandler
    AbstractHandler --> PolicyResolver
    PolicyResolver --> CustomerPolicyCollection
    AbstractHandler --> TargetResolver
    TargetResolver --> TargetDto
    AbstractHandler --> PolicyDto
    AbstractHandler --> KeyBuilder
    CustomerHandler --> CustomerRepository
    CustomerHandler --> CachePools
    AbstractHandler --> ResultDto
    RefreshWorker --> FailedQueue

    ReadApi["Customer read API"] --> CachedCustomerRepository
    CachedCustomerRepository --> PolicyResolver
    CachedCustomerRepository --> KeyBuilder
    CachedCustomerRepository --> CachePools
    CachedCustomerRepository --> CustomerRepository
    FutureReadApi["Future read API"] --> FutureRepository

    AbstractSubscriber --> Metrics
    RefreshWorker --> Metrics
    AbstractHandler --> Metrics
    CachedCustomerRepository --> Metrics
    Metrics --> Emitter
```

## New Source Tree

The implementation PR should add reusable shared classes first, then add Customer as the first adapter. The tree below shows new files and existing files that should be edited.

```text
src/
  Shared/
    Application/
      Command/
        CacheRefreshCommand.php
      CommandHandler/
        AbstractCacheRefreshCommandHandler.php
        CacheRefreshCommandHandler.php
      DTO/
        CacheRefreshPolicy.php
        CacheRefreshResult.php
        CacheRefreshTarget.php
      EventSubscriber/
        AbstractCacheInvalidationSubscriber.php
      Factory/
        AbstractCacheRefreshCommandFactory.php
      Observability/
        Metric/
          CacheHitMetric.php
          CacheMissMetric.php
          CacheRefreshFailedMetric.php
          CacheRefreshScheduledMetric.php
          CacheRefreshStaleServedMetric.php
          CacheRefreshSucceededMetric.php
          ValueObject/
            CacheRefreshMetricDimensions.php
      Resolver/
        CacheRefreshCommandHandlerResolverInterface.php
        CacheRefreshPolicyResolverInterface.php
        CacheRefreshTargetResolverInterface.php
    Infrastructure/
      Cache/
        CacheKeyBuilder.php (existing, edit for generic context/family key helpers)
      Collection/
        CacheRefreshCommandHandlerCollection.php
        CacheRefreshPolicyCollection.php
        CacheRefreshTargetResolverCollection.php
      Resolver/
        CacheRefreshCommandHandlerResolver.php
        CacheRefreshPolicyResolver.php
  Core/
    Customer/
      Application/
        CommandHandler/
          CustomerCacheRefreshCommandHandler.php
        EventSubscriber/
          CustomerCreatedCacheInvalidationSubscriber.php (edit)
          CustomerDeletedCacheInvalidationSubscriber.php (edit)
          CustomerUpdatedCacheInvalidationSubscriber.php (edit)
        Factory/
          CustomerCacheRefreshCommandFactory.php
      Infrastructure/
        Collection/
          CustomerCachePolicyCollection.php
          CustomerCacheTagCollection.php (existing)
        Repository/
          CachedCustomerRepository.php (edit)
        Resolver/
          CustomerCachePolicyResolver.php
          CustomerCacheRefreshTargetResolver.php
          CustomerCacheTagResolver.php (existing)
```

Planned test tree:

```text
tests/
  Unit/
    Shared/
      Application/
        Command/
          CacheRefreshCommandTest.php
        CommandHandler/
          AbstractCacheRefreshCommandHandlerTest.php
          CacheRefreshCommandHandlerTest.php
        DTO/
          CacheRefreshPolicyTest.php
          CacheRefreshResultTest.php
          CacheRefreshTargetTest.php
        EventSubscriber/
          AbstractCacheInvalidationSubscriberTest.php
        Factory/
          AbstractCacheRefreshCommandFactoryTest.php
        Observability/
          Metric/
            CacheRefreshMetricTest.php
            ValueObject/
              CacheRefreshMetricDimensionsTest.php
        Resolver/
          CacheRefreshCommandHandlerResolverInterfaceTest.php
          CacheRefreshPolicyResolverInterfaceTest.php
          CacheRefreshTargetResolverInterfaceTest.php
      Infrastructure/
        Collection/
          CacheRefreshCommandHandlerCollectionTest.php
          CacheRefreshPolicyCollectionTest.php
          CacheRefreshTargetResolverCollectionTest.php
        Resolver/
          CacheRefreshCommandHandlerResolverTest.php
          CacheRefreshPolicyResolverTest.php
    Customer/
      Application/
        CommandHandler/
          CustomerCacheRefreshCommandHandlerTest.php
        EventSubscriber/
          CustomerCreatedCacheInvalidationSubscriberTest.php
          CustomerDeletedCacheInvalidationSubscriberTest.php
          CustomerUpdatedCacheInvalidationSubscriberTest.php
        Factory/
          CustomerCacheRefreshCommandFactoryTest.php
      Infrastructure/
        Collection/
          CustomerCachePolicyCollectionTest.php
        Repository/
          CachedCustomerRepositoryPolicyTest.php
        Resolver/
          CustomerCachePolicyResolverTest.php
          CustomerCacheRefreshTargetResolverTest.php
  Integration/
    Customer/
      Infrastructure/
        Repository/
          AsyncCustomerCacheRefreshTest.php
```

Configuration and documentation expected to change in the later implementation PR:

```text
config/
  packages/
    cache.yaml
    messenger.yaml
  packages/test/
    cache.yaml
    messenger.yaml (new, only if test routing cannot stay in messenger.yaml)
  services.yaml
.env
.env.test
docs/
  advanced-configuration.md
  design-and-architecture.md
  operational.md
  performance.md
```

## Shared Components

### Generic Refresh Command

`CacheRefreshCommand` is the single queue payload for all bounded contexts. It should carry scalar, serialization-stable data only:

- context name, such as `customer`
- cache family, such as `detail` or `lookup`
- target identifiers as a string map
- triggering domain event name and event ID
- occurred-at timestamp
- refresh strategy
- attempt metadata where Messenger retry handling needs it

The command must not contain Customer-specific fields such as `customerEmail`. Customer-specific meaning belongs in `CustomerCacheRefreshCommandFactory` and `CustomerCacheRefreshTargetResolver`.

### Abstract Subscriber Contract

`AbstractCacheInvalidationSubscriber` should handle the common sequence:

1. Resolve affected cache targets from the domain event.
2. Invalidate tags immediately.
3. Create one or more `CacheRefreshCommand` instances.
4. Dispatch refresh commands best-effort.
5. Emit scheduled or failed metrics without breaking domain-event processing.

Concrete subscribers should only map a domain event to context-specific tags and targets.

### Shared Worker Contract

`CacheRefreshCommandHandler` is the single Messenger worker entrypoint for the `cache-refresh` queue. It should:

1. Resolve a registered context command handler by context and family.
2. Delegate refresh execution to that context handler.
3. Emit common success or failure metrics.
4. Let Messenger route unrecoverable job failures to `failed-cache-refresh` according to configured retry strategy.

`AbstractCacheRefreshCommandHandler` should hold the reusable refresh template for context handlers:

1. Resolve the cache policy.
2. Resolve the concrete target.
3. Build cache keys through `CacheKeyBuilder`.
4. Refresh the cache through a context repository callback.
5. Return a `CacheRefreshResult`.

Concrete handlers, such as `CustomerCacheRefreshCommandHandler`, should only provide context-specific target loading, such as customer detail by ID or customer lookup by email.

### Policy and Target Resolution

`CacheRefreshPolicy` belongs in `Shared/Application/DTO` because it is data passed across application orchestration. It should contain:

- context
- family
- key namespace
- tags
- soft TTL
- hard TTL
- jitter
- consistency class
- refresh strategy

`CacheRefreshPolicyCollection` belongs in `Shared/Infrastructure/Collection` as the generic iterable policy holder. `CustomerCachePolicyCollection` may compose or configure customer policies in the Customer context, but policy lookup should be performed through the shared resolver interface.

`CacheRefreshTarget` belongs in `Shared/Application/DTO`. It should describe what must be refreshed without depending on Customer classes.

## Queue and Worker Model

Use two worker paths:

- Existing `domain-events` queue: reads any domain event through `DomainEventMessageHandler`.
- New `cache-refresh` queue: reads the generic `CacheRefreshCommand` through `CacheRefreshCommandHandler`.

The implementation should add a single shared cache-refresh transport:

- `CACHE_REFRESH_QUEUE_NAME`
- `FAILED_CACHE_REFRESH_QUEUE_NAME`
- `CACHE_REFRESH_TRANSPORT_DSN`
- `FAILED_CACHE_REFRESH_TRANSPORT_DSN`
- `cache-refresh` transport
- `failed-cache-refresh` transport

Do not create per-domain cache-refresh queues in the first implementation. A single queue keeps worker operations reusable. If future throughput requires separate queues, that should be an operations-driven follow-up with metrics evidence.

## Customer Adapter

Customer should be the first adapter for the shared design:

- Customer create/update/delete subscribers extend or delegate to `AbstractCacheInvalidationSubscriber`.
- `CustomerCacheRefreshCommandFactory` creates generic `CacheRefreshCommand` instances from Customer events.
- `CustomerCacheRefreshTargetResolver` maps target DTOs to Customer repository lookup inputs.
- `CustomerCachePolicyCollection` declares Customer detail, lookup, collection, reference, and negative lookup policies.
- `CustomerCacheRefreshCommandHandler` extends or composes `AbstractCacheRefreshCommandHandler` and registers for `customer` context families.
- `CachedCustomerRepository` consumes the shared policy resolver and key builder instead of method-local TTL literals.

The first implementation should refresh currently cached same-entity families:

- Customer detail by ID.
- Customer lookup by email.

Collection and reference policies should be declared and immediately invalidated by tags, but arbitrary proactive collection materialization stays out of scope until the codebase has a stable query-shape abstraction.

## Observability

Metric classes should be shared because refresh lifecycle is not Customer-specific:

- `CacheRefreshScheduledMetric`
- `CacheRefreshSucceededMetric`
- `CacheRefreshFailedMetric`
- `CacheHitMetric`
- `CacheMissMetric`
- `CacheRefreshStaleServedMetric`

Place them under `Shared/Application/Observability/Metric`. Use dimensions such as context, family, source event, result, and failure type. Context-specific metrics should only be added when a bounded context needs dimensions that the shared classes cannot represent.

## Failure Semantics

- Cache failures fall back to the inner repository where the current repository already does so.
- Subscriber dispatch failures are logged and measured but do not break domain-event handling.
- Worker refresh failures are logged and measured; retry and failed routing are controlled by Messenger.
- Delete events invalidate and avoid warming deleted entities.
- Domain remains free of Symfony, cache, Messenger, logging, and metrics dependencies.

## Implementation Sequence

1. Add shared DTOs, resolver interfaces, collections, abstract subscriber, abstract factory, generic refresh command, generic worker, abstract context handler, and shared metrics.
2. Add the single `cache-refresh` and `failed-cache-refresh` Messenger transports.
3. Add Customer adapter classes that extend or compose the shared abstractions.
4. Update `CachedCustomerRepository` to use resolved policies and generic key helpers.
5. Update Customer subscribers to invalidate plus schedule refresh work through the shared subscriber path.
6. Add unit and integration tests for shared orchestration and the Customer adapter.
7. Update docs and run cache performance evidence where runtime services allow.

## Architectural Tradeoffs

- This design keeps reusable orchestration in `Shared` and leaves domain-specific mapping in bounded contexts.
- It uses the existing CQRS command/handler names instead of inventing `Request`, `Message`, `MessageHandler`, `Scheduler`, or `Worker` directories.
- It uses `Policy` as a DTO class name, not as a new directory type.
- It avoids `ReadModel` because the current project read paths are repository and resolver based, and issue #176 is about refreshing endpoint cache entries rather than introducing a separate projection model.
