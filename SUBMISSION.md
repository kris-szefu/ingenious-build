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
- **Notifications** is a fixture — untouched; Invoices only imports `Modules\Notifications\Api\*`.
- **Backlog:** one task ≈ one commit; status in [`docs/BACKLOG.md`](./docs/BACKLOG.md). Core path (create / view / send / deliver) plus polish [014](./docs/backlog/014-final-quality-pass.md) is done; [019](./docs/backlog/019-ddd-purity-refactor.md) (strict DDD) and OpenAPI tasks [017](./docs/backlog/017-openapi-spec.md) / [018](./docs/backlog/018-openapi-ci-check.md) are follow-ups, not required for this MR.

## Where to look

| To find… | Read this |
|---|---|
| Task-by-task history + status | [`docs/BACKLOG.md`](./docs/BACKLOG.md) + [`docs/backlog/`](./docs/backlog/) |
| Big architectural decisions | [`docs/adr/`](./docs/adr/) |
| Module map + cross-module flows | [`docs/architecture/README.md`](./docs/architecture/README.md) |
| HTTP status mapping | [`bootstrap/app.php`](./bootstrap/app.php) |
| Invoice state machine | [`src/Modules/Invoices/Domain/Entities/Invoice.php`](./src/Modules/Invoices/Domain/Entities/Invoice.php) |
| Send flow guard order | [`src/Modules/Invoices/Application/UseCases/SendInvoice/SendInvoiceHandler.php`](./src/Modules/Invoices/Application/UseCases/SendInvoice/SendInvoiceHandler.php) |
| Delivery listener | [`src/Modules/Invoices/Infrastructure/Listeners/MarkInvoiceSentToClientListener.php`](./src/Modules/Invoices/Infrastructure/Listeners/MarkInvoiceSentToClientListener.php) |
