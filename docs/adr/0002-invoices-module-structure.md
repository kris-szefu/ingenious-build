# ADR 0002 — Invoices module structure and cross-module flow

- **Status:** Accepted
- **Date:** 2026-09-30
- **Deciders:** maintainer
- **Related backlog:** [013 — ADR + architecture docs](../backlog/013-adr-and-architecture-docs.md)
- **Related skills:** [`architecture.md`](../agent-skills/architecture.md), [`module-boundaries-guardrails.md`](../agent-skills/module-boundaries-guardrails.md), [`invoice-domain.md`](../agent-skills/invoice-domain.md), [`laravel-conventions.md`](../agent-skills/laravel-conventions.md)
- **See also:** [ADR 0001 — OpenAPI strategy](./0001-openapi-strategy.md)

## Context

The README specifies three HTTP endpoints, a three-state invoice state machine (`draft` → `sending` → `sent-to-client`), a notification side effect on send, and a webhook-triggered transition on delivery. The submission is graded on *"architecture, separation of concerns, and clarity of module boundaries."* Before the module could be built, several structural decisions had to be made that the README does not answer:

1. What internal layout does the Invoices module take?
2. Is the customer notification dispatched **synchronously** as part of the send request, or **queued** for later?
3. What correlation id ties a delivery webhook back to an invoice?
4. Which HTTP status codes represent illegal state transitions and validation failures?
5. What is the policy when a delivery event arrives for an invoice that is missing or not in `sending`?
6. The README references `ResourceDeliveredEvent`, but the code ships `WebhookDeliveredEvent`. Which is authoritative?

This ADR records all six decisions in one place so that reviewers can reconstruct the reasoning without reading every commit. The OpenAPI strategy is a separate concern covered by ADR 0001; this ADR does not restate it.

## Decision

### 1. DDD-lite layout inside `src/Modules/Invoices/`

```
src/Modules/Invoices/
  Api/              # (currently unused — nothing to publish across modules yet)
  Domain/           # Invoice, ProductLine, value objects, enums, domain exceptions
  Application/      # UseCases (CreateInvoice, GetInvoice, SendInvoice,
                    #          MarkInvoiceDelivered), Ports (interfaces)
  Infrastructure/   # Eloquent adapter, UUID generator, service provider, listener
  Presentation/     # Invokable HTTP controllers, FormRequests, routes
```

Rules (enforced by `module-boundaries-guardrails`):

- `Domain/` is pure PHP. No Laravel, no HTTP, no Eloquent.
- `Application/` only depends on ports it declares itself. Business orchestration lives here as invokable use-case handlers.
- `Infrastructure/` is the only place that speaks to Eloquent, Laravel event bus, or PSR-3 logger via container resolution.
- `Presentation/` is the only place that owns request/response shape. Controllers are thin — they translate HTTP into a use-case call.
- Across modules, Invoices depends **only** on `Modules\Notifications\Api\*` (facade interface, `NotifyData`, `WebhookDeliveredEvent`). Nothing in `Modules\Notifications\Application/Infrastructure/Presentation` is imported.
- Invoices does not currently publish anything of its own, so `Api/` stays empty. Adding a public surface is a follow-up if another module ever needs to consume invoice events.

### 2. Send is synchronous, and serialised by a pessimistic row lock

`SendInvoiceHandler::handle()` runs the entire send flow in the request thread, wrapped in a database transaction that pessimistically locks the invoice row:

1. `InvoiceRepositoryInterface::updateLocked($id, $mutator)` opens a transaction and does `SELECT ... FOR UPDATE` on the invoice.
2. Inside the mutator closure: `ensureCanBeSent()` — guard-only, no state mutation.
3. Call `NotificationFacadeInterface::notify(...)`.
4. **Only if the facade returns normally**: `Invoice::send()` (draft → sending). The adapter persists on closure return and commits.

Rationale:

- The task is a recruitment exercise for a synchronous CRUD-shaped API. Queueing adds a job driver, retry semantics, dead-letter handling, and idempotency questions that the README does not ask for.
- Placing the guard **before** the facade call ensures a failure never triggers a customer notification (unit-tested in `SendInvoiceHandlerTest::it_rejects_when_invoice_has_no_product_lines`).
- Placing the state transition **after** the facade call ensures a facade error leaves the invoice in `draft`, so a retry is safe (unit-tested in `it_leaves_invoice_in_draft_when_facade_throws`).
- Wrapping the whole flow in `updateLocked` closes the concurrent-double-send race: two simultaneous requests for the same invoice serialise on the row lock. The winner commits `sending`; the loser's `ensureCanBeSent()` guard then throws `InvoiceCannotBeSent::notInDraft(...)` **before** any second `notify(...)` call. Sequential coverage: `SendInvoiceEndpointTest::returns_422_when_invoice_is_not_in_draft` asserts `FakeDriver::$sent` has exactly one entry after two send calls.

Trade-off: if the notification path becomes slow, the lock is held for the duration and the request thread pays for it. Acceptable at this scale; if it ever isn't, the seam is a single method behind an interface — queueing (via an outbox) is a listener swap, not a redesign.

### 3. Invoice id is the notification reference id

`SendInvoiceHandler::notifyDataFor()` sets `NotifyData::$resourceId` to the invoice's UUID. When the Notifications module dispatches `WebhookDeliveredEvent`, `event->resourceId` is the same UUID, and `MarkInvoiceSentToClientListener` uses it directly as the invoice id (`InvoiceId::fromString(...)`).

Rationale:

