# Product Brief Distillate

Implement issue #176 as a narrowly scoped cache consistency upgrade:

- central typed cache policy DTO/factory/collection/resolver classes
- split customer cache pools by query family
- async cache refresh command/handler routed through SQS in non-test envs, with LocalStack for local/test
- event subscribers invalidate tags and enqueue refresh work
- typed EMF metrics for refresh/read cache lifecycle
- tests and cache performance evidence

Primary delivery target: currently cached customer detail and email lookup families. Declare collection and reference policies now, but avoid speculative arbitrary collection warmup unless a deterministic query abstraction exists.
