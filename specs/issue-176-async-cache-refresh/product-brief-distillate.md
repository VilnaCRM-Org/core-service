# Product Brief Distillate

Implement issue #176 as a reusable cache consistency platform:

- shared cache-refresh DTOs, abstract subscribers, abstract commands, abstract handlers, factories, resolvers, collections, and metrics
- one shared `cache-refresh` SQS worker path for any bounded context, plus `failed-cache-refresh`
- existing domain-event workers continue to read any domain event and invoke context subscribers
- Customer becomes the first adapter, not the only design target
- Customer detail and email lookup get proactive async refresh first
- collection and reference policies are declared and tag-invalidated, with proactive arbitrary collection warmup deferred until a deterministic query abstraction exists
- no new context `Cache`, `ReadModel`, `Policy`, `Registry`, `Scheduler`, `Message`, or `MessageHandler` directories
- tests, docs, and cache performance evidence complete the implementation PR
