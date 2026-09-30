# Architecture docs

Living documentation of module boundaries, flows, and interfaces.
Update these files whenever a change alters boundaries, dependency direction, or public module `Api` contracts. Guardrail: [`../agent-skills/adr-architecture-guardrails.md`](../agent-skills/adr-architecture-guardrails.md).

Suggested files (add as needed):
- `modules.md` — module map and responsibilities.
- `invoice-flow.md` — create / send / deliver sequence.
- `boundaries.md` — allowed dependency direction and cross-module rules.

## Modules

Two modules ship in this repo. Only the Invoices module is graded; the Notifications module is a fixture and its shape is intentionally not DDD-ish.

```
src/Modules/
  Invoices/          # graded module — DDD layout, hexagonal ports/adapters
  Notifications/     # fixture — provided as-is, exposes an Api\ surface only
```

### Invoices module map

Full rationale in [ADR 0002 — Invoices module structure](../adr/0002-invoices-module-structure.md).

```
src/Modules/Invoices/
  Api/                  # (empty — nothing published cross-module yet)
  Domain/
    Entities/           # Invoice, ProductLine
    ValueObjects/       # InvoiceId, CustomerName, CustomerEmail,
                        # ProductName, Quantity, UnitPrice
    Enums/              # StatusEnum { Draft, Sending, SentToClient }
    Exceptions/         # InvoiceNotFound, InvalidInvoiceId,
                        # InvoiceCannotBeSent, InvoiceCannotBeMarkedSent,
                        # InvalidProductLine
  Application/
    Ports/              # InvoiceRepositoryInterface, IdGeneratorInterface
    UseCases/
      CreateInvoice/    # CreateInvoiceCommand + CreateInvoiceHandler
      GetInvoice/       # GetInvoiceHandler + InvoiceView / ProductLineView
      SendInvoice/      # SendInvoiceCommand + SendInvoiceHandler
      MarkInvoiceDelivered/  # MarkInvoiceDeliveredCommand + Handler
  Infrastructure/
    Ids/                # UuidGenerator
    Eloquent/           # InvoiceModel + product-line model
    Repositories/       # EloquentInvoiceRepository
    Listeners/          # MarkInvoiceSentToClientListener
    Providers/          # InvoiceServiceProvider (bindings + Event::listen)
  Presentation/
    Http/               # CreateInvoiceController, ViewInvoiceController,
                        # SendInvoiceController, CreateInvoiceRequest
    routes.php          # POST /invoices, GET /invoices/{id},
                        # POST /invoices/{id}/send
```

### Dependency direction (inside a module)

`Presentation → Application → Domain`.
`Infrastructure` implements ports declared in `Application/Domain` and is wired in the module's `ServiceProvider`.

### Dependency direction (across modules)

Invoices imports **only** from `Modules\Notifications\Api\*`:

- `Modules\Notifications\Api\NotificationFacadeInterface` — outbound port used by `SendInvoiceHandler`.
- `Modules\Notifications\Api\Data\NotifyData` — outbound DTO.
- `Modules\Notifications\Api\Events\WebhookDeliveredEvent` — inbound event consumed by `MarkInvoiceSentToClientListener`.

Notifications does **not** import anything from Invoices.

## Invoice HTTP surface

Registered in `src/Modules/Invoices/Presentation/routes.php` under Laravel's `/api` prefix:

| Method | Path                          | Controller                 | Success | Failures |
|--------|-------------------------------|----------------------------|---------|----------|
| POST   | `/api/invoices`               | `CreateInvoiceController`  | `201`   | `422` (validation / invalid product line) |
| GET    | `/api/invoices/{id}`          | `ViewInvoiceController`    | `200`   | `404` (missing / not a UUID) |
| POST   | `/api/invoices/{id}/send`     | `SendInvoiceController`    | `202`   | `404` (missing / not a UUID) · `422` (non-draft / no lines / invalid lines) |

Domain exceptions are mapped to HTTP status codes centrally in `bootstrap/app.php`. See ADR 0002 §4 for the rationale of `422` (not `409`) on illegal transitions.

## Cross-module flow

### Create

