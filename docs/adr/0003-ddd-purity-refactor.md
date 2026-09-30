# ADR 0003 — DDD purity refactor: domain events, ACL notifier, notify-before-transition

- **Status:** Accepted
- **Date:** 2026-09-30
- **Deciders:** maintainer
- **Related backlog:** [019 — DDD purity refactor](../backlog/019-ddd-purity-refactor.md)
- **Related skills:** [`architecture.md`](../agent-skills/architecture.md), [`module-boundaries-guardrails.md`](../agent-skills/module-boundaries-guardrails.md), [`invoice-domain.md`](../agent-skills/invoice-domain.md)
- **See also:** [ADR 0002 — Invoices module structure](./0002-invoices-module-structure.md)

## Context

Task [014](../backlog/014-final-quality-pass.md) landed the send-race guard (pessimistic row lock via `updateLocked`) and the domain-exception polish for value objects. The remaining tactical-DDD gaps flagged in [019](../backlog/019-ddd-purity-refactor.md) were:

1. `Invoice` orchestrated its own transitions but recorded no **domain events**.
2. `SendInvoiceHandler` imported `Modules\Notifications\Api\NotificationFacadeInterface` and `Modules\Notifications\Api\Data\NotifyData` directly — a cross-module leak into `Application/`.
3. `ProductLine` sat under `Domain/Entities/` despite having no identity.
4. `Invoice::ensureCanBeSent()` was public, splitting the send transition into a "query then mutate" API surface.

This ADR records the choices that closed those gaps. The three-endpoint HTTP contract is unchanged and the send flow's observable behaviour (guard-first, notify-second, transition-third, roll back on any failure) is preserved.

## Decisions

### 1. Record domain events on the aggregate; dispatch after commit

