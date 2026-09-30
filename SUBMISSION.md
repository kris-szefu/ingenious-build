# Submission notes

Entry point for reviewers: how to run the suite, what was built, and where decisions live.

The root [`README.md`](./README.md) is the recruiter's original task description and is **intentionally left untouched**. Task status lives in [`docs/BACKLOG.md`](./docs/BACKLOG.md).

## How to run

```bash
./start.sh
```

Brings up Laravel Sail (PHP 8.5, SQLite), installs dependencies, and runs migrations. The API is served at `http://localhost:${APP_PORT:-80}`. To open a shell inside the container:

```bash
docker compose exec app bash
```

### Endpoints

| Method | URL                                      | Purpose                                              |
|--------|------------------------------------------|------------------------------------------------------|
| POST   | `/api/invoices`                          | Create a draft invoice                               |
| GET    | `/api/invoices/{id}`                     | View an invoice with computed totals                 |
| POST   | `/api/invoices/{id}/send`                | Guards + notify + transition to `sending`            |
| GET    | `/api/notification/hook/delivered/{ref}` | Fixture webhook — flips `sending` → `sent-to-client` |

## How to test

```bash
docker compose exec app ./vendor/bin/phpunit
```

Feature tests use `RefreshDatabase` against the same SQLite connection the app runs on — no extra setup. To scope a run:

```bash
docker compose exec app ./vendor/bin/phpunit --filter SendInvoiceEndpointTest
docker compose exec app ./vendor/bin/phpunit tests/Unit/Invoices/Domain
```

Two PHP 8.5 deprecations may surface from stock Laravel's `config/database.php` (`PDO::MYSQL_ATTR_SSL_CA`); they are unrelated to this submission.

### Test layout

```
tests/
  Unit/Invoices/            # Domain + Application, no framework
  Feature/Invoices/         # HTTP endpoints, DB via RefreshDatabase
  Feature/Notification/     # Fixture module, kept as-is
  Support/                  # Shared test doubles
    Invoices/               # InMemoryInvoiceRepository, InvoiceIds, FixedIdGenerator
    Notifications/          # FakeDriver — records DriverInterface::send calls
```

## Reviewer notes

- **Event name:** README says `ResourceDeliveredEvent`; the Notifications fixture ships `WebhookDeliveredEvent`. Code uses the real class; see [ADR 0002 §6](./docs/adr/0002-invoices-module-structure.md#6-use-the-real-class-name-webhookdeliveredevent).
- **Notifications** is a fixture — untouched; Invoices only imports `Modules\Notifications\Api\*`, and only inside `Infrastructure/` (via the ACL adapter `NotificationsCustomerNotifier` and the delivery listener). `Application/` and `Domain/` are cross-module-import-free — see [ADR 0003](./docs/adr/0003-ddd-purity-refactor.md).
- **Design highlights:** the send flow records domain events on the aggregate (`InvoiceMarkedSending`, `InvoiceSentToClient`) that are dispatched after the transaction commits via a module-owned `DomainEventDispatcherInterface`; customer notifications go through Invoices' own `CustomerNotifierInterface` port. `Domain/` and `Application/` have zero Laravel, Eloquent, or Notifications imports. `Ramsey\Uuid\Uuid::isValid()` is retained deliberately inside `InvoiceId` — see [ADR 0003 §6](./docs/adr/0003-ddd-purity-refactor.md).
- **Backlog:** one task ≈ one commit; status in [`docs/BACKLOG.md`](./docs/BACKLOG.md). The core path (create / view / send / deliver), the polish [014](./docs/backlog/014-final-quality-pass.md), and the strict DDD refactor [019](./docs/backlog/019-ddd-purity-refactor.md) are done; OpenAPI tasks [017](./docs/backlog/017-openapi-spec.md) / [018](./docs/backlog/018-openapi-ci-check.md) and the state-machine-library spike [015](./docs/backlog/015-spike-state-machine-library.md) remain as documented follow-ups.

## Where to look

| To find… | Read this |
|---|---|
| Task-by-task history + status | [`docs/BACKLOG.md`](./docs/BACKLOG.md) + [`docs/backlog/`](./docs/backlog/) |
| Big architectural decisions | [`docs/adr/`](./docs/adr/) |
| Notify-vs-transition ordering + ACL notifier rationale | [ADR 0003](./docs/adr/0003-ddd-purity-refactor.md) |
| Module map + cross-module flows | [`docs/architecture/README.md`](./docs/architecture/README.md) |
| HTTP status mapping | [`bootstrap/app.php`](./bootstrap/app.php) |
| Invoice state machine + domain events | [`src/Modules/Invoices/Domain/Entities/Invoice.php`](./src/Modules/Invoices/Domain/Entities/Invoice.php) |
| Send flow guard order | [`src/Modules/Invoices/Application/UseCases/SendInvoice/SendInvoiceHandler.php`](./src/Modules/Invoices/Application/UseCases/SendInvoice/SendInvoiceHandler.php) |
| Invoices' outbound notifier port (ACL) | [`src/Modules/Invoices/Application/Ports/CustomerNotifierInterface.php`](./src/Modules/Invoices/Application/Ports/CustomerNotifierInterface.php) → [`Infrastructure/Notifications/NotificationsCustomerNotifier.php`](./src/Modules/Invoices/Infrastructure/Notifications/NotificationsCustomerNotifier.php) |
| Delivery listener | [`src/Modules/Invoices/Infrastructure/Listeners/MarkInvoiceSentToClientListener.php`](./src/Modules/Invoices/Infrastructure/Listeners/MarkInvoiceSentToClientListener.php) |