```
HTTP POST /api/invoices
  → CreateInvoiceController
    → CreateInvoiceHandler
      → IdGeneratorInterface   (UuidGenerator)
      → Invoice::draft(...)    (Domain — enforces value-object invariants)
      → InvoiceRepositoryInterface::save   (EloquentInvoiceRepository)
    ← InvoiceView::fromDomain(Invoice)   (no repository re-fetch)
  ← 201 { id, status, customer_*, product_lines[], total_price }
    Location: /api/invoices/{id}
```

### View

```
HTTP GET /api/invoices/{id}
  → ViewInvoiceController
    → GetInvoiceHandler
      → InvoiceRepositoryInterface::getById
    ← InvoiceView (Application DTO)
  ← 200 { id, status, customer_*, product_lines[], total_price }
```

### Send (synchronous — see ADR 0002 §2)

```
HTTP POST /api/invoices/{id}/send
  → SendInvoiceController
    → SendInvoiceHandler
      → InvoiceRepositoryInterface::updateLocked($id, $mutator)
          [ DB::transaction + SELECT ... FOR UPDATE ]
          → $mutator(Invoice):
              → Invoice::ensureCanBeSent()          (guards — no mutation)
              → NotificationFacadeInterface::notify(NotifyData{ resourceId = invoice.id, ... })
                  → NotificationFacade (Notifications module)
                    → DriverInterface::send         (DummyDriver / FakeDriver in tests)
              → Invoice::send()                     (draft → sending)
          [ persist + commit ]
  ← 202 (empty body)
```

Guard order matters: the domain check runs **before** the notification call so a failure never triggers a customer email. The state transition runs **after** the notification returns so a facade failure leaves the invoice in `draft` and the request retryable. The pessimistic row lock serialises concurrent send requests for the same invoice — the losing request blocks until the winner commits, then its `ensureCanBeSent()` guard fires (invoice is now `sending`) before any second notification is dispatched.

### Deliver (webhook → listener → state transition)

```
HTTP GET /api/notification/hook/delivered/{reference}    (Notifications module)
  → NotificationController::hook
    → NotificationService::delivered(uuid)
      → Event::dispatch(new WebhookDeliveredEvent(resourceId: uuid))
                                                   │
                                                   ▼   (Laravel event bus)
  ← 204                          MarkInvoiceSentToClientListener::handle($event)
                                   → MarkInvoiceDeliveredHandler::handle(
                                       new MarkInvoiceDeliveredCommand(
                                         InvoiceId::fromString($event->resourceId)))
                                     → InvoiceRepositoryInterface::getById
                                     → Invoice::markSentToClient()   (sending → sent-to-client)
                                     → InvoiceRepositoryInterface::save
```

Idempotency + missing-invoice policy: unknown reference or invoice not in `sending` is logged (PSR-3 `warning`) and swallowed. Webhooks are at-least-once by nature; failing loudly would surface Notifications-side races as Invoices-side 500s. See ADR 0002 §5.

The Invoices module's `resourceId` **is** the invoice UUID — see ADR 0002 §3 for why we do not maintain a separate correlation table.

## Naming caveat

The README refers to the delivery event as `ResourceDeliveredEvent`. The class actually shipped by the Notifications module is `Modules\Notifications\Api\Events\WebhookDeliveredEvent`. The code uses the real class name. See ADR 0002 §6.

## API contract
- [ADR 0001 — OpenAPI strategy](../adr/0001-openapi-strategy.md): contract-first. `docs/api/openapi.yaml` is the source of truth; `jane-php/open-api` generates typed PHP DTOs into `src/Modules/Invoices/Presentation/Http/Generated/`; hand-written assemblers under `Presentation/Http/Assemblers/` translate between generated DTOs and Application DTOs (`CreateInvoiceCommand`, `InvoiceView`). `Application/` and `Domain/` never depend on generated code. Implementation tracked in backlog tasks [017](../backlog/017-openapi-spec.md) and [018](../backlog/018-openapi-ci-check.md).

## ADR index
- [0001 — OpenAPI strategy](../adr/0001-openapi-strategy.md)
- [0002 — Invoices module structure](../adr/0002-invoices-module-structure.md)