`Invoice::send()` records `InvoiceMarkedSending`. `Invoice::markSentToClient()` records `InvoiceSentToClient`. Both events carry the `InvoiceId` and an `occurredAt` `DateTimeImmutable`. Events live under `Modules\Invoices\Domain\Events\` and implement a marker `DomainEvent` interface — pure PHP, no framework contracts.

`Invoice::pullRecordedEvents(): list<DomainEvent>` returns and clears the buffer. The Application layer pulls events *after* the enclosing unit of work commits and hands them to a `DomainEventDispatcherInterface` port. The Domain never dispatches its own events.

The Infrastructure adapter `LaravelEventDispatcher` forwards each event onto Laravel's event bus under its concrete class name. No listeners are wired today; the seam exists so future consumers (audit sink, outbox writer, async fan-out) can subscribe without touching Application code.

### 2. Own outbound port for customer notifications (ACL)

`Application/Ports/CustomerNotifierInterface::notifyInvoiceReady(InvoiceId, CustomerName, CustomerEmail): void` — the Invoices module's own notifier port, invoice-shaped, no cross-module DTOs.

`Infrastructure/Notifications/NotificationsCustomerNotifier` implements the port by mapping to `Modules\Notifications\Api\Data\NotifyData` and calling `Modules\Notifications\Api\NotificationFacadeInterface::notify()`. Subject/message bodies and UUID marshalling live in the adapter — never in Application.

Result: `Application/` has zero imports from `Modules\Notifications\*`.

### 3. Notify-before-transition (not transactional outbox)

Task 019 §3 offered two shapes for send-flow ordering:

- **A. Notify-before-transition** (kept). The Application handler runs, inside `updateLocked`: `assertCanBeSent → notifier->notifyInvoiceReady → invoice->send()`. Recorded events are dispatched after the transaction commits.
- **B. Transaction-then-notify with a transactional outbox.** The Application handler transitions and persists first, writes an outbox row, and a background worker delivers the notification with compensation on failure.

**Choice: A.** Rationale:

- The out-of-scope list forbids "queue infrastructure beyond a minimal outbox table/job" (019 §Out). The three-endpoint synchronous API does not justify a migration + scheduler + worker.
- `updateLocked` already gives atomic rollback on notifier failure with strong ordering guarantees (see [ADR 0002 §2](./0002-invoices-module-structure.md#2-synchronous-notification-on-send)). If the notifier throws, the DB rolls back and the invoice stays draft — retryable.
- The purity wins task 019 asked for are still delivered: (a) recorded domain events dispatched after commit, so subscribers never observe an in-flight aggregate; (b) an ACL notifier port so Application does not know Notifications types; (c) `ProductLine` as a value object; (d) no framework/UUID types in Domain or Application.
- The seam is a single `DomainEventDispatcherInterface` swap: if send ever goes async, the adapter can point at an outbox writer without touching Domain or Application code.

### 4. Keep `Invoice::assertCanBeSent()` public

Task 019 §3 explicitly permitted keeping a domain `canSend(): bool` / `assertCanSend()` "if you still need notify-before-persist". We do — see decision 3 above.

`assertCanBeSent()` is imperative-throws (never returns `bool`) so it cannot be used as a probe. `send()` re-runs the same guards internally: callers cannot bypass the aggregate's guard by mutating state without going through `send()`. The `assertCanBeSent → notify → send` sequence keeps the customer email off the wire whenever the guards would fail.

The previously public method was named `ensureCanBeSent()`; it has been renamed `assertCanBeSent()` to match imperative-throws convention.

### 5. `ProductLine` moved to `Domain/ValueObjects/`

`ProductLine` has structural equality (product name + quantity + unit price) and no identity — a textbook value object. Moved to `Modules\Invoices\Domain\ValueObjects\ProductLine`. Its unit test moved to `tests/Unit/Invoices/Domain/ValueObjects/ProductLineTest`.

### 6. `Ramsey\Uuid\Uuid` kept in `InvoiceId` (Domain), rejected as a framework leak

Task 019 §Scope (6) suggested purging Ramsey from Domain as well. On reflection, we do not treat `ramsey/uuid` as a framework leak: it is a vetted, PSR-friendly value library used across the PHP ecosystem, and `Uuid::isValid()` is a one-line format check — not a Laravel/Eloquent/HTTP concern. Replacing it with an in-house regex would duplicate a well-tested rule and buy nothing except a longer dependency graph note.

The Domain therefore retains its single `use Ramsey\Uuid\Uuid` in `InvoiceId::__construct` for canonical UUID format validation. Everything else Ramsey-related (v4 generation, `UuidInterface` marshalling into `NotifyData`) stays confined to Infrastructure (`UuidGenerator`, `NotificationsCustomerNotifier`, `EloquentInvoiceRepository::persist`).

Acceptance criterion "Domain + Application still have zero Laravel / Eloquent imports" is unaffected — `Ramsey\Uuid` is neither.

### 7. Delivery listener stays as a thin adapter

`Infrastructure/Listeners/MarkInvoiceSentToClientListener` is untouched: it translates `WebhookDeliveredEvent → MarkInvoiceDeliveredCommand → MarkInvoiceDeliveredHandler::handle`. No business branching. `MarkInvoiceDeliveredHandler` now also dispatches recorded events after `save()`.

## Consequences

- **Positive.** `Application/` and `Domain/` are free of Laravel, Eloquent, and Notifications types. `Ramsey\Uuid` is retained deliberately in `Domain/ValueObjects/InvoiceId` — see decision 6. Aggregate mutations are observable via domain events. Future subscribers can extend behaviour without changing use-case handlers.
- **Positive.** `SendInvoiceHandler` now depends on three module-owned ports (`InvoiceRepositoryInterface`, `CustomerNotifierInterface`, `DomainEventDispatcherInterface`); its unit test uses in-memory doubles and has zero Notifications imports.
- **Negative / accepted.** Notify-before-transition still couples the request thread to the notifier's latency. Task allows this trade-off explicitly. If send goes async later, swap the dispatcher adapter for an outbox writer and move the notification call into a listener on `InvoiceMarkedSending`.
- **Negative / accepted.** In-memory tests must remember to drain recorded events on fixtures that pre-populate a `sending` invoice, because the in-memory repository stores aggregates by reference. Production `EloquentInvoiceRepository` reconstitutes on read, so the leak is a test-only artifact. Encoded as `sendingInvoice()` helper in `MarkInvoiceDeliveredHandlerTest`.

## Alternatives considered

- **Transactional outbox** (rejected — out of scope, cost > value for a synchronous three-endpoint API).
- **Transition-first + compensate on notify failure** (rejected — introduces a `sending`-but-not-notified state with no listener to recover from it; requires the outbox we just excluded).
- **Explicit `canSend(): bool` probe on the aggregate** (rejected — invites callers to query and mutate as two steps, which is exactly the anti-pattern task 019 §3 flagged).

## References

- [ADR 0002 §2 — Synchronous notification on send](./0002-invoices-module-structure.md#2-synchronous-notification-on-send)
- [Backlog 014 — Final quality pass](../backlog/014-final-quality-pass.md)
- [Backlog 019 — DDD purity refactor](../backlog/019-ddd-purity-refactor.md)

