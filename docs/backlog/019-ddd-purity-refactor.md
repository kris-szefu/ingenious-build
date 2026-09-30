# 019 — DDD purity refactor (strict)

## Story
As the author, I want the Invoices module to follow textbook tactical DDD,
so that domain rules and side effects are expressed as domain decisions,
not application orchestration of foreign types.

## Addresses (README requirements)
- "Preferred Approach: Domain-Driven Design (DDD)"
- Evaluation criteria: architecture / module boundaries (strict interpretation)

## Scope
- In:
  1. **Domain events on the aggregate**
     - `Invoice::send()` records `InvoiceMarkedSending` (or `InvoiceSendRequested`).
     - `Invoice::markSentToClient()` records `InvoiceSentToClient`.
     - Application/Infrastructure pulls & dispatches after successful persist (outbox-lite or post-save dispatch).
  2. **Own outbound port (ACL)**
     - Replace direct use of `NotificationFacadeInterface` / `NotifyData` in Application.
     - Introduce `Application/Ports/CustomerNotifierInterface` (or Domain service port) with invoice-shaped args.
     - Infrastructure adapter maps → `NotifyData` + calls Notifications `Api\`.
  3. **Side-effect order owned by application policy, not split entity API**
     - Remove public `ensureCanBeSent()` as a separate “query then mutate” API surface
       (keep private guards inside `send()`), **or** keep a domain `canSend(): bool` /
       `assertCanSend()` if you still need notify-before-persist — but document the policy
       in one place (unit of work / transactional outbox preferred for purity).
     - Preferred strict shape: transition + persist first (or outbox row), then notify;
       compensate/retry if notify fails — **or** transactional outbox so domain never
       waits on infrastructure.
  4. **`ProductLine` as value object**
     - Move `Entities/ProductLine` → `ValueObjects/ProductLine` (no identity),
       or give it a real identity if lines must be addressed individually later.
  5. **Customer VO exceptions**
     - Ensure all VO/invariant failures are domain exceptions (overlaps 014; finish here if still open).
  6. **No Ramsey / framework types in Domain or Application**
     - UUID bridging only in Infrastructure (ACL / Id mapper).
  7. **Delivery as domain reaction**
     - Keep listening to `WebhookDeliveredEvent` only in Infrastructure.
     - Listener → application command → `Invoice::markSentToClient()` only;
       no business branching in the listener (already mostly true).
- Out:
  - OpenAPI (017/018), state-machine library spike (015).
  - Changing Notifications module internals.
  - Event sourcing / full CQRS read models.
  - Queue infrastructure beyond a minimal outbox table/job.

## Acceptance criteria
- [ ] `Invoice` raises domain events for send and delivered transitions; tests assert recorded events.
- [ ] Application layer has **zero** imports from `Modules\Notifications\*`
      (only Infrastructure adapter may import Notifications `Api\`).
- [ ] `SendInvoiceHandler` depends on an Invoices-owned notifier port, not `NotifyData`.
- [ ] `ProductLine` lives under ValueObjects (or has an explicit identity + repo concern).
- [ ] Domain + Application still have zero Laravel / Eloquent imports.
- [ ] Existing behavioural tests still green (HTTP contract unchanged unless ADR says otherwise).
- [ ] New ADR `0003-ddd-purity-refactor.md` records notify-vs-transition ordering choice
      (outbox vs notify-before-persist) and why.

## Tests
- Unit (Domain): event recording on `send` / `markSentToClient`; illegal transitions still throw.
- Unit (Application): handler uses port mock; no Notifications types in test subject imports.
- Feature: send + webhook flow unchanged from client POV.

## Guardrails to run
- module-boundaries-guardrails
- php-and-laravel-guardrails
- laravel-conventions
- adr-architecture-guardrails

## Commit
`refactor(invoices): strict DDD events, ACL notifier, and ProductLine VO`

## Status
todo
