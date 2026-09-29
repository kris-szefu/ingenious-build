# laravel-conventions

## Goal
Wire modules into Laravel the same way `Notifications` already does, and follow Laravel 13 essentials at the framework edges.

## When to use
When adding routes, providers, config, controllers, requests, jobs, or artisan-visible features. For rules about what must **not** leak into Domain/Application, see `php-and-laravel-guardrails.md`.

## Module wiring
- Each module registers itself via a `ServiceProvider` in `Modules\<Module>\Infrastructure\Providers\`.
- Bind ports (interfaces) to concrete adapters in the provider's `register()`. Choose lifetime deliberately:
  - `bind()` — transient/stateless (default).
  - `singleton()` — one shared instance per process (audit for state under long-lived workers).
  - `scoped()` — one instance per request/job.
- Load module routes in the provider's `boot()` from `Modules\<Module>\Presentation\routes.php` under an `api` prefix and middleware group. Do **not** add module routes to `routes/api.php` directly.
- Register the provider in `bootstrap/providers.php`.
- Event listeners for cross-module events are registered in the *consuming* module's provider (e.g. Invoices listens to `Modules\Notifications\Api\Events\WebhookDeliveredEvent`).
- Eloquent models live in `Infrastructure` and are **not** exposed outside the module. Repositories return domain entities / DTOs.
- Migrations stay in `database/migrations/` (Laravel convention), one file per table; reversible where practical.
- Config, if needed, goes into `config/<module>.php` and is `mergeConfigFrom()`'d in the provider.

## Controllers & requests (Presentation only)
- Keep controllers thin: **translate HTTP → authorize → invoke application action → build response**. No business rules.
- Use **resource controllers** for CRUD, **invokable** (`__invoke`) controllers for single-action endpoints.
- Validation and HTTP-level authorization go into a dedicated **`FormRequest`**, never inline `$request->validate()` in a controller and never anywhere in `Domain/`/`Application/`.
- Prefer typed request accessors and cast to domain types (enums, value objects) before calling the application service.
- Responses: Eloquent API Resources or explicit DTOs — do not return Eloquent models directly.

Example:
```php
final class SendInvoiceController
{
    public function __invoke(SendInvoiceRequest $request, string $invoiceId, SendInvoice $action): Response
    {
        $action->handle(InvoiceId::fromString($invoiceId));
        return response()->noContent(202);
    }
}
```

## Eloquent (Infrastructure only)
- Declare `$fillable` allow-lists; do not use `$guarded = []`. Never mass-assign raw request data — pass validated, typed values from the application layer.
- Use `casts()` for dates, booleans, JSON, and **backed enums** (e.g. `StatusEnum`).
- Prevent N+1: `with()` / `load()` on hot paths; verify with `expectsDatabaseQueryCount()` when a query budget matters.
- Use `chunkById()` / lazy collections for large batches; pagination for user-facing lists.
- Wrap multi-step writes in `DB::transaction()`. Dispatch jobs/events that must not observe uncommitted state with `->afterCommit()`.
- Bind values in raw SQL via parameters; allow-list identifiers if they must be dynamic.
- Do not rely on model events/observers/global scopes for business rules (see guardrails).

## Jobs & queues (Infrastructure)
- Queue heavy or retryable work with `ShouldQueue`. Make handlers **idempotent** (safe to retry).
- Prefer Laravel 13 attributes when the API supports them: `#[Tries]`, `#[Backoff]`, `#[Timeout]`. Keep retries bounded and timeout below worker/process limits.
- Use `Queue::route(...)` in a ServiceProvider to centrally assign default connection/queue for job classes.
- Never assume `queue:pause --all` / `queue:resume --all` exist — check `php artisan help queue:pause` for the installed app.

## Security (edges)
- CSRF middleware is `PreventRequestForgery` in Laravel 13 (renamed from `VerifyCsrfToken`); Sec-Fetch-Site origin verification supplements tokens. Don't disable CSRF to work around a broken form.
- Enforce authorization on **every** protected action via policies/gates. `Gate::authorize()` fails closed with 403. Hiding a UI button is not authorization. Prefer class-based policies for resources; gates for concise abilities.
- Validate every untrusted boundary (HTTP, CLI, webhook, queue payload). Use `FormRequest` for HTTP.
- Uploads: validate content-type/size/extension, generate random storage names, store on a private disk, serve via authorized endpoint or short-lived URL.
- Cache: Laravel 13's `serializable_classes` defaults to `false` — prefer arrays/scalars/JSON in cache; only allow-list specific trusted classes if you must.
- Never commit `.env` / `APP_KEY` / secrets. `APP_DEBUG=false` in production. Rate-limit sensitive endpoints.

## Testing at the edges
- Use `RefreshDatabase` in Feature tests. Use factories, not production-shaped fixtures.
- Fake integrations: `Event::fake()`, `Mail::fake()`, `Notification::fake()`, `Queue::fake()`, `Http::fake()`, `Storage::fake()`. Never hit real external APIs in ordinary tests.
- Assert status codes, response payload, persistence, authorization, and dispatched side effects.
- Cover unhappy paths: invalid input, unauthenticated / unauthorized, missing records, illegal state transitions, retries.
- Parallel tests (`php artisan test --parallel`) only when DB and external resources are per-process isolated.

## Deploy sequence (reference)
```
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan reload   # graceful worker/Octane/Reverb reload where supported
```
Only backward-compatible expand migrations before switching traffic. Never run `--dev` install in production. Config-cache only on deploy, not during local dev.

## Framework version awareness
- This app targets **PHP 8.5 + Laravel 13**. Do not assume Laravel 12 APIs or third-party package features exist without checking `composer.lock` and installed extensions.
- When unsure about a Laravel 13 signature or attribute namespace, verify against the official docs before generating code — do not invent attribute names or command flags.