- The Notifications API accepts a single opaque `UuidInterface` reference. We need *some* correlation id.
- Using the invoice id avoids a mapping table between notification-ids and invoice-ids. Invoice UUIDs are already globally unique.
- Alternatives considered:
  - **Separate correlation id table** — extra Eloquent model, extra migration, extra port. Zero benefit at this stage.
  - **Random per-notification id + lookup map** — same overhead as above, plus a race window when the webhook arrives before the map is committed.
- Cost: the notification module theoretically learns an "invoice id" via its opaque reference field. In practice `resourceId` is documented as opaque and Notifications never introspects it, so this leaks nothing meaningful.

### 4. Error mapping: 422 for illegal transitions, 404 for missing / invalid ids

| Domain exception            | HTTP status | Where |
|-----------------------------|------------|-------|
| `InvoiceNotFound`           | `404`      | `bootstrap/app.php` global renderer |
| `InvalidInvoiceId` (bad UUID) | `404`      | `bootstrap/app.php` global renderer |
| `InvoiceCannotBeSent` (non-draft, no lines, invalid lines) | `422` | `bootstrap/app.php` global renderer |
| `InvalidProductLine` (create-time) | `422` | `bootstrap/app.php` global renderer |
| FormRequest validation (`CreateInvoiceRequest`) | `422` | Laravel default |

Rationale for **422, not 409**, on illegal state transitions:

- `invoice-domain.md` initially listed "`409` or `422`" as acceptable. We picked **422 uniformly** for every domain-level rejection so that the client sees the same status shape for validation errors and business-rule violations. Consumers only need to disambiguate by the `message` field / RFC 7807 `type` (once ADR 0001 lands).
- 409 (Conflict) is defensible for state-machine transitions, but mixing 409 and 422 forces every client to branch on a second axis. A single 422 with a stable message is easier to consume and easier to document in OpenAPI (see 017).
- 404 is used for both "unknown invoice" and "not-a-UUID" because both are indistinguishable from the client's point of view — the URL simply does not resolve to a resource.

### 5. Delivery listener is idempotent and silent on missing / wrong-state invoices

`MarkInvoiceDeliveredHandler` catches `InvoiceNotFound` and `InvoiceCannotBeMarkedSent` and logs a PSR-3 `warning`, then returns without touching the database.

Rationale:

- Webhooks are inherently at-least-once. Duplicate delivery is normal, not an error.
- A stale reference (invoice deleted, unknown UUID) is a Notifications-side / race condition, not an Invoices-side bug. Failing loudly would 500 the notification module's webhook consumer for no benefit.
- The listener stays a two-line adapter; the "swallow + log" policy lives in the use case, so it is reachable from unit tests without a container, and any future non-HTTP trigger (queue, CLI replay) inherits the same policy.

### 6. Use the real class name: `WebhookDeliveredEvent`

The README refers to `ResourceDeliveredEvent`. The class shipped with the Notifications module is `Modules\Notifications\Api\Events\WebhookDeliveredEvent`. The code uses the real class name; this ADR (and the PR description) flags the mismatch so the reviewer knows it was noticed, not missed.

## Consequences

Easier:

- Every domain rule is unit-testable without spinning up Laravel — `Domain/` and `Application/` have zero framework imports.
- Swapping persistence (SQLite → Postgres → in-memory for tests) is a one-line change in `InvoiceServiceProvider::register()`.
- Swapping the notification driver is a one-line container rebind (already exercised by `FakeDriver` in feature tests).
- The `bootstrap/app.php` renderer table is the single source of truth for HTTP status mapping — reviewers see all five renderers in one place.
- The listener is a two-line adapter; the delivery policy is unit-tested in `MarkInvoiceDeliveredHandlerTest` without any HTTP or event-dispatcher machinery.

Harder:

- The DDD layout has more files than a "Laravel-native" solution. Justified by the evaluation criterion emphasising boundaries.
- Synchronous send blocks the request thread on the notification call. Acceptable at task scale; the ports are shaped so queueing is additive later.
- Using the invoice id as the notification reference means a future notification consumer that inspects `resourceId` would learn something invoice-shaped. Non-issue while Notifications is opaque.

## Alternatives considered

- **Flat Laravel-native layout** (controllers/services/models under `app/`). Rejected: erases the boundary story the README asks the candidate to demonstrate.
- **Queued send via a Laravel job**. Rejected: adds a queue driver, retries, and idempotency questions with no test-time benefit for a three-endpoint API.
- **Correlation-id table** mapping notification ids to invoice ids. Rejected: extra port + adapter + migration with no user-visible benefit; invoice UUIDs already suffice.
- **`409 Conflict`** for illegal state transitions. Rejected in favour of a uniform `422` — see decision 4.
- **Rename the shipped `WebhookDeliveredEvent`** to match the README's `ResourceDeliveredEvent`. Rejected: the Notifications module is documented as "not a reference for the expected invoice design" and is out of scope for edits. Flagging the mismatch is enough.
- **Loud failure on unknown / duplicate delivery events**. Rejected: webhooks are at-least-once by design; failing loudly would surface Notifications-side races as Invoices-side 500s.

## Docs impact

- New: this ADR.
- Updated: [`docs/architecture/README.md`](../architecture/README.md) — Invoices module map + cross-module flow + link here.
- Backlog: [013](../backlog/013-adr-and-architecture-docs.md) marked `done`.
- Skill: [`submission-updates.md`](../agent-skills/submission-updates.md) added to keep `SUBMISSION.md` synced with future decisions.

