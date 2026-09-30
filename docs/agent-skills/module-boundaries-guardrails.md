# module-boundaries-guardrails

## Goal
Enforce the layout described in `architecture.md`. Pure checklist, no prose.

## When to use
Before implementation and on every diff.

## Checklist
- [ ] Every new/moved file sits in the correct namespace (`Api` / `Domain` / `Application` / `Infrastructure` / `Presentation`).
- [ ] No cross-module `use` outside `Modules\<Other>\Api\...`.
- [ ] Cross-module `Api\` imports live **only** in the consuming module's `Infrastructure/` (ACL adapter) or in a `Listeners/` translating an `Api\Events\*` payload. `Application/` and `Domain/` have zero imports from other modules. See [ADR 0003](../adr/0003-ddd-purity-refactor.md).
- [ ] `Domain/` has **no** imports from `Infrastructure`, `Presentation`, `Illuminate\*`, `Eloquent`, or HTTP contracts (enums / plain interfaces / value objects / domain events only). A single, deliberate exception is documented in [ADR 0003 §6](../adr/0003-ddd-purity-refactor.md): `Ramsey\Uuid\Uuid::isValid()` is used inside `InvoiceId` for canonical UUID format validation.
- [ ] `Application/` depends only on ports it declares itself, not on framework contracts.
- [ ] No controller talks to Eloquent directly — it calls an `Application` service or the module `Api`.
- [ ] Outbound side effects (mail, HTTP, DB, queue, cross-module facades) go through ports declared in `Application/Ports/` and implemented in `Infrastructure/`.
- [ ] Business rules and state transitions live in `Domain`, not in controllers, listeners, or Eloquent models.
- [ ] Domain events are recorded on aggregates (`pullRecordedEvents()`) and dispatched by the Application layer via a port; framework event-bus wiring is confined to `Infrastructure/` and a `ServiceProvider`.
- [ ] Listeners for another module's events live in the *consuming* module's `Infrastructure`/`Application` and consume only `Api\Events\*`.
- [ ] Generated OpenAPI DTOs live under `Presentation\Http\Generated\` and are **not** referenced from `Domain\`, `Application\`, `Infrastructure\`, or any other module. Only `Presentation\Http\Controllers\` and `Presentation\Http\Assemblers\` may import them. See [ADR 0001](../adr/0001-openapi-strategy.md).

## Output
Pass/fail per item; concrete file references for failures.
