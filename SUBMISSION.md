# Submission notes

This file exists so a reviewer can get from a fresh clone to a running suite in under two minutes, and see at a glance what was built and where to look for the reasoning.

The root [`README.md`](./README.md) is the recruiter's original task description and is **intentionally left untouched**. Task-by-task status lives in [`docs/BACKLOG.md`](./docs/BACKLOG.md).

## How to run

```bash
./start.sh
```

Brings up Laravel Sail (PHP 8.5, SQLite), installs dependencies, and runs migrations. The API is served at `http://localhost:${APP_PORT:-80}`. To open a shell inside the container:

```bash
docker compose exec app bash
```

### Endpoints

| Method | URL                                        | Purpose                                                            |
|--------|--------------------------------------------|--------------------------------------------------------------------|
| POST   | `/api/invoices`                            | Create a draft invoice                                             |
| GET    | `/api/invoices/{id}`                       | View an invoice with computed totals                               |
| POST   | `/api/invoices/{id}/send`                  | Guards + notify + transition to `sending`                          |
| GET    | `/api/notification/hook/delivered/{ref}`   | Fixture webhook — flips `sending` → `sent-to-client`               |

## How to test

```bash
docker compose exec app ./vendor/bin/phpunit
```

Feature tests use `RefreshDatabase` against the same SQLite connection the app runs on — no extra setup. To scope a run:

```bash
docker compose exec app ./vendor/bin/phpunit --filter SendInvoiceEndpointTest
docker compose exec app ./vendor/bin/phpunit tests/Unit/Invoices/Domain
```

Current baseline: **65 tests, 148 assertions, all green.** Two PHP 8.5 deprecations surface from stock Laravel's `config/database.php` (`PDO::MYSQL_ATTR_SSL_CA`) and are unrelated to the submission.

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

- **Event-name mismatch.** The README (recruiter's task description) refers to the delivery event as `ResourceDeliveredEvent`, but the Notifications fixture module actually ships `Modules\Notifications\Api\Events\WebhookDeliveredEvent`. The mismatch is pre-existing drift between the task description and the starter code — not something introduced here. The README explicitly says the Notifications module *"should not be treated as a reference for DDD structure"* and is out of scope for edits, so renaming the fixture class was not an option. The code uses the real class name; flagged in [ADR 0002 §6](./docs/adr/0002-invoices-module-structure.md#6-use-the-real-class-name-webhookdeliveredevent).
- **Notifications module is a fixture.** Its shape is intentionally not DDD-ish (per the README) and was not modified. The Invoices module only reaches into `Modules\Notifications\Api\*`.
- **Backlog-driven, one-task-one-commit** — every commit maps 1:1 to a `docs/backlog/NNN-*.md` file. `docs/BACKLOG.md` is the status dashboard.

## Where to look

| To find… | Read this |
|---|---|
| Task-by-task history + status | [`docs/BACKLOG.md`](./docs/BACKLOG.md) + [`docs/backlog/`](./docs/backlog/) |
| Big architectural decisions | [`docs/adr/`](./docs/adr/) |
| Module map + cross-module flow diagrams | [`docs/architecture/README.md`](./docs/architecture/README.md) |
| Agent working rules (skills that governed this build) | [`docs/agent-skills/`](./docs/agent-skills/) |
| HTTP status mapping | [`bootstrap/app.php`](./bootstrap/app.php) |
| Invoice state machine | [`src/Modules/Invoices/Domain/Entities/Invoice.php`](./src/Modules/Invoices/Domain/Entities/Invoice.php) |
| Send flow guard order | [`src/Modules/Invoices/Application/UseCases/SendInvoice/SendInvoiceHandler.php`](./src/Modules/Invoices/Application/UseCases/SendInvoice/SendInvoiceHandler.php) |
| Delivery listener | [`src/Modules/Invoices/Infrastructure/Listeners/MarkInvoiceSentToClientListener.php`](./src/Modules/Invoices/Infrastructure/Listeners/MarkInvoiceSentToClientListener.php) |


