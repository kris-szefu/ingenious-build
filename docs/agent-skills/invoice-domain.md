# invoice-domain

Reference notes for the Invoices module — invariants, state machine, HTTP contract, and cross-module flow. Distilled from the README plus ADRs 0002/0003. This is a **facts** document, not a playbook.

## Entities / value objects
- **Invoice** (aggregate): id, status (`StatusEnum`), customerName, customerEmail, productLines[].
- **ProductLine** (value object under `Domain/ValueObjects/`): productName, quantity, unitPrice; `totalUnitPrice = quantity × unitPrice`. No identity.
- **Invoice.totalPrice** = Σ productLine.totalUnitPrice.

## Invariants
- Invoice is **created** only in `draft`.
- Invoice may be created with an empty product line list.
- Quantity and unit price are integers; when present they must be > 0. Enforce at value-object construction (`Quantity`, `UnitPrice`).

## State machine
```
draft ──send()──▶ sending ──markSentToClient()──▶ sent-to-client
```
- `send()` is allowed **only** from `draft` **and only if** at least one product line exists. Line qty/price > 0 is already guaranteed by the VOs.
- Callers that notify before transitioning use public `assertCanBeSent()` first; `send()` re-checks the same guards.
- `markSentToClient()` is allowed **only** from `sending`.
- Any other transition throws a typed domain exception.
- Successful `send()` / `markSentToClient()` record domain events (`InvoiceMarkedSending`, `InvoiceSentToClient`); Application pulls and dispatches them after commit.

## Suggested domain errors
- `InvoiceCannotBeSent` — wrong status or no product lines.
- `InvoiceCannotBeMarkedSent` — not currently `sending`.
- `InvalidProductLine` — non-positive quantity/unitPrice.
- `InvalidCustomer` — empty name / invalid email.

## HTTP contract
| Endpoint       | Success | Notable failures |
|----------------|---------|------------------|
| `GET /invoices/{id}`  | `200` | `404` if missing / invalid id |
| `POST /invoices`      | `201` (+ `Location`) | `422` on validation / domain VO errors |
| `POST /invoices/{id}/send` | `202` (empty body) | `404` missing · `422` on illegal state / no lines |

Contract rules:
- Validation lives in a `FormRequest` (see `laravel-conventions.md`).
- Response shape is an Application view DTO (`InvoiceView::toArray()`) — never a raw Eloquent model.
- Canonical HTTP shapes: [`docs/api/openapi.yaml`](../api/openapi.yaml).
- Feature tests must cover: happy path, validation errors, illegal state transitions.

## Cross-module flow (Invoices ↔ Notifications)

`Application/` depends on **Invoices-owned ports only** (`CustomerNotifierInterface`, `DomainEventDispatcherInterface`, repository/id ports). Cross-module types stay in `Infrastructure/`.

Infrastructure may import `Modules\Notifications\Api\`:
- `NotificationFacadeInterface` + `Data\NotifyData` — used only by `NotificationsCustomerNotifier` (ACL).
- `Events\WebhookDeliveredEvent` — consumed by `MarkInvoiceSentToClientListener`.

### Send use-case order of operations
1. `updateLocked` — load + pessimistic row lock.
2. `Invoice::assertCanBeSent()` (domain checks; no mutation).
3. `CustomerNotifierInterface::notifyInvoiceReady(...)` (ACL → Notifications facade).
4. `Invoice::send()` → `sending`; pull recorded events.
5. Persist + commit; then dispatch domain events.
6. **If the notifier throws, do not move to `sending`** — transaction rolls back, invoice stays `draft`.

### Delivery listener
- Registered in the Invoices module's ServiceProvider (`Infrastructure/Providers/`).
- Listens for `WebhookDeliveredEvent`.
- Correlates via `resourceId` = invoice UUID (documented in ADR 0002 §3 / ADR 0003).
- Transitions `sending → sent-to-client` via `MarkInvoiceDeliveredHandler` → domain method.
- **Ignores** events for invoices not in `sending` (log + no-op, do not throw).
- **Ignores** events for unknown invoices (log + no-op).

### Naming caveat
README refers to `ResourceDeliveredEvent`; the actual class in `Modules\Notifications\Api\Events\` is `WebhookDeliveredEvent`. Use the real class name in code and flag the mismatch in the PR description.

### Failure modes to cover in tests
- Notifier throws → invoice stays `draft`, no state change persisted.
- Delivered event for unknown invoice → no crash, logged.
- Delivered event for invoice not in `sending` → no transition, logged.
- Sequential double-send → second call 422, only one notification.

Testing: port doubles (`RecordingCustomerNotifier`, `RecordingEventDispatcher`) for unit tests; `FakeDriver` for feature notification path. See `testing-strategy.md` for layer placement.
