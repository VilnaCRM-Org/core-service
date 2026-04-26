# Product Brief Distillate

Implement issue #176 as a reusable cache consistency platform:

- shared cache-refresh DTOs, abstract subscribers, abstract commands, abstract handlers, factories, resolvers, collections, and metrics
- shared layered invalidation through domain events, an ODM listener, and repository fallback hooks for custom writes that bypass ODM observation
- one idempotent invalidation command/handler used by every invalidation source
- one shared `cache-refresh` SQS worker path for any bounded context, plus `failed-cache-refresh`
- existing domain-event workers continue to read any domain event and invoke context subscribers
- Customer becomes the first adapter, not the only design target
- Customer detail and email lookup get proactive async refresh first through `repository_refresh`
- collection and reference policies are declared and tag-invalidated, with proactive arbitrary collection warmup deferred until a deterministic query abstraction exists
- event-snapshot refresh is deferred until events carry complete, versioned cache payloads
- no cache invalidation methods are added to Domain repository interfaces
- no new context `Cache`, `ReadModel`, `Policy`, `Registry`, `Scheduler`, `Message`, or `MessageHandler` directories
- tests, docs, and cache performance evidence complete the implementation PR
