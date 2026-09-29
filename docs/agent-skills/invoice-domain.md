# invoice-domain

Reference notes for the Invoices module — invariants, state machine, HTTP contract, and cross-module flow. Distilled from the README. This is a **facts** document, not a playbook.

## Entities
- **Invoice**: id, status (`StatusEnum`), customerName, customerEmail, productLines[].
- **ProductLine**: productName, quantity (int > 0 when sending), unitPrice (int > 0 when sending), `totalUnitPrice = quantity × unitPrice`.
- **Invoice.totalPrice** = Σ productLine.totalUnitPrice.

## Invariants
- Invoice is **created** only in `draft`.
- Invoice may be created with an empty product line list.
- Quantity and unit price are integers; when present they must be > 0. Enforce at value-object construction.

## State machine
```
draft ──send()──▶ sending ──onDelivered()──▶ sent-to-client
```
- `send()` is allowed **only** from `draft` **and only if** at least one product line exists and every line has quantity > 0 and unitPrice > 0.
- `markSentToClient()` is allowed **only** from `sending`.
- Any other transition throws a typed domain exception.

## Suggested domain errors
- `InvoiceCannotBeSent` — wrong status or invalid lines.
- `InvoiceCannotBeMarkedSent` — not currently `sending`.
- `InvalidProductLine` — non-positive quantity/unitPrice.

## HTTP contract
| Endpoint       | Success | Notable failures |
|----------------|---------|------------------|
| `GET /invoices/{id}`  | `200` | `404` if missing |
| `POST /invoices`      | `201` | `422` on validation errors |
| `POST /invoices/{id}/send` | `202` | `404` missing · `409` or `422` on illegal state / invalid lines · `422` on validation |

Contract rules:
- Validation lives in a `FormRequest` (see `laravel-conventions.md`).
- Response shape is a DTO / API Resource — never a raw Eloquent model.
- Document response fields explicitly: invoice id, status, customer, product lines with `total_unit_price`, plus `total_price`.
- Feature tests must cover: happy path, validation errors, illegal state transitions.

## Cross-module flow (Invoices ↔ Notifications)
Depend only on `Modules\Notifications\Api\`:
- `NotificationFacadeInterface` — triggers the email.
- `Data\NotifyData` — request payload.
- `Events\WebhookDeliveredEvent` — delivery signal.

Inject `NotificationFacadeInterface` into the invoice application service via constructor.

### Send use-case order of operations
1. Load invoice; assert `draft` and valid lines (domain checks).
2. Build `NotifyData` (subject/message may be hardcoded).
3. Call the facade.
4. On success, transition invoice to `sending` and persist.
5. **If the facade throws, do not move to `sending`** — invoice stays `draft`.

### Delivery listener
- Registered in the Invoices module's ServiceProvider (`Infrastructure/Providers/`).
- Listens for `WebhookDeliveredEvent`.
- Correlates the event to an invoice via a reference id in `NotifyData` (likely the invoice id — decide and document).
- Transitions `sending → sent-to-client` via a domain method.
- **Ignores** events for invoices not in `sending` (log + no-op, do not throw).
- **Ignores** events for unknown invoices (log + no-op).

### Naming caveat
README refers to `ResourceDeliveredEvent`; the actual class in `Modules\Notifications\Api\Events\` is `WebhookDeliveredEvent`. Use the real class name in code and flag the mismatch in the PR description.

### Failure modes to cover in tests
- Facade throws → invoice stays `draft`, no state change persisted.
- Delivered event for unknown invoice → no crash, logged.
- Delivered event for invoice not in `sending` → no transition, logged.

Testing: `Event::fake()` for feature tests; fake facade or Mockery mock for use-case tests. See `testing-strategy.md` for layer placement.

